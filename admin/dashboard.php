<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once INCLUDE_PATH . '/db.php';
require_once INCLUDE_PATH . '/auth.php';
require_once INCLUDE_PATH . '/functions.php';

try {
    $conn = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASSWORD
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

function get_count($table) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM $table");
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

function get_article_categories_count() {
    global $conn;
    $stmt = $conn->prepare("SELECT c.name AS category_name, COUNT(a.id) AS article_count
                            FROM articles a
                            LEFT JOIN categories c ON a.category_id = c.id
                            WHERE a.status = 'published'
                            GROUP BY c.name");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_user_roles_count() {
    global $conn;
    $stmt = $conn->prepare("SELECT r.role_name, COUNT(u.user_id) AS count 
                            FROM users u 
                            LEFT JOIN roles r ON u.role_id = r.role_id 
                            GROUP BY r.role_name");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$articles_count = get_count('articles');
$users_count = get_count('users');
$article_categories = get_article_categories_count();
$user_roles = get_user_roles_count();

$today_views_stmt = $conn->prepare("SELECT SUM(view_count) as views FROM articles WHERE DATE(created_at) = CURDATE()");
$today_views_stmt->execute();
$today_views_count = $today_views_stmt->fetch(PDO::FETCH_ASSOC)['views'] ?? 0;

$pending_stmt = $conn->prepare("SELECT COUNT(*) as count FROM articles WHERE status = 'pending'");
$pending_stmt->execute();
$pending_articles_count = $pending_stmt->fetch(PDO::FETCH_ASSOC)['count'];

$stmt = $conn->query("SELECT device_type, COUNT(*) as count FROM visit_logs GROUP BY device_type");
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$view_trends_stmt = $conn->prepare("
    SELECT SUM(view_count) as views, DATE(created_at) as date 
    FROM articles 
    WHERE created_at >= ? 
    GROUP BY date 
    ORDER BY date ASC
");
$view_trends_stmt->execute([date('Y-m-01', strtotime('-6 months'))]);
$view_trend_data = $view_trends_stmt->fetchAll(PDO::FETCH_ASSOC);

$top_articles_stmt = $conn->prepare("
    SELECT title, view_count 
    FROM articles 
    ORDER BY view_count DESC 
    LIMIT 5
");
$top_articles_stmt->execute();
$top_articles_data = $top_articles_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>控制面板</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= CSS_URL ?>/admin_style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="container-fluid py-4">

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card p-3 d-flex flex-row align-items-center">
                <i class="fas fa-newspaper fs-4 me-3 text-primary"></i>
                <div>
                    <h6 class="mb-1">文章总数</h6>
                    <strong><?= $articles_count ?> 篇</strong>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 d-flex flex-row align-items-center">
                <i class="fas fa-users fs-4 me-3 text-success"></i>
                <div>
                    <h6 class="mb-1">用户数量</h6>
                    <strong><?= $users_count ?> 位</strong>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 d-flex flex-row align-items-center">
                <i class="fas fa-eye fs-4 me-3 text-warning"></i>
                <div>
                    <h6 class="mb-1">今日浏览</h6>
                    <strong><?= $today_views_count ?> 次</strong>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 d-flex flex-row align-items-center">
                <i class="fas fa-exclamation-triangle fs-4 me-3 text-danger"></i>
                <div>
                    <h6 class="mb-1">待处理</h6>
                    <strong><?= $pending_articles_count ?> 条</strong>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-4">
        <div class="col-md-6">
            <div class="card p-3">
                <h6 class="mb-3"><i class="fas fa-chart-bar me-2"></i>文章浏览趋势</h6>
                <canvas id="viewTrendChart"></canvas>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card p-3">
                <h6 class="mb-3"><i class="fas fa-user-cog me-2"></i>用户角色统计</h6>
                <canvas id="userRoleChart"></canvas>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-4">
        <div class="col-md-6">
            <div class="card p-3">
                <h6 class="mb-3"><i class="fas fa-th-large me-2"></i>文章分类统计</h6>
                <canvas id="categoryChart"></canvas>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card p-3">
                <h6 class="mb-3"><i class="fas fa-trophy me-2"></i>热度榜单</h6>
                <ul class="list-group list-group-flush">
                    <?php foreach ($top_articles_data as $article): ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <strong><?= htmlspecialchars($article['title']) ?></strong>
                            <span class="badge bg-warning"><?= $article['view_count'] ?> 浏览</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

</div>

<script>
    const viewTrendChart = new Chart(document.getElementById('viewTrendChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($view_trend_data, 'date')) ?>,
            datasets: [{
                label: '浏览量',
                data: <?= json_encode(array_column($view_trend_data, 'views')) ?>,
                borderColor: 'rgba(255, 159, 64, 1)',
                backgroundColor: 'rgba(255, 159, 64, 0.2)',
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    const categoryChart = new Chart(document.getElementById('categoryChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($article_categories, 'category_name')) ?>,
            datasets: [{
                label: '文章数量',
                data: <?= json_encode(array_column($article_categories, 'article_count')) ?>,
                backgroundColor: [
                    'rgba(54, 162, 235, 0.6)',
                    'rgba(255, 99, 132, 0.6)',
                    'rgba(255, 206, 86, 0.6)',
                    'rgba(75, 192, 192, 0.6)',
                    'rgba(153, 102, 255, 0.6)'
                ],
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            scales: {
                x: { beginAtZero: true }
            }
        }
    });

    const userRoleChart = new Chart(document.getElementById('userRoleChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($user_roles, 'role_name')) ?>,
            datasets: [{
                label: '用户数量',
                data: <?= json_encode(array_column($user_roles, 'count')) ?>,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 206, 86, 0.7)'
                ],
                borderColor: '#fff',
                borderWidth: 1
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            scales: {
                x: { beginAtZero: true }
            }
        }
    });
</script>
</body>
</html>

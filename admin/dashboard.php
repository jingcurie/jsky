<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/config.php';
require_once INCLUDE_PATH . '/db.php';
require_once INCLUDE_PATH . '/auth.php';
require_once INCLUDE_PATH . '/functions.php';

if (!isLoggedIn()) {
    redirect('/admin/login.php');
}

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

// 获取文章统计数据
function get_dashboard_stats()
{
    global $conn;

    $stmt = $conn->query("
        SELECT 
            (SELECT COUNT(*) FROM articles) as articles_count,
            (SELECT COUNT(*) FROM users) as users_count,
            (SELECT SUM(view_count) FROM articles) as total_views,
            (SELECT COUNT(*) FROM articles WHERE status = 'draft') as pending_articles
    ");
    $basic_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // 获取文章分类统计
    $stmt = $conn->query("
        SELECT c.name AS category_name, COUNT(a.id) AS article_count
        FROM articles a
        LEFT JOIN categories c ON a.category_id = c.id
        WHERE a.status = 'published'
        GROUP BY c.name
    ");
    $article_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 获取热门文章
    $stmt = $conn->query("
        SELECT title, view_count 
        FROM articles 
        ORDER BY view_count DESC 
        LIMIT 5
    ");
    $top_articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 获取浏览趋势
    $stmt = $conn->prepare("
        SELECT SUM(view_count) as views, DATE(created_at) as date 
        FROM articles 
        WHERE created_at >= ? 
        GROUP BY date 
        ORDER BY date ASC
    ");
    $stmt->execute([date('Y-m-01', strtotime('-6 months'))]);
    $view_trends = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'basic_stats' => $basic_stats,
        'article_categories' => $article_categories,
        'top_articles' => $top_articles,
        'view_trends' => $view_trends
    ];
}

$stats = get_dashboard_stats();
?>
<!DOCTYPE html>
<html lang="zh">

<head>
    <meta charset="UTF-8">
    <title>控制面板</title>
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"> -->
    <link href="/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= CSS_URL ?>/admin_style.css" rel="stylesheet">
    <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"> -->
    <link rel="stylesheet" href="/assets/css/all.min.css">
    <!-- <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> -->
    <script src="/assets/js/chart.js"></script>
</head>

<body>
    <div class="container-fluid py-4">
        <!-- 顶部指标卡片 -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card small-card p-3 d-flex flex-row align-items-center">
                    <i class="fas fa-newspaper fs-4 me-3 text-primary"></i>
                    <div>
                        <h6 class="mb-1">文章总数</h6>
                        <strong><?= $stats['basic_stats']['articles_count'] ?> 篇</strong>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card  small-card p-3 d-flex flex-row align-items-center">
                    <i class="fas fa-eye fs-4 me-3 text-warning"></i>
                    <div>
                        <h6 class="mb-1">文章总浏览</h6>
                        <strong><?= $stats['basic_stats']['total_views'] ?? 0 ?> 次</strong>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card  small-card p-3 d-flex flex-row align-items-center">
                    <i class="fas fa-exclamation-triangle fs-4 me-3 text-danger"></i>
                    <div>
                        <h6 class="mb-1">待发布文章</h6>
                        <strong><?= $stats['basic_stats']['pending_articles'] ?> 条</strong>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card  small-card p-3 d-flex flex-row align-items-center">
                    <i class="fas fa-users fs-4 me-3 text-success"></i>
                    <div>
                        <h6 class="mb-1">用户数量</h6>
                        <strong><?= $stats['basic_stats']['users_count'] ?> 位</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- 主要内容区域 -->
        <div class="row g-4">
            <!-- 左栏 -->
            <div class="col-lg-8">
                <div class="card p-3 mb-4">
                    <h6 class="mb-3"><i class="fas fa-chart-line me-2"></i>内容数据趋势（过去6个月内，每天创建的文章的总浏览量）</h6>
                    <div class="chart-container" style="position: relative; height:300px;">
                        <canvas id="contentTrendChart"></canvas>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-6 ">
                        <div class="card p-3">
                            <h6 class="mb-3"><i class="fas fa-chart-bar me-2"></i>访问分析</h6>
                            <div class="chart-container" style="position: relative; height:300px;">
                                <canvas id="visitAnalysisChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- 新增：每小时访问量图表（添加在这里！） -->
                    <div class="col-lg-6 ">
                        <div class="card p-3"> <!-- mt-4 是上边距，避免紧贴上方卡片 -->
                            <h6 class="mb-3"><i class="fas fa-clock me-2"></i>每小时访问量</h6>
                            <canvas id="hourlyVisitChart" height="300"></canvas> <!-- 固定高度避免过大 -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- 右栏 -->
            <div class="col-lg-4">
                <div class="card p-3 mb-4">
                    <h6 class="mb-3"><i class="fas fa-th-large me-2"></i>文章分类</h6>
                    <canvas id="categoryChart"></canvas>
                </div>

                <div class="card p-3">
                    <h6 class="mb-3"><i class="fas fa-trophy me-2"></i>热门文章</h6>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($stats['top_articles'] as $article): ?>
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="text-truncate" style="max-width: 70%;"><?= htmlspecialchars($article['title']) ?></span>
                                <span class="badge bg-warning"><?= $article['view_count'] ?> 浏览</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>


            </div>
        </div>
    </div>

    <script src="/assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // 初始化内容趋势图表
        const contentTrendChart = new Chart(
            document.getElementById('contentTrendChart').getContext('2d'), {
                type: 'line',
                data: {
                    labels: <?= json_encode(array_column($stats['view_trends'], 'date')) ?>,
                    datasets: [{
                        label: '文章浏览量',
                        data: <?= json_encode(array_column($stats['view_trends'], 'views')) ?>,
                        borderColor: 'rgba(255, 159, 64, 1)',
                        backgroundColor: 'rgba(255, 159, 64, 0.2)',
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            }
        );

        // 初始化分类图表
        const categoryChart = new Chart(
            document.getElementById('categoryChart').getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode(array_column($stats['article_categories'], 'category_name')) ?>,
                    datasets: [{
                        data: <?= json_encode(array_column($stats['article_categories'], 'article_count')) ?>,
                        backgroundColor: [
                            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                            '#9966FF', '#FF9F40', '#8AC24A', '#EA5F89'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            }
        );

        // 异步加载访问分析数据
        document.addEventListener("DOMContentLoaded", async function() {
            try {
                const response = await fetch('get_visit_stats.php');
                if (!response.ok) throw new Error('Network response was not ok');

                const visitData = await response.json();
                renderVisitAnalysis(visitData);
            } catch (error) {
                console.error('Error loading visit data:', error);
            }
        });

        function renderVisitAnalysis(data) {
            // 访问分析图表（组合图表）
            new Chart(document.getElementById('visitAnalysisChart'), {
                type: 'bar',
                data: {
                    labels: data.trend_data.map(row => row.date),
                    datasets: [{
                            label: '总访问量',
                            data: data.trend_data.map(row => row.count),
                            backgroundColor: 'rgba(54, 162, 235, 0.7)',
                            type: 'bar',
                            order: 2
                        },
                        {
                            label: '移动端',
                            data: data.trend_data.map(row => data.device_by_day['Mobile']?.[row.date] || 0),
                            borderColor: '#FF6384',
                            backgroundColor: 'rgba(255, 99, 132, 0.1)',
                            type: 'line',
                            order: 1,
                            tension: 0.3
                        },
                        {
                            label: '台式机',
                            data: data.trend_data.map(row => data.device_by_day['Desktop']?.[row.date] || 0),
                            backgroundColor: 'rgba(255, 99, 132, 0.1)',
                            borderColor: '#1E88E5',
                            borderWidth: 2,
                            order: 0, // 柱状图在底层
                            type: 'line',
                            tension: 0.3
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        },
                        subtitle: {
                            display: true,
                            text: `今日访问: ${data.today_visits} | 独立访客: ${data.unique_visitors}`,
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // 新增：渲染每小时访问量图表
            new Chart(document.getElementById('hourlyVisitChart'), {
                type: 'bar',
                data: {
                    labels: data.hourly_data.map(row => row.hour + ':00'), // X轴：0-23时
                    datasets: [{
                        label: '访问量',
                        data: data.hourly_data.map(row => row.count),
                        backgroundColor: '#36A2EB',
                        borderColor: '#1E88E5',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false // 隐藏图例（因为只有一个数据集）
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: '访问次数'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: '时间（小时）'
                            }
                        }
                    }
                }
            });
        }
    </script>
</body>

</html>
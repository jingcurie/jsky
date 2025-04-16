<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../includes/config.php';
require_once INCLUDE_PATH . '/db.php';
require_once INCLUDE_PATH . '/check_ip_whitelist.php';
require_once INCLUDE_PATH . '/auth.php';
require_once INCLUDE_PATH . '/functions.php';

csrfProtect();

if (!isLoggedIn()) {
    redirect('login.php');
}

// 获取分类 ID 和名称
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : null;
if ($category_id) {
    $category = getById($conn, 'categories', 'id', $category_id);
    $category_name = $category ? $category['name'] : '未知分类';
} else {
    redirect('categories.php');
}

// 单个软删除文章
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $article = getById($conn, "articles", "id", $_POST['delete_id']);
    $success = update($conn, 'articles', 'id', $_POST['delete_id'], [
        'is_deleted' => 1,
        'deleted_at' => date('Y-m-d H:i:s'),
        'deleted_by' => $_SESSION['user_id']
    ]);
    if ($success) {
        log_operation($conn, $_SESSION['user_id'], $_SESSION['username'], '软删除', $article["category_id"] . '文章', $_POST['delete_id'], $article["title"]);
        // 构建所有原参数
        $query = http_build_query([
            'category_id' => $category_id,
            'page' => $_GET['page'] ?? 1,
            'sort' => $_GET['sort'] ?? '',
            'order' => $_GET['order'] ?? '',
            'status' => $_GET['status'] ?? '',
            'start_date' => $_GET['start_date'] ?? '',
            'end_date' => $_GET['end_date'] ?? '',
            'keyword' => $_GET['keyword'] ?? ''
        ]);

        redirect("articles.php?$query");
        //redirect("articles.php?category_id=" . $_POST['category_id']);
    } else {
        $error = "删除文章失败";
    }
}

// 批量软删除
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_delete'])) {
    if (!empty($_POST['selected_articles'])) {
        $successCount = 0;
        $errorCount = 0;

        foreach ($_POST['selected_articles'] as $articleId) {
            $article = getById($conn, "articles", "id", $articleId);
            $success = update($conn, 'articles', 'id', $articleId, [
                'is_deleted' => 1,
                'deleted_at' => date('Y-m-d H:i:s'),
                'deleted_by' => $_SESSION['user_id']
            ]);
            if ($success) {
                log_operation($conn, $_SESSION['user_id'], $_SESSION['username'], '软删除', $article["category_id"] . '文章', $article["id"], $article["title"]);
                $successCount++;
            } else {
                $errorCount++;
            }
        }

        $_SESSION['success_message'] = "成功软删除 $successCount 篇文章";
        $query = http_build_query([
            'category_id' => $category_id,
            'page' => $_GET['page'] ?? 1,
            'sort' => $_GET['sort'] ?? '',
            'order' => $_GET['order'] ?? '',
            'status' => $_GET['status'] ?? '',
            'start_date' => $_GET['start_date'] ?? '',
            'end_date' => $_GET['end_date'] ?? '',
            'keyword' => $_GET['keyword'] ?? ''
        ]);

        redirect("articles.php?$query");
        // redirect("articles.php?category_id=$category_id");
    } else {
        $error = "请先选择要删除的文章";
    }
}

$statusBadge = [
    'draft' => ['color' => 'secondary', 'icon' => 'fa-edit', 'text' => '草稿'],
    'published' => ['color' => 'success', 'icon' => 'fa-check-circle', 'text' => '发布'],
    'archived' => ['color' => 'dark', 'icon' => 'fa-archive', 'text' => '归档']
];

// =========== 分页 + 查询功能（改为 trash.php 风格） ============
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$keyword = $_GET['keyword'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$where = "WHERE category_id = ? AND is_deleted = 0";
$params = [$category_id];

if ($start_date) {
    $where .= " AND created_at >= ?";
    $params[] = $start_date . " 00:00:00";
}
if ($end_date) {
    $where .= " AND created_at <= ?";
    $params[] = $end_date . " 23:59:59";
}
if ($keyword) {
    $where .= " AND (title LIKE ? OR author LIKE ?)";
    $params[] = '%' . $keyword . '%';
    $params[] = '%' . $keyword . '%';
}
if ($statusFilter && in_array($statusFilter, ['draft', 'published', 'archived'])) {
    $where .= " AND status = ?";
    $params[] = $statusFilter;
}

$records_per_page = 20;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $records_per_page;

$sort_column = isset($_GET['sort']) && in_array($_GET['sort'], ['title', 'author', 'created_at', 'updated_at', 'view_count', 'status']) ? $_GET['sort'] : 'created_at';
$sort_order = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'ASC' : 'DESC';

$total = query($conn, "SELECT COUNT(*) as count FROM articles $where", $params)[0]['count'];
$articles = query($conn, "SELECT * FROM articles $where ORDER BY $sort_column $sort_order LIMIT $records_per_page OFFSET $offset", $params);
$total_pages = max(1, ceil($total / $records_per_page));
$page = $current_page;
?>

<!DOCTYPE html>
<html lang="zh">

<head>
    <meta charset="UTF-8">
    <title>文章管理</title>
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/all.min.css">
    <link href="<?= CSS_URL ?>/admin_style.css" rel="stylesheet">
    <style>
        .checkbox-cell {
            width: 40px;
        }

        #bulkDeleteBtn[disabled] {
            opacity: 0.5;
            pointer-events: none;
        }

        #search {
            max-width: 80%;
        }

        #search>div {
            min-width: 200px;
        }
    </style>
</head>

<body>
    <div class="container-fluid mt-4">
        <h2><i class="fas fa-newspaper"></i> <?= htmlspecialchars($category_name) ?> 文章管理</h2>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        <?php if (!empty($_SESSION['success_message'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success_message'];
                                                unset($_SESSION['success_message']); ?></div>
        <?php endif; ?>

        <!-- 状态按钮 -->
        <div class="btn-group btn-group-sm mb-3" role="group">
            <a href="?category_id=<?= $category_id ?>" class="btn btn-outline-secondary <?= $statusFilter == '' ? 'active' : '' ?>">全部</a>
            <?php foreach ($statusBadge as $key => $config): ?>
                <a href="?category_id=<?= $category_id ?>&status=<?= $key ?>" class="btn btn-outline-<?= $config['color'] ?> <?= $statusFilter == $key ? 'active' : '' ?>">
                    <i class="fas <?= $config['icon'] ?>"></i> <?= $config['text'] ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- 筛选表单 -->
        <form method="get" class="row mb-3" id="search">
            <input type="hidden" name="category_id" value="<?= $category_id ?>">
            <input type="hidden" name="sort" value="<?= $sort_column ?>">
            <input type="hidden" name="order" value="<?= $sort_order ?>">
            <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">

            <div class="col-md-3">
                <input type="date" class="form-control" name="start_date" value="<?= htmlspecialchars($start_date) ?>" placeholder="起始日期">
            </div>
            <div class="col-md-3">
                <input type="date" class="form-control" name="end_date" value="<?= htmlspecialchars($end_date) ?>" placeholder="结束日期">
            </div>
            <div class="col-md-4">
                <input type="text" class="form-control" name="keyword" value="<?= htmlspecialchars($keyword) ?>" placeholder="搜索标题或作者">
            </div>
            <div class="col-md-2">
                <button class="btn btn-edit btn-sm w-100 h-100" type="submit"><i class="fas fa-search"></i> 筛选</button>
            </div>
        </form>

        <!-- 批量操作按钮 -->
        <form id="bulkActionForm" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()) ?>">
            <div class="d-flex justify-content-between mb-2">
                <a href="article_form.php?category_id=<?= $category_id ?>" class="btn btn-create"><i class="fas fa-plus"></i> 新建文章</a>
                <button type="button" id="bulkDeleteBtn" class="btn btn-danger" disabled data-bs-toggle="modal" data-bs-target="#bulkDeleteModal">
                    <i class="fas fa-trash"></i> 批量删除 (<span id="selectedCount">0</span>)
                </button>
            </div>

            <!-- 表格 -->
            <table class="table table-bordered table-hover text-center">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll" class="form-check-input"></th>
                        <th><a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'status', 'order' => ($sort_column === 'status' && $sort_order === 'ASC') ? 'desc' : 'asc'])) ?>" class="text-decoration-none text-white">
                                状态 <i class="fas <?= $sort_column === 'status' ? ($sort_order === 'ASC' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort' ?>"></i></a></th>
                        <th><a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'title', 'order' => ($sort_column === 'title' && $sort_order === 'ASC') ? 'desc' : 'asc'])) ?>" class="text-decoration-none text-white">
                                标题 <i class="fas <?= $sort_column === 'title' ? ($sort_order === 'ASC' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort' ?>"></i></a></th>
                        <th><a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'author', 'order' => ($sort_column === 'author' && $sort_order === 'ASC') ? 'desc' : 'asc'])) ?>" class="text-decoration-none text-white">
                                作者 <i class="fas <?= $sort_column === 'author' ? ($sort_order === 'ASC' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort' ?>"></i></a></th>
                        <th><a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'created_at', 'order' => ($sort_column === 'created_at' && $sort_order === 'ASC') ? 'desc' : 'asc'])) ?>" class="text-decoration-none text-white">
                                创建时间 <i class="fas <?= $sort_column === 'created_at' ? ($sort_order === 'ASC' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort' ?>"></i></a></th>
                        <th><a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'view_count', 'order' => ($sort_column === 'view_count' && $sort_order === 'ASC') ? 'desc' : 'asc'])) ?>" class="text-decoration-none text-white">
                                访问量 <i class="fas <?= $sort_column === 'view_count' ? ($sort_order === 'ASC' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort' ?>"></i></a></th>
                        <th>封面图片</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($articles as $article): ?>
                        <tr>
                            <td><input type="checkbox" name="selected_articles[]" value="<?= $article['id'] ?>" class="form-check-input article-checkbox"></td>
                            <td>
                                <?php $status = $article['status'] ?? 'draft'; ?>
                                <span class="badge bg-<?= $statusBadge[$status]['color'] ?> rounded-pill d-inline-flex align-items-center" data-bs-toggle="tooltip" title="最后修改: <?= $article['updated_at'] ?>">
                                    <i class="fas <?= $statusBadge[$status]['icon'] ?> me-1"></i>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($article['title']) ?></td>
                            <td><?= htmlspecialchars($article['author']) ?></td>
                            <td><?= htmlspecialchars($article['created_at']) ?></td>
                            <td><?= htmlspecialchars($article['view_count']) ?></td>
                            <td>
                                <?php if (!empty($article['cover_image'])): ?>
                                    <img src="<?= ARTICLE_URL . "/" . htmlspecialchars($article['cover_image']) ?>" alt="封面" style="width: 80px; height: 50px; object-fit: cover; border-radius: 5px;">
                                <?php else: ?>
                                    <img src="<?= ARTICLE_URL ?>/default_cover_image.jpg" alt="封面" style="width: 80px; height: 50px; object-fit: cover; border-radius: 5px;">
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="view.php?id=<?= $article['id'] ?>&category_id=<?= $category_id ?>" class="btn btn-view btn-sm"><i class="fas fa-eye"></i> 查看</a>
                                <a href="article_form.php?id=<?= $article['id'] ?>&category_id=<?= $category_id ?>" class="btn btn-edit btn-sm"><i class="fas fa-edit"></i> 编辑</a>
                                <button class="btn btn-delete btn-sm" onclick="event.preventDefault(); openDeleteModal2('<?= addslashes($article['title']) ?>', '<?= $article['id'] ?>', '<?= $category_id ?>')">
                                    <i class="fas fa-trash"></i> 删除
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>

        <!-- 分页 -->
        <div class="d-flex justify-content-between align-items-center mt-3">
            <div>共 <?= $total ?> 条记录，第 <?= $page ?>/<?= $total_pages ?> 页</div>
            <div class="d-flex align-items-center gap-2">
                <?php if ($page > 1): ?>
                    <a class="btn btn-sm btn-secondary" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">上一页</a>
                <?php endif; ?>

                <select class="form-select form-select-sm" style="width:auto;" onchange="location.href='?<?= http_build_query($_GET) ?>&page=' + this.value">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <option value="<?= $i ?>" <?= $i == $page ? 'selected' : '' ?>>第 <?= $i ?> 页</option>
                    <?php endfor; ?>
                </select>

                <?php if ($page < $total_pages): ?>
                    <a class="btn btn-sm btn-secondary" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">下一页</a>
                <?php endif; ?>
            </div>
        </div>

        <?php require_once INCLUDE_PATH . '/delete_modal.php'; ?>
    </div>

    <script src="/assets/js/bootstrap.bundle.min.js"></script>
    <script src="<?= JS_URL ?>/admin.js"></script>
</body>

</html>
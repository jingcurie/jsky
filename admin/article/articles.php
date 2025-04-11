<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../includes/config.php';
require_once INCLUDE_PATH . '/db.php';
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

// 单个删除文章
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $article = getById($conn, "articles", "id", $_POST['delete_id']);
    $success = deleteArticleWithImages($conn, $_POST['delete_id']);
    if ($success) {
        log_operation($conn, $_SESSION['user_id'], $_SESSION['username'], '删除', $article["category_id"].'文章', $_POST['delete_id'], $article["title"]);
        redirect("articles.php?category_id=" . $_POST['category_id']);
    } else {
        $error = "删除文章失败";
    }
}

// 批量删除处理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_delete'])) {
    if (!empty($_POST['selected_articles'])) {
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($_POST['selected_articles'] as $articleId) {
            $article = getById($conn, "articles", "id", $articleId);
            $success = deleteArticleWithImages($conn, $articleId);
            if ($success) {
                log_operation($conn, $_SESSION['user_id'], $_SESSION['username'], '删除', $article["category_id"].'文章', $article["id"], $article["title"]);
                $successCount++;
            } else {
                $errorCount++;
            }
        }
        
        $_SESSION['success_message'] = "成功删除 $successCount 篇文章";
        redirect("articles.php?category_id=$category_id");
        
    } else {
        $error = "请先选择要删除的文章";
    }
}

// [保持原有分页、排序、状态筛选代码不变]
$records_per_page = 20;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$sort_column = isset($_GET['sort']) && in_array($_GET['sort'], ['title', 'author', 'created_at', 'updated_at', 'view_count', 'status']) ? $_GET['sort'] : 'created_at';
$sort_order = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'ASC' : 'DESC';

$statusBadge = [
    'draft' => ['color' => 'secondary', 'icon' => 'fa-edit', 'text' => '草稿'],
    'published' => ['color' => 'success', 'icon' => 'fa-check-circle', 'text' => '发布'],
    'archived' => ['color' => 'dark', 'icon' => 'fa-archive', 'text' => '归档']
];

$statusFilter = $_GET['status'] ?? '';
$where = "WHERE category_id = ?";
$params = [$category_id];

if ($statusFilter && array_key_exists($statusFilter, $statusBadge)) {
    $where .= " AND status = ?";
    $params[] = $statusFilter;
}

$total_records = query($conn, "SELECT COUNT(*) FROM articles $where", $params)[0]['COUNT(*)'];
$total_pages = max(1, ceil($total_records / $records_per_page));

$offset = ($current_page - 1) * $records_per_page;
$articles = query(
    $conn,
    "SELECT * FROM articles 
     $where 
     ORDER BY $sort_column $sort_order 
     LIMIT $records_per_page OFFSET $offset",
    $params
);
?>

<!DOCTYPE html>
<html lang="zh">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>文章管理</title>
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"> -->
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
    </style>
</head>

<body>
    <div class="container-fluid">
        <h2><i class="fas fa-newspaper"></i> <?= htmlspecialchars($category_name) ?> 文章管理</h2>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success_message'];
            unset($_SESSION['success_message']); ?></div>
        <?php endif; ?>

        <div class="d-flex justify-content-between mb-3">
            <a href="article_form.php?category_id=<?= $category_id ?>" class="btn btn-create">
                <i class="fas fa-plus"></i> 新建文章
            </a>

            <button type="button" id="bulkDeleteBtn" class="btn btn-danger" disabled data-bs-toggle="modal"
                data-bs-target="#bulkDeleteModal">
                <i class="fas fa-trash"></i> 批量删除 (<span id="selectedCount">0</span>)
            </button>
        </div>

        <div class="btn-group btn-group-sm mb-3" role="group">
            <a href="?category_id=<?= $category_id ?>"
                class="btn btn-outline-secondary <?= !isset($_GET['status']) ? 'active' : '' ?>">
                全部
            </a>
            <?php foreach ($statusBadge as $key => $config): ?>
                <a href="?category_id=<?= $category_id ?>&status=<?= $key ?>"
                    class="btn btn-outline-<?= $config['color'] ?> <?= ($_GET['status'] ?? '') === $key ? 'active' : '' ?>">
                    <i class="fas <?= $config['icon'] ?>"></i> <?= $config['text'] ?>
                </a>
            <?php endforeach; ?>
        </div>
        <!-- <input type="text" name="keyword" class="form-control" placeholder="搜索标题或作者" value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>"> -->
        <form id="bulkActionForm" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()) ?>">
            <table class="table table-bordered table-hover text-center">
                <thead>
                    <tr>
                        <th class="checkbox-cell">
                            <input type="checkbox" id="selectAll" class="form-check-input">
                        </th>
                        <th><a href="?category_id=<?= $category_id ?>&sort=status&order=<?= $sort_column === 'status' && $sort_order === 'ASC' ? 'desc' : 'asc' ?>"
                                class="text-light text-decoration-none">
                                状态 <i
                                    class="fas <?= $sort_column === 'status' ? ($sort_order === 'ASC' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort' ?>"></i>
                            </a></th>
                        <th><a href="?category_id=<?= $category_id ?>&sort=title&order=<?= $sort_column === 'title' && $sort_order === 'ASC' ? 'desc' : 'asc' ?>"
                                class="text-light text-decoration-none">标题 <i
                                    class="fas <?= $sort_column === 'title' ? ($sort_order === 'ASC' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort' ?>"></i></a>
                        </th>
                        <th><a href="?category_id=<?= $category_id ?>&sort=author&order=<?= $sort_column === 'author' && $sort_order === 'ASC' ? 'desc' : 'asc' ?>"
                                class="text-light text-decoration-none">作者 <i
                                    class="fas <?= $sort_column === 'author' ? ($sort_order === 'ASC' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort' ?>"></i></a>
                        </th>
                        <th><a href="?category_id=<?= $category_id ?>&sort=created_at&order=<?= $sort_column === 'created_at' && $sort_order === 'ASC' ? 'desc' : 'asc' ?>"
                                class="text-light text-decoration-none">创建时间 <i
                                    class="fas <?= $sort_column === 'created_at' ? ($sort_order === 'ASC' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort' ?>"></i></a>
                        </th>
                        <!-- <th><a href="?category_id=<?= $category_id ?>&sort=updated_at&order=<?= $sort_column === 'updated_at' && $sort_order === 'ASC' ? 'desc' : 'asc' ?>"
                                class="text-light text-decoration-none">修改时间 <i
                                    class="fas <?= $sort_column === 'updated_at' ? ($sort_order === 'ASC' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort' ?>"></i></a>
                        </th> -->
                        <th><a href="?category_id=<?= $category_id ?>&sort=view_count&order=<?= $sort_column === 'view_count' && $sort_order === 'ASC' ? 'desc' : 'asc' ?>"
                                class="text-light text-decoration-none">访问量 <i
                                    class="fas <?= $sort_column === 'view_count' ? ($sort_order === 'ASC' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort' ?>"></i></a>
                        </th>
                        <th>封面图片</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($articles as $article): ?>
                        <tr>
                            <td class="checkbox-cell">
                                <input type="checkbox" name="selected_articles[]" value="<?= $article['id'] ?>"
                                    class="form-check-input article-checkbox">
                            </td>
                            <td>
                                <?php $status = $article['status'] ?? 'draft'; ?>
                                <span
                                    class="badge bg-<?= $statusBadge[$status]['color'] ?> rounded-pill d-inline-flex align-items-center"
                                    data-bs-toggle="tooltip" title="最后修改: <?= $article['updated_at'] ?>">
                                    <i class="fas <?= $statusBadge[$status]['icon'] ?> me-1"></i>
                                    <!-- <?= $statusBadge[$status]['text'] ?> -->
                                </span>
                            </td>
                            <td><?= htmlspecialchars($article['title']) ?></td>
                            <td><?= htmlspecialchars($article['author']) ?></td>
                            <td><?= htmlspecialchars($article['created_at']) ?></td>
                            <!-- <td><?= htmlspecialchars($article['updated_at']) ?></td> -->
                            <td><?= htmlspecialchars($article['view_count']) ?></td>
                            <td>
                                <?php if (!empty($article['cover_image'])): ?>
                                    <img src="<?php echo ARTICLE_URL . "/" .htmlspecialchars($article['cover_image']) ?>" alt="封面"
                                        style="width: 80px; height: 50px; object-fit: cover; border-radius: 5px;">
                                <?php else: ?>
                                    <img src="<?php echo ARTICLE_URL ?>/default_cover_image.jpg" alt="封面"
                                        style="width: 80px; height: 50px; object-fit: cover; border-radius: 5px;">
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="view.php?id=<?= $article['id'] ?>&category_id=<?= $category_id ?>"
                                    class="btn btn-view btn-sm"><i class="fas fa-eye"></i> 查看</a>
                                <a href="article_form.php?id=<?= $article['id'] ?>&category_id=<?= $category_id ?>"
                                    class="btn btn-edit btn-sm"><i class="fas fa-edit"></i> 编辑</a>


                                <button class="btn btn-delete btn-sm"
                                    onclick="event.preventDefault(); openDeleteModal2('<?= htmlspecialchars(addslashes($article['title'])) ?>', '<?= $article['id'] ?>', '<?= $category_id ?>')">
                                    <i class="fas fa-trash"></i> 删除
                                </button>


                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="pagination-container">
                <?php
                $pagination_params = [
                    'category_id' => $category_id,
                    'sort' => $sort_column,
                    'order' => $sort_order,
                    'status' => $statusFilter
                ];
                ?>
                <a href="?<?= http_build_query(array_merge($pagination_params, ['page' => max(1, $current_page - 1)])) ?>"
                    class="btn btn-secondary <?= $current_page == 1 ? 'disabled' : '' ?>">
                    <i class="fas fa-arrow-left"></i> 上一页
                </a>
                <select class="form-select w-auto" id="pageSelect"
                    onchange="location.href='?<?= http_build_query($pagination_params) ?>&page='+this.value">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <option value="<?= $i ?>" <?= $i == $current_page ? 'selected' : '' ?>>第 <?= $i ?> 页</option>
                    <?php endfor; ?>
                </select>
                <a href="?<?= http_build_query(array_merge($pagination_params, ['page' => min($total_pages, $current_page + 1)])) ?>"
                    class="btn btn-secondary <?= $current_page == $total_pages ? 'disabled' : '' ?>">
                    下一页 <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </form>



        <!-- 引入通用删除模态框 -->
        <?php require_once INCLUDE_PATH . '/delete_modal.php'; ?>

        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?= JS_URL ?>/admin.js"></script>

    </div>
</body>

</html>
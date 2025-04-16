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
    redirect('/admin/login.php');
}

// 软删除分类
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $category_id = intval($_POST['delete_id']);
    $option = $_POST['delete_option'] ?? '';
    $target_id = intval($_POST['target_category_id'] ?? 0);

    $category = getById($conn, 'categories', 'id', $category_id);
    if (!$category || $category['is_deleted']) {
        $error = "分类不存在或已被删除。";
    } else {
        // 软删除当前分类
        update($conn, 'categories', 'id', $category_id, [
            'is_deleted' => 1,
            'deleted_at' => date('Y-m-d H:i:s'),
            'deleted_by' => $_SESSION['user_id']
        ]);

        if ($option === 'delete_all') {
            // 删除该分类下所有文章（软删）
            $articles = query($conn, "SELECT id, title FROM articles WHERE category_id = ? AND is_deleted = 0", [$category_id]);
            foreach ($articles as $article) {
                update($conn, 'articles', 'id', $article['id'], [
                    'is_deleted' => 1,
                    'deleted_at' => date('Y-m-d H:i:s'),
                    'deleted_by' => $_SESSION['user_id']
                ]);
                log_operation($conn, $_SESSION['user_id'], $_SESSION['username'], '软删除', '文章管理', $article['id'], "[分类被删] " . $article['title']);
            }
            $msg = "分类及其下共 " . count($articles) . " 篇文章已删除";
        } elseif ($option === 'migrate' && $target_id) {
            // 迁移文章
            $affected = execute($conn, "UPDATE articles SET category_id = ? WHERE category_id = ? AND is_deleted = 0", [$target_id, $category_id]);
            log_operation($conn, $_SESSION['user_id'], $_SESSION['username'], '迁移文章', '分类管理', $category_id, "将文章迁移至分类 ID: {$target_id}");
            $msg = "分类删除成功，文章已迁移至其他分类";
        } else {
            $msg = "分类删除成功";
        }

        log_operation($conn, $_SESSION['user_id'], $_SESSION['username'], '软删除', '分类管理', $category_id, "分类名称：" . $category['name']);
        $_SESSION['success_message'] = $msg;
        redirect('categories.php');
    }
}


// 查询未删除的分类
$categories = query($conn, "SELECT id, name, description FROM categories WHERE is_deleted = 0 ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="zh">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>分类管理</title>
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/all.min.css">
    <link href="<?= CSS_URL ?>/admin_style.css" rel="stylesheet">
</head>

<body>
    <div class="container">
        <h2><i class="fas fa-folder"></i> 文章分类管理</h2>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <a href="category_form.php" class="btn btn-create"><i class="fas fa-plus"></i> 新增分类</a>

        <table class="table table-bordered table-hover mt-3">
            <thead>
                <tr>
                    <th>序号</th>
                    <th>分类名称</th>
                    <th>分类描述</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php $count = 0; ?>
                <?php foreach ($categories as $category): $count++; ?>
                    <tr>
                        <td><?= $count ?></td>
                        <td><?= htmlspecialchars($category['name']) ?></td>
                        <td><?= htmlspecialchars($category['description']) ?></td>
                        <td>
                            <a href="category_form.php?id=<?= $category['id'] ?>" class="btn btn-edit btn-sm">
                                <i class="fas fa-edit"></i> 编辑
                            </a>
                            <!-- <button class="btn btn-delete btn-sm" onclick="openDeleteModal('<?= htmlspecialchars($category['name']) ?>', <?= $category['id'] ?>)">
                                <i class="fas fa-trash"></i> 删除
                            </button> -->
                            <button class="btn btn-delete btn-sm" onclick="openCategoryDeleteModal(<?= $category['id'] ?>)">
                                <i class="fas fa-trash"></i> 删除
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- 分类删除专用模态框 -->
    <div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-labelledby="deleteCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="post" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()) ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-trash text-danger"></i> 删除分类</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="delete_id" id="deleteCategoryId">

                    <p class="mb-2">该分类下可能仍有文章，您希望如何处理？</p>

                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="delete_option" value="delete_all" id="optionDeleteAll" checked>
                        <label class="form-check-label" for="optionDeleteAll">
                            删除分类及其下所有文章（软删除）
                        </label>
                    </div>

                    <div class="form-check mt-2">
                        <input class="form-check-input" type="radio" name="delete_option" value="migrate" id="optionMigrate">
                        <label class="form-check-label" for="optionMigrate">
                            将文章迁移到其他分类：
                        </label>
                    </div>

                    <select name="target_category_id" class="form-select mt-2" id="targetCategorySelect">
                        <option value="">-- 选择分类 --</option>
                        <?php
                        $all_categories = query($conn, "SELECT id, name FROM categories WHERE is_deleted = 0");
                        foreach ($all_categories as $cat) {
                            echo "<option value=\"{$cat['id']}\">" . htmlspecialchars($cat['name']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-danger"><i class="fas fa-check"></i> 确认删除</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                </div>
            </form>
        </div>
    </div>


    <script src="/assets/js/bootstrap.bundle.min.js"></script>
    <script>
        function openCategoryDeleteModal(categoryId) {
            document.getElementById('deleteCategoryId').value = categoryId;
            const modal = new bootstrap.Modal(document.getElementById('deleteCategoryModal'));
            modal.show();
        }
    </script>
</body>

</html>
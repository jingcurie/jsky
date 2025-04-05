<?php
require_once __DIR__ . '/../../includes/config.php';
require INCLUDE_PATH . '/db.php';
require INCLUDE_PATH . '/auth.php';
require INCLUDE_PATH . '/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// 删除分类
if (isset($_GET['delete_id'])) {
    if (delete($conn, 'categories', 'id', $_GET['delete_id'])) {
        redirect('categories.php');
    } else {
        $error = "Error deleting category.";
    }
}

// 查询所有分类
$categories = getAll($conn, 'categories');
?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>分类管理</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="<?= CSS_URL ?>/admin_style.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h2><i class="fas fa-folder"></i> 文章分类管理</h2>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <a href="category_form.php" class="btn btn-create"><i class="fas fa-plus"></i> 新增分类</a>

        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>分类名称</th>
                    <th>分类描述</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($category['id']); ?></td>
                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                        <td><?php echo htmlspecialchars($category['description']); ?></td>
                        <td>
                            <a href="category_form.php?id=<?php echo $category['id']; ?>" class="btn btn-edit btn-sm"><i class="fas fa-edit"></i> 编辑</a>
                            <button class="btn btn-delete btn-sm" onclick="openDeleteModal('<?php echo htmlspecialchars($category['name']); ?>', 'categories.php?delete_id=<?= $category['id'] ?>')">
                                <i class="fas fa-trash"></i> 删除
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- 引入通用删除模态框 -->
    <?php require_once INCLUDE_PATH . '/delete_modal.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= JS_URL ?>/admin.js"></script>
</body>
</html>
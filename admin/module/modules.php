<?php
require_once __DIR__ . '/../../includes/config.php';
require INCLUDE_PATH . '/db.php';
require INCLUDE_PATH . '/auth.php';
require INCLUDE_PATH . '/functions.php';
if (!isLoggedIn()) {
    redirect('login.php');
}

// 删除模块
if (isset($_GET['delete_id'])) {
    $module = getById($conn, "modules", "module_id", $_GET['delete_id']);
    log_operation($conn, $_SESSION['user_id'], $_SESSION['username'], '删除', '模块管理', $_GET['delete_id'], $module["module_name"]);
    $success= delete($conn, 'modules', 'module_id', $_GET['delete_id']);
    if ($success) {
        redirect('modules.php');
    } else {
        $error = "Error deleting module.";
    }
}

// 查询所有模块，并按 module_order 排序
$modules = query($conn, "SELECT * FROM modules ORDER BY module_order ASC");
?>

<!DOCTYPE html>
<html lang="zh">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"> -->
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/all.min.css">
    <link href="<?= CSS_URL ?>/admin_style.css" rel="stylesheet">
</head>

<body>
    <div class="container">
        <h2><i class="fas fa-cubes"></i> 模块管理</h2>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <a href="module_form.php" class="btn btn-create"><i class="fas fa-plus"></i> 添加新模块</a>

        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>序号</th>
                    <th>图标</th>
                    <th>模块名称</th>
                    <th>描述</th>
                    <th>URL</th>
                    <th>排序</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                    <?php 
                    $count = 0;
                    foreach ($modules as $module): 
                    $count++;?>
                    <tr>
                        <td><?php echo $count; ?></td>
                        <td class="module-icon"><i class="fas <?php echo htmlspecialchars($module['module_icon']); ?>"></i>
                        </td>
                        <td><?php echo htmlspecialchars($module['module_name']); ?></td>
                        <td><?php echo htmlspecialchars($module['description']); ?></td>
                        <td><?php echo htmlspecialchars($module['module_url']); ?></td>
                        <td><?php echo htmlspecialchars($module['module_order']); ?></td>
                        <td>
                            <a href="module_form.php?module_id=<?php echo $module['module_id']; ?>"
                                class="btn btn-edit btn-sm"><i class="fas fa-edit"></i> 编辑</a>
                            <button class="btn btn-delete btn-sm"
                                onclick="openDeleteModal('<?php echo htmlspecialchars($module['module_name']); ?>', 'modules.php?delete_id=<?= $module['module_id'] ?>')">
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
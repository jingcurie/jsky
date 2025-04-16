<?php
require_once __DIR__ . '/../../includes/config.php';
require INCLUDE_PATH . '/db.php';
require_once INCLUDE_PATH . '/check_ip_whitelist.php';
require INCLUDE_PATH . '/auth.php';
require_once INCLUDE_PATH . '/functions.php';

if (!isLoggedIn()) {
    redirect('/admin/login.php');
}

// 软删除模块
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $module_id = $_POST['delete_id'];
    $module = getById($conn, "modules", "module_id", $module_id);

    if ($module['is_deleted'] == 1) {
        $error = "该模块已被删除。";
    } else {
        $success = update($conn, 'modules', 'module_id', $module_id, [
            'is_deleted' => 1,
            'deleted_at' => date('Y-m-d H:i:s'),
            'deleted_by' => $_SESSION['user_id']
        ]);

        log_operation($conn, $_SESSION['user_id'], $_SESSION['username'], '软删除', '模块管理', $module_id, $module["module_name"]);

        if ($success) {
            redirect('modules.php');
        } else {
            $error = "删除模块失败，请重试。";
        }
    }
}

// 查询未被软删除的模块
$modules = query($conn, "SELECT * FROM modules WHERE is_deleted = 0 ORDER BY module_order ASC");
?>

<!DOCTYPE html>
<html lang="zh">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>模块管理</title>
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/all.min.css">
    <link href="<?= CSS_URL ?>/admin_style.css" rel="stylesheet">
</head>

<body>
    <div class="container">
        <h2><i class="fas fa-cubes"></i> 模块管理</h2>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
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
                <?php $count = 0; ?>
                <?php foreach ($modules as $module): $count++; ?>
                    <tr>
                        <td><?= $count ?></td>
                        <td class="module-icon"><i class="fas <?= htmlspecialchars($module['module_icon']) ?>"></i></td>
                        <td><?= htmlspecialchars($module['module_name']) ?></td>
                        <td><?= htmlspecialchars($module['description']) ?></td>
                        <td><?= htmlspecialchars($module['module_url']) ?></td>
                        <td><?= htmlspecialchars($module['module_order']) ?></td>
                        <td>
                            <a href="module_form.php?module_id=<?= $module['module_id'] ?>" class="btn btn-edit btn-sm">
                                <i class="fas fa-edit"></i> 编辑
                            </a>
                            <button class="btn btn-delete btn-sm"
                                onclick="openDeleteModal('<?= htmlspecialchars($module['module_name']) ?>', <?= $module['module_id'] ?>)">
                                <i class="fas fa-trash"></i> 删除
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php require_once INCLUDE_PATH . '/delete_modal.php'; ?>
    <script src="/assets/js/bootstrap.bundle.min.js"></script>
    <script src="<?= JS_URL ?>/admin.js"></script>
</body>
</html>

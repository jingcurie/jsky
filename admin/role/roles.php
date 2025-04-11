<?php
require_once __DIR__ . '/../../includes/config.php';
require INCLUDE_PATH . '/db.php';
require INCLUDE_PATH . '/auth.php';
require INCLUDE_PATH . '/functions.php';

if (!isLoggedIn()) {
    redirect('/admin/login.php');
}

// 删除角色操作
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    // 查询角色名称
    $roleToDelete = getById($conn, 'roles', 'role_id', $_POST['delete_id']);
    if ($roleToDelete && !in_array(strtolower($roleToDelete['role_name']), ['admin', 'editor'])) {
        $success = delete($conn, 'roles', 'role_id', $_POST['delete_id']);
        if ($success) {
            log_operation($conn, $_SESSION['user_id'], $_SESSION['username'], '删除', '角色管理', $_POST['delete_id'], $roleToDelete["role_name"]);
            redirect('roles.php');
        } else {
            $error = '删除失败，请重试';
        }
    } else {
        $error = '该角色不允许删除';
    }
}

// 获取所有角色
$roles = getAll($conn, 'roles');
?>

<!DOCTYPE html>
<html lang="zh">

<head>
    <meta charset="UTF-8">
    <title>角色管理</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="<?= CSS_URL ?>/admin_style.css" rel="stylesheet">
</head>

<body>
    <div class="container">
        <h2 class="my-4"><i class="fas fa-user-shield"></i> 角色管理</h2>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="mb-3 text-start">
            <a href="role_form.php" class="btn btn-create">
                <i class="fas fa-plus"></i> 新增角色
            </a>
        </div>

        <table class="table table-bordered table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>序号</th>
                    <th>角色名称</th>
                    <th>描述</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $count = 0;
                foreach ($roles as $role):
                    $roleNameLower = strtolower($role['role_name']);
                    $canDelete = !in_array($roleNameLower, ['admin', 'editor']);
                    $count++;
                ?>
                    <tr>
                        <td><?= $count ?></td>
                        <td><?= htmlspecialchars($role['role_name']) ?></td>
                        <td><?= htmlspecialchars($role['role_desc']) ?></td>
                        <td style="text-align:left;">
                            <a href="role_form.php?role_id=<?= $role['role_id'] ?>" class="btn btn-sm btn-edit">
                                <i class="fas fa-edit"></i> 编辑
                            </a>
                            <a href="role_modules.php?role_id=<?= $role['role_id'] ?>" class="btn btn-sm btn-task">
                                <i class="fas fa-tasks"></i> 分配模块
                            </a>

                            <!-- <button class="btn btn-sm btn-delete <?= $canDelete ? '' : 'disabled' ?>"
                                <?= $canDelete ? "onclick=\"openDeleteModal('" . htmlspecialchars($role['role_name']) . "', 'roles.php?delete_id={$role['role_id']}')\"" : '' ?>
                                title="<?= $canDelete ? '删除该角色' : '此角色不可删除' ?>">
                                <i class="fas fa-trash"></i> 删除
                            </button> -->

                            <button class="btn btn-sm btn-delete <?= $canDelete ? '' : 'disabled' ?>"
                                <?= $canDelete ? "onclick=\"openDeleteModal('" . htmlspecialchars($role['role_name']) . "', {$role['role_id']})\"" : '' ?>
                                title="<?= $canDelete ? '删除该角色' : '此角色不可删除' ?>">
                                <i class="fas fa-trash"></i> 删除
                            </button>

                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php require_once INCLUDE_PATH . '/delete_modal.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= JS_URL ?>/admin.js"></script>
</body>

</html>
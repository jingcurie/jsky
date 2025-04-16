<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../includes/config.php';
require INCLUDE_PATH . '/db.php';
require_once INCLUDE_PATH . '/check_ip_whitelist.php';
require INCLUDE_PATH . '/auth.php';
require_once INCLUDE_PATH . '/functions.php';

if (!isLoggedIn()) {
    redirect('/admin/login.php');
}

// 软删除角色操作
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $role_id = $_POST['delete_id'];
    $roleToDelete = getById($conn, 'roles', 'role_id', $role_id);

    if ($roleToDelete && !in_array(strtolower($roleToDelete['role_name']), ['admin', 'editor'])) {

        // 检查是否有关联用户
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE role_id = :role_id AND is_deleted = 0");
        $stmt->execute(['role_id' => $role_id]);
        $userCount = $stmt->fetchColumn();

        if ($userCount > 0) {
            echo "<script>alert('该角色（". $roleToDelete['role_name'] . "）已被用户使用，不能直接删除，请先更换这些用户的角色'); window.location.href = 'roles.php';</script>";
            exit;
        }

        // 执行软删除
        $success = update($conn, 'roles', 'role_id', $role_id, [
            'is_deleted' => 1,
            'deleted_at' => date('Y-m-d H:i:s'),
            'deleted_by' => $_SESSION['user_id']
        ]);

        if ($success) {
            log_operation($conn, $_SESSION['user_id'], $_SESSION['username'], '软删除', '角色管理', $role_id, $roleToDelete["role_name"]);
            redirect('roles.php');
        } else {
            $error = '删除失败，请重试';
        }
    } else {
        $error = '该角色不允许删除';
    }
}

// 获取所有未被软删除的角色
$roles = query($conn, "SELECT * FROM roles WHERE is_deleted = 0 ORDER BY role_id ASC");
?>

<!DOCTYPE html>
<html lang="zh">

<head>
    <meta charset="UTF-8">
    <title>角色管理</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/all.min.css" rel="stylesheet">
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
                <?php $count = 0; ?>
                <?php foreach ($roles as $role): $count++; ?>
                    <?php
                    $roleNameLower = strtolower($role['role_name']);
                    $canDelete = !in_array($roleNameLower, ['admin', 'editor']);
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
    <script src="/assets/js/bootstrap.bundle.min.js"></script>
    <script src="<?= JS_URL ?>/admin.js"></script>
</body>
</html>

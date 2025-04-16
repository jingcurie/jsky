<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../includes/config.php';
require INCLUDE_PATH . '/db.php';
require_once INCLUDE_PATH . '/check_ip_whitelist.php';
require INCLUDE_PATH . '/auth.php';
require INCLUDE_PATH . '/functions.php';

csrfProtect();

if (!isLoggedIn()) {
    redirect('/admin/login.php');
}

// 软删除用户
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $user = getById($conn, "users", "user_id", $_POST['delete_id']);

    if ($user['username'] === 'admin') {
        $error = "超级管理员账户不允许删除。";
    } elseif ($user['is_deleted'] == 1) {
        $error = "该用户已被删除。";
    } else {
        $success = update($conn, 'users', 'user_id', $_POST['delete_id'], [
            'is_deleted' => 1,
            'deleted_at' => date('Y-m-d H:i:s'),
            'deleted_by' => $_SESSION['user_id']
        ]);

        log_operation(
            $conn,
            $_SESSION['user_id'],
            $_SESSION['username'],
            '软删除',
            '用户管理',
            $_POST['delete_id'],
            "软删除用户：" . $user['username']
        );

        if ($success) {
            redirect('users.php');
        } else {
            $error = "删除失败，请重试。";
        }
    }
}

// 查询所有未被删除的用户
$users = query($conn, "
    SELECT users.user_id, users.username, users.email, roles.role_name
    FROM users
    LEFT JOIN roles ON users.role_id = roles.role_id
    WHERE users.is_deleted = 0
    ORDER BY users.user_id DESC
");
?>

<!DOCTYPE html>
<html lang="zh">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户管理</title>
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/all.min.css">
    <link href="<?= CSS_URL ?>/admin_style.css" rel="stylesheet">
</head>

<body>
    <div class="container">
        <h2><i class="fas fa-users"></i> 用户管理</h2>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <a href="user_form.php" class="btn btn-create"><i class="fas fa-user-plus"></i> 新增用户</a>

        <table class="table table-bordered table-hover mt-3">
            <thead>
                <tr>
                    <th>序号</th>
                    <th>用户名</th>
                    <th>邮箱</th>
                    <th>角色</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php $count = 0; ?>
                <?php foreach ($users as $user): $count++; ?>
                    <tr>
                        <td><?= $count ?></td>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= htmlspecialchars($user['role_name'] ?: '未分配') ?></td>
                        <td>
                            <a href="user_form.php?user_id=<?= $user['user_id'] ?>" class="btn btn-edit btn-sm">
                                <i class="fas fa-edit"></i> 编辑
                            </a>
                            <button class="btn btn-sm btn-delete <?= $user['username'] !== 'admin' ? '' : 'disabled' ?>"
                                <?= $user['username'] !== 'admin' ? "onclick=\"openDeleteModal('" . htmlspecialchars($user['username']) . "', {$user['user_id']})\"" : '' ?>>
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

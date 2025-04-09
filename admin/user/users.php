<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../includes/config.php';
require INCLUDE_PATH . '/db.php';
require INCLUDE_PATH . '/auth.php';
require INCLUDE_PATH . '/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// 删除用户
if (isset($_GET['delete_id'])) {
    $user = getById($conn, "users", "user_id", $_GET['delete_id']);
    log_operation($conn, $_SESSION['user_id'], $_SESSION['username'], '删除', '用户管理', $_GET['delete_id'], $user["username"]);
    $success = delete($conn, 'users', 'user_id', $_GET['delete_id']);
    if ($success) {
        redirect('users.php');
    } else {
        $error = "Error deleting user.";
    }
}

// 查询所有用户
$users = query($conn, "SELECT users.user_id, users.username, users.email, roles.role_name FROM users LEFT JOIN roles ON users.role_id = roles.role_id");
?>

<!DOCTYPE html>
<html lang="zh">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户管理</title>
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"> -->
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

        <table class="table table-bordered table-hover">
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
                <?php
                $count = 0;
                foreach ($users as $user):
                    $count++; ?>
                    <tr>
                        <td><?php echo $count ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['role_name'] ?: '未分配'); ?></td>
                        <td>
                            <a href="user_form.php?user_id=<?php echo $user['user_id']; ?>" class="btn btn-edit btn-sm"><i class="fas fa-edit"></i>编辑</a>
                            <button class="btn btn-delete btn-sm" onclick="openDeleteModal('<?php echo htmlspecialchars($user['username']); ?>', 'users.php?delete_id=<?= $user['user_id'] ?>')">
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
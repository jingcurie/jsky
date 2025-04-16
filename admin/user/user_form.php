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

// 初始化变量
$user_id = $_GET['user_id'] ?? null;
$username = '';
$email = '';
$role_id = '';
$password = '';
$generated_password = bin2hex(random_bytes(4));
$error = '';

// 查询所有角色
$roles = query($conn, "SELECT * FROM roles WHERE is_deleted = 0");

// 获取原用户信息（编辑模式）
if ($user_id) {
    $user = getById($conn, 'users', 'user_id', $user_id);
    if ($user && $user['is_deleted'] == 0) {
        $username = $user['username'];
        $email = $user['email'];
        $role_id = $user['role_id'];
        $password = $user['password_hash'];
        $generated_password = '';
    } else {
        $error = "用户不存在或已被删除";
    }
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role_id = trim($_POST['role_id']);
    $password = trim($_POST['password']) ?: $generated_password;

    if (empty($username) || empty($email) || (!$user_id && empty($password))) {
        $error = "所有字段都是必填的。";
    } else {
        if (!$user_id) {
            // 新建时检查是否冲突
            $exists = query($conn, "SELECT COUNT(*) as cnt FROM users WHERE (username = ? OR email = ?) AND is_deleted = 0", [$username, $email])[0]['cnt'];
            if ($exists > 0) {
                $error = "用户名或邮箱已被使用，请更换。";
            }

            if (empty($error)) {
                $deleted = query($conn, "SELECT * FROM users WHERE (username = ? OR email = ?) AND is_deleted = 1", [$username, $email]);
                if ($deleted) {
                    $error = "该用户名或邮箱已存在于回收站中，请联系管理员恢复该账户。";
                }
            }
        } else {
            // 编辑时判断是否与其他用户冲突（排除自己）
            $exists = query($conn, "SELECT COUNT(*) as cnt FROM users WHERE user_id != ? AND (username = ? OR email = ?) AND is_deleted = 0", [$user_id, $username, $email])[0]['cnt'];
            if ($exists > 0) {
                $error = "该用户名或邮箱已被其他用户占用，请更换。";
            }
        }
    }

    if (empty($error)) {
        if ($user_id) {
            // 更新
            if (!empty($password)) {
                $sql = "UPDATE users SET username = ?, email = ?, role_id = ?, password_hash = ? WHERE user_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$username, $email, $role_id, password_hash($password, PASSWORD_DEFAULT), $user_id]);
            } else {
                $sql = "UPDATE users SET username = ?, email = ?, role_id = ? WHERE user_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$username, $email, $role_id, $user_id]);
            }
            log_operation($conn, $_SESSION['user_id'], $_SESSION['username'], '更新', '用户管理', $user_id, $username);
        } else {
            // 新建
            $sql = "INSERT INTO users (username, email, password_hash, role_id, must_change_password) VALUES (?, ?, ?, ?, 1)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), $role_id]);
            log_operation($conn, $_SESSION['user_id'], $_SESSION['username'], '创建', '用户管理', null, $username);
        }
        header("Location: users.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="zh">

<head>
    <meta charset="UTF-8">
    <title><?php echo $user_id ? '编辑角色' : '新增用户'; ?></title>
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"> -->
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .form-container {
            max-width: 500px;
            margin: 50px auto;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .form-container h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .btn {
            border-radius: 5px;
        }
    </style>
</head>

<body>

    <div class="form-container">
        <h2><?php echo $user_id ? '编辑角色' : '新增用户'; ?></h2>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()) ?>">
            <div class="mb-3">
                <label for="username" class="form-label"><i class="fas fa-user"></i> 用户名</label>
                <input type="text" class="form-control" id="username" name="username"
                    value="<?php echo htmlspecialchars($username); ?>" required
                    <?php echo ($username === 'admin') ? 'readonly' : ''; ?>>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label"><i class="fas fa-envelope"></i> 邮箱</label>
                <input type="email" class="form-control" id="email" name="email"
                    value="<?php echo htmlspecialchars($email); ?>" required
                    <?php echo ($username === 'admin') ? 'readonly' : ''; ?>>
            </div>


            <div class="mb-3">
                <label for="password" class="form-label"><i class="fas fa-lock"></i> 密码</label>
                <input type="text" class="form-control" id="password" name="password"
                    value="<?php echo $generated_password; ?>">

                <!-- <small>如果不输入密码，将自动生成一个随机密码：<strong><?php echo $generated_password; ?></strong></small> -->
            </div>

            <div class="mb-3">
                <label for="role_id" class="form-label"><i class="fas fa-user-shield"></i> 角色</label>
                <select class="form-control" id="role_id" name="role_id" required <?= ($user_id && $username === 'admin') ? 'disabled' : '' ?>>
                    <option value="">请选择角色</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?php echo $role['role_id']; ?>"
                            <?php echo ($role_id == $role['role_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($role['role_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-save"></i> <?php echo $user_id ? '更新用户' : '创建用户'; ?>
            </button>
        </form>

        <div class="text-center mt-3">
            <a href="users.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> 返回</a>
        </div>
    </div>

    <script src="/assets/js/bootstrap.bundle.min.js"></script>
</body>

</html>
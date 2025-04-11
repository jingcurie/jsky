<script>
if (window.top !== window.self) {
  // 当前在 iframe 中，跳转顶层
  window.top.location = window.location.href;
}
</script>

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/config.php';
require INCLUDE_PATH . '/db.php';
require INCLUDE_PATH . '/auth.php';
require INCLUDE_PATH . '/functions.php';

// 记住我功能,如果存在，自动跳转
if (isset($_COOKIE['remember_me'])) {
    $token = $_COOKIE['remember_me'];
    $sql = "SELECT * FROM users WHERE remember_token = :token";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':token', $token);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role_id'] = $user['role_id'];
        header("Location: index.php"); // 自动跳转到管理面板
        exit;
    }
}

// 初始化变量
$username = '';
$error = '';

// 处理登录请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $remember_me = isset($_POST['remember_me']);

    $sql = "SELECT * FROM users WHERE username = :username";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role_id'] = $user['role_id']; // 存储 role_id

        // 写入登录成功日志
        log_login($conn, $user['user_id'], $user['username'], 1, '登录成功');

        if ($user['must_change_password']) {
            header("Location: change_password.php");
            exit;
        }

        // 记住我功能
        if ($remember_me) {
            $token = bin2hex(random_bytes(32));
            $expiry = time() + 60 * 60 * 24 * 30; // 30 天
            setcookie('remember_me', $token, $expiry, '/');

            $sql = "UPDATE users SET remember_token = :token WHERE user_id = :user_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':token', $token);
            $stmt->bindParam(':user_id', $user['user_id']);
            $stmt->execute();
        }
        // 写入登录成功日志
        log_login($conn, $user['user_id'], $user['username'], 1, '登录成功');
        header("Location: index.php"); // 登录成功后跳转
        exit;
    } else {
        $error = "用户名不存在或密码有误，请核实.";

        // ✅ 写入登录失败日志（注意：user_id 使用 0 或 null）
        log_login($conn, 0, $username, 0, '登录失败：用户名或密码错误');
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        /* 全局样式 */
        body {
            font-family: 'Arial', sans-serif;
            background-color: #0569c1;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        /* 登录框样式 */
        .login-container {
            background: #fff;
            /* padding: 2rem; */
            max-width:800px;
            text-align: center;
            display:grid;
            grid-template-columns: 1fr 1fr;
            /* border-radius: 10px; */
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .login-image-section, .login-section{
            padding:2rem;
        }

        .login-image-section{
            background-color:#f4f9fe;
            display: flex;
            align-items: center;
        }

        .login-container img{
            width:100%;
        }

        .login-container h2 {
            margin-bottom: 1.5rem;
            color: #333;
        }

        .login-container input {
            width: 90%;
            padding: 0.75rem;
            margin: 0.5rem 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .login-container input:focus {
            border-color: #007bff;
            outline: none;
        }

        .login-container button {
            width: 100%;
            padding: 0.75rem;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 20px;
            font-size: 1rem;
            cursor: pointer;
            margin-top: 1rem;
        }

        .login-container button:hover {
            background-color: #0056b3;
        }

        .remember-me {
            display: flex;
            align-items: center;
            margin: 1rem 0;
        }

        .remember-me input {
            width: auto;
            margin-right: 0.5rem;
        }

        .register-link {
            margin-top: 1rem;
            color: #666;
        }

        .register-link a {
            color: #007bff;
            text-decoration: none;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .error {
            color: #ff4444;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-section">
            <h2>用户登陆</h2>
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <input type="text" name="username" placeholder="用户名" required>
                <input type="password" name="password" placeholder="密码" required>
                <div class="remember-me">
                    <input type="checkbox" name="remember_me" id="remember_me">
                    <label for="remember_me">记住我</label>
                </div>
                <button type="submit">Login</button>
            </form>
            <div class="register-link">
                如果您还不是一个注册用户， 点击<a href="register.php">注册</a>.
            </div>
        </div>
        <div class="login-image-section">
            <img src="../assets/images/login.jpg" alt="登陆配图">
        </div>
    </div>
</body>
</html>
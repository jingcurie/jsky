<script>
    if (window.top !== window.self) {
        window.top.location = window.location.href;
    }
</script>

<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/config.php';
require INCLUDE_PATH . '/db.php';
require_once INCLUDE_PATH . '/check_ip_whitelist.php';
require INCLUDE_PATH . '/auth.php';
require_once INCLUDE_PATH . '/functions.php';

// 自动登录（记住我）
if (isset($_COOKIE['remember_me'])) {
    $token = $_COOKIE['remember_me'];
    $sql = "SELECT * FROM users WHERE remember_token = :token AND is_deleted = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':token', $token);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role_id'] = $user['role_id'];
        header("Location: index.php");
        exit;
    }
}

$username = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $captcha = $_POST['captcha'] ?? '';
    $remember_me = isset($_POST['remember_me']);

    if (!isset($_SESSION['captcha']) || strtoupper($captcha) !== $_SESSION['captcha']) {
        $error = "验证码错误，请重新输入";
        log_login($conn, 0, $username, 0, '登录失败：验证码错误');
    } else {
        $sql = "SELECT * FROM users WHERE username = :username";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if ($user['is_deleted'] == 1) {
                $error = "该账号已被禁用，请联系管理员。";
                log_login($conn, 0, $username, 0, '登录失败：账号已被禁用');
            } elseif (!password_verify($password, $user['password_hash'])) {
                $error = "用户名或密码错误，请重试。";
                log_login($conn, 0, $username, 0, '登录失败：密码错误');
            } else {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role_id'] = $user['role_id'];

                log_login($conn, $user['user_id'], $user['username'], 1, '登录成功');

                if ($user['must_change_password']) {
                    header("Location: change_password.php");
                    exit;
                }

                if ($remember_me) {
                    $token = bin2hex(random_bytes(32));
                    $expiry = time() + 60 * 60 * 24 * 30;
                    setcookie('remember_me', $token, $expiry, '/');

                    $sql = "UPDATE users SET remember_token = :token WHERE user_id = :user_id";
                    $stmt = $conn->prepare($sql);
                    $stmt->bindParam(':token', $token);
                    $stmt->bindParam(':user_id', $user['user_id']);
                    $stmt->execute();
                }

                unset($_SESSION['captcha']);
                header("Location: index.php");
                exit;
            }
        } else {
            $error = "用户名或密码错误，请重试。";
            log_login($conn, 0, $username, 0, '登录失败：用户不存在');
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户登录</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #0569c1;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-container {
            background: #fff;
            max-width: 800px;
            text-align: center;
            display: grid;
            grid-template-columns: 1fr 1fr;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .login-image-section,
        .login-section {
            padding: 2rem;
        }

        .login-image-section {
            background-color: #f4f9fe;
            display: flex;
            align-items: center;
        }

        .login-container img {
            width: 100%;
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
            margin-left: 1.5rem;
        }

        .remember-me input {
            width: auto;
            margin-right: 0.5rem;
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
            <h2>用户登录</h2>
            <?php if (isset($error)): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <input type="text" name="username" placeholder="用户名" required>
                <input type="password" name="password" placeholder="密码" required>
                <input type="text" name="captcha" placeholder="请输入验证码" required>
                <div style="margin: 0.5rem auto; text-align:center; width:90%;">
                    <img src="captcha.php" alt="验证码" onclick="this.src='captcha.php?'+Math.random()" style="width:100%; cursor:pointer; border:1px solid #ccc;border-radius:5px">
                </div>
                <div class="remember-me">
                    <input type="checkbox" name="remember_me" id="remember_me">
                    <label for="remember_me">记住我</label>
                </div>
                <button type="submit">登陆</button>
            </form>
        </div>
        <div class="login-image-section">
            <img src="../assets/images/login.jpg" alt="登陆配图">
        </div>
    </div>
</body>

</html>

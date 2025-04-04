<?php
error_reporting(E_ALL);
ini_set('display_errors', 1); // 开启错误报告，防止页面空白

require 'db.php';
session_start();

// 生成 CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 初始化变量
$username = '';
$email = '';
$error = '';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 验证 CSRF Token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token.");
    }

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 获取 Guest 角色的 ID
    $sql = "SELECT role_id FROM roles WHERE role_name = 'Guest' LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $role = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($role) {
        $role_id = $role['role_id']; // 赋值 Guest 角色的 ID
    } else {
        die("错误: 'Guest' 角色未找到，请检查数据库中的 roles 表."); 
    }

    // 验证表单数据
    if (empty($username)) {
        $error = "用户名不能为空.";
    } elseif (empty($email)) {
        $error = "邮箱不能为空.";
    } elseif (empty($password)) {
        $error = "密码不能为空.";
    } elseif ($password !== $confirm_password) {
        $error = "两次输入的密码不一致.";
    } else {
        // 检查用户名是否已存在
        $sql = "SELECT * FROM users WHERE username = :username";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingUser) {
            $error = "该用户已经存在.";
        } else {
            // 检查邮箱是否已存在
            $sql = "SELECT * FROM users WHERE email = :email";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $existingEmail = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existingEmail) {
                $error = "该邮箱已经存在.";
            } else {
                // 插入新用户
                $password_hash = password_hash($password, PASSWORD_BCRYPT);
                $sql = "INSERT INTO users (username, password_hash, email, role_id) VALUES (:username, :password, :email, :role_id)";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':password', $password_hash);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':role_id', $role_id);

                if ($stmt->execute()) {
                    header("Location: login.php"); // 注册成功后跳转到登录页面
                    exit;
                } else {
                    $error = "注册用户时出错.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户注册</title>
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

        .login-image-section, .login-section {
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

        .password-container {
            position: relative;
            width: 90%;
            margin: 0 auto;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            user-select: none;
        }

        .eye-icon {
            font-size: 20px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-section">
            <h2>用户注册</h2>
            <?php if (!empty($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="text" name="username" placeholder="用户名" value="<?php echo htmlspecialchars($username); ?>" required>
                <input type="email" name="email" placeholder="邮箱" value="<?php echo htmlspecialchars($email); ?>" required>
                <div class="password-container">
                    <input type="password" name="password" id="password" placeholder="密码" required>
                    <span class="toggle-password" onclick="togglePassword()">
                        <i class="eye-icon">👁️</i>
                    </span>
                </div>
                <input type="password" name="confirm_password" placeholder="确认密码" required>
                <button type="submit">注册</button>
            </form>
            <div class="register-link">
                如果您已经注册过，请点击 <a href="login.php">登录</a>.
            </div>
        </div>
        <div class="login-image-section">
            <img src="../assets/images/login.jpg" alt="注册配图">
        </div>
    </div>
</body>
</html>

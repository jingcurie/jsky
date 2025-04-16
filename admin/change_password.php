
<?php
require_once __DIR__ . '/../includes/config.php';
require INCLUDE_PATH . '/db.php';
require_once INCLUDE_PATH . '/check_ip_whitelist.php';
require INCLUDE_PATH . '/auth.php';
require INCLUDE_PATH . '/functions.php';

if (!isLoggedIn()) {
    redirect('/admin/login.php');
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST['old_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = :uid");
    $stmt->execute([':uid' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($old, $user['password_hash'])) {
        $error = "旧密码错误。";
    } elseif ($new !== $confirm) {
        $error = "新密码与确认密码不一致。";
    } elseif (strlen($new) < 6) {
        $error = "新密码长度不能少于6位。";
    } else {
        $new_hash = password_hash($new, PASSWORD_DEFAULT);
        $update = $conn->prepare("UPDATE users SET password_hash = :hash, must_change_password = 0 WHERE user_id = :uid");
        $update->execute([':hash' => $new_hash, ':uid' => $user_id]);
        $success = "密码修改成功！";
        header('Location: login.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>修改密码</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

        .change-container {
            background: #fff;
            display: grid;
            grid-template-columns: 1fr 1fr;
            max-width: 800px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .form-section, .image-section {
            padding: 2rem;
        }

        .image-section {
            background-color: #f4f9fe;
            display: flex;
            align-items: center;
        }

        .image-section img {
            width: 100%;
        }

        h2 {
            margin-bottom: 1.5rem;
            color: #333;
        }

        form input {
            width: 90%;
            padding: 0.75rem;
            margin: 0.5rem 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        form button {
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

        form button:hover {
            background-color: #0056b3;
        }

        .alert {
            margin-bottom: 1rem;
            padding: 0.75rem;
            border-radius: 5px;
        }

        .alert-danger {
            background-color: #ffdddd;
            color: #a94442;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
    </style>
</head>
<body>
<div class="change-container">
    <div class="form-section">
        <h2>修改密码</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="password" name="old_password" placeholder="旧密码" required>
            <input type="password" name="new_password" placeholder="新密码" required>
            <input type="password" name="confirm_password" placeholder="确认新密码" required>
            <button type="submit">确认修改</button>
        </form>
    </div>
    <div class="image-section">
        <img src="<?= IMG_URL ?>/login.jpg" alt="密码修改插图">
    </div>
</div>
</body>
</html>

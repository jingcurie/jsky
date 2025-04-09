<?php
session_start();
require '../includes/db.php';
require '../includes/functions.php';

// 如果用户已登录，清除数据库中的 remember_token
if (isset($_SESSION['user_id'])) {

    // 登出日志记录
    $user_id = $_SESSION['user_id'];
    $username = $_SESSION['username'] ?? 'unknown';
    log_login($conn, $user_id, $username, 1, '用户登出');

    $sql = "UPDATE users SET remember_token = NULL WHERE user_id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
}


// 清除 session
session_unset();
session_destroy();

// 清除浏览器 remember_me 的 cookie
setcookie('remember_me', '', time() - 3600, '/'); // 设置为过去时间即可删除

// 跳转回登录页
header('Location: index.php');
exit;
?>
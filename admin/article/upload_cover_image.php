<?php
session_start();
require '../includes/db.php'; // 确保数据库连接
require '../includes/auth.php'; // 确保用户登录
require '../includes/functions.php';

if (!isLoggedIn()) {
    echo json_encode(["success" => false, "error" => "未授权访问"]);
    exit;
}

// CSRF 保护（确保 POST 请求来自合法表单）
if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_FILES["file"])) {
    echo json_encode(["success" => false, "error" => "非法请求"]);
    exit;
}

// 限制文件类型
$allowedTypes = ["image/jpeg", "image/png"];
$file = $_FILES["file"];
if (!in_array($file["type"], $allowedTypes)) {
    echo json_encode(["success" => false, "error" => "只允许上传 JPG 或 PNG 图片"]);
    exit;
}

// 限制文件大小（3MB）
if ($file["size"] > 3 * 1024 * 1024) {
    echo json_encode(["success" => false, "error" => "文件大小不能超过 3MB"]);
    exit;
}

// 生成唯一文件名
$ext = pathinfo($file["name"], PATHINFO_EXTENSION);
$fileName = uniqid("img_", true) . "." . $ext;
$targetDir = __DIR__ . "/../assets/images/uploads/";
$targetFile = $targetDir . $fileName;

// 确保目录存在
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

// 移动文件
if (move_uploaded_file($file["tmp_name"], $targetFile)) {
    echo json_encode(["success" => true, "file_name" => $fileName]);
} else {
    echo json_encode(["success" => false, "error" => "上传失败"]);
}
?>

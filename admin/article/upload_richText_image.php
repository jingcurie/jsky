<?php
// 配置
ini_set('upload_max_filesize', '3M');
ini_set('post_max_size', '3M');
header('Content-Type: application/json');

// 依赖加载
require_once __DIR__ . '/../../includes/config.php';
require_once INCLUDE_PATH . '/db.php';
require_once INCLUDE_PATH . '/auth.php';
require_once INCLUDE_PATH . '/functions.php';

// 权限验证
if (!isLoggedIn()) {
    echo json_encode(["success" => false, "error" => "未授权访问"]);
    exit;
}

// 配置参数
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
$max_size = 3 * 1024 * 1024; // 3MB
$upload_dir = realpath(__DIR__ . '/../../uploads/articles/') . '/';

// 创建目录
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// 验证上传
if (!isset($_FILES['file'])) {
    echo json_encode(["success" => false, "error" => "未接收到文件"]);
    exit;
}

$file = $_FILES['file'];

// 安全验证
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(["success" => false, "error" => "上传错误: " . $file['error']]);
    exit;
}

if ($file['size'] > $max_size) {
    echo json_encode(["success" => false, "error" => "文件大小超过3MB限制"]);
    exit;
}

$file_type = mime_content_type($file['tmp_name']);
if (!in_array($file_type, $allowed_types)) {
    echo json_encode(["success" => false, "error" => "只允许JPG/PNG/GIF图片"]);
    exit;
}

// 生成安全文件名
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$filename = 'rich_' . bin2hex(random_bytes(8)) . '.' . $ext;
$target_path = $upload_dir . $filename;

// 移动文件
if (!move_uploaded_file($file['tmp_name'], $target_path)) {
    echo json_encode(["success" => false, "error" => "文件保存失败"]);
    exit;
}

// 返回TinyMCE需要的格式
echo json_encode([
    "location" => "/uploads/articles/" . $filename, // 完整可访问URL
    "title" => pathinfo($file['name'], PATHINFO_FILENAME) // 原始文件名(无扩展名)
]);
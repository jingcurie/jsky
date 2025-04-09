<?php
session_start();
require_once __DIR__ . '/../../includes/config.php';
require_once INCLUDE_PATH . '/db.php';
require_once INCLUDE_PATH . '/auth.php';
require_once INCLUDE_PATH . '/functions.php';

header('Content-Type: application/json');

// 验证登录和权限
if (!isLoggedIn()) {
    echo json_encode(["success" => false, "error" => "未授权访问"]);
    exit;
}

// 验证请求方法
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "error" => "非法请求方式"]);
    exit;
}

// 检查是否编辑模式（通过文章ID判断）
$isEditMode = isset($_POST['article_id']) && is_numeric($_POST['article_id']);
$oldCoverPath = '';

// 如果是编辑模式，获取旧封面路径
if ($isEditMode) {
    $articleId = (int)$_POST['article_id'];
    $stmt = $conn->prepare("SELECT cover_image FROM articles WHERE id = ?");
    $stmt->execute([$articleId]);
    $oldCoverPath = $stmt->fetchColumn();
}

// 验证文件上传
if (!isset($_FILES["file"])) {
    echo json_encode(["success" => false, "error" => "未接收到文件"]);
    exit;
}

$file = $_FILES["file"];

// 验证文件类型
$allowedTypes = ["image/jpeg", "image/png"];
$fileType = mime_content_type($file["tmp_name"]); // 更安全的MIME检测
if (!in_array($fileType, $allowedTypes)) {
    echo json_encode(["success" => false, "error" => "只允许上传 JPG 或 PNG 图片"]);
    exit;
}

// 验证文件大小 (3MB)
if ($file["size"] > 3 * 1024 * 1024) {
    echo json_encode(["success" => false, "error" => "文件大小不能超过 3MB"]);
    exit;
}

// 生成安全文件名
$ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
$fileName = 'cover_' . bin2hex(random_bytes(8)) . '.' . $ext; // 更安全的随机名
$targetDir = realpath(__DIR__ . "/../../uploads/articles/") . '/';
$targetFile = $targetDir . $fileName;


// 创建目录（如果不存在）
if (!is_dir($targetDir)) {
    if (!mkdir($targetDir, 0755, true)) {
        echo json_encode(["success" => false, "error" => "无法创建上传目录"]);
        exit;
    }
}

if (!is_dir($targetDir)) {
    echo json_encode(["success" => false, "error" => "目标目录不存在"]);
    exit;
}

// echo $file["tmp_name"]. "<br>";
// echo $targetFile;
// exit;

// 移动上传文件
if (!move_uploaded_file($file["tmp_name"], $targetFile)) {
    echo json_encode(["success" => false, "error" => "文件移动失败"]);
    exit;
}

// 如果是编辑模式且旧封面存在，则删除旧文件
if ($isEditMode && !empty($oldCoverPath)) {
    $oldFile = $targetDir . basename($oldCoverPath);
    if (file_exists($oldFile) && is_writable($oldFile)) {
        @unlink($oldFile); // 静默删除，失败不影响主流程
    }
}

// 返回成功响应
echo json_encode([
    "success" => true,
    "file_name" => $fileName,
    "file_path" => "/uploads/articles/" . $fileName // 返回完整可访问路径
]);
?>
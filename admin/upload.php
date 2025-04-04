

<?php
require '../includes/db.php'; // 数据库连接

$upload_dir = '../assets/images/uploads/'; // 目标存储目录

// 确保目录存在
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// 确定是哪个字段上传的（TinyMCE 默认用 "file"）
$file_key = isset($_FILES['cover_image']) ? 'cover_image' : (isset($_FILES['file']) ? 'file' : null);

if ($file_key === null || !isset($_FILES[$file_key]) || $_FILES[$file_key]['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(["success" => false, "error" => "文件上传失败"]);
    exit;
}

$file = $_FILES[$file_key];
$filename = uniqid() . '_' . basename($file['name']);  // 生成唯一文件名
$target_path = $upload_dir . $filename;  // 目标文件路径

// 移动上传文件
if (move_uploaded_file($file['tmp_name'], $target_path)) {
    $file_url = isset($_FILES['cover_image']) ? 'assets/images/uploads/' . $filename : '../assets/images/uploads/' . $filename;

    // 根据上传来源返回不同格式
    if ($file_key === 'cover_image') {
        echo json_encode(["success" => true, "file" => $file_url]);  // 封面图片
    } else {
        echo json_encode(["location" => $file_url]);  // TinyMCE 需要 "location" 字段
    }
} else {
    echo json_encode(["success" => false, "error" => "文件保存失败"]);
}
?>


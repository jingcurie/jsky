<?php
require '../includes/db.php';
require '../includes/auth.php';
require '../includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

csrfProtect(); // 在表单处理前检查

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 验证并处理上传的Banner图片
    if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        $fileType = $_FILES['banner_image']['type'];
        $fileSize = $_FILES['banner_image']['size'];
        
        if (!in_array($fileType, $allowedTypes)) {
            $error = '只允许上传JPEG、PNG或WebP格式的图片';
        } elseif ($fileSize > $maxSize) {
            $error = '图片大小不能超过2MB';
        } else {
            // 生成唯一文件名
            $extension = pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION);
            $filename = 'banner_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
            $uploadPath = '../assets/images/uploads/banners/' . $filename;
            
            // 确保上传目录存在
            if (!is_dir('../assets/images/uploads/banners')) {
                mkdir('../assets/images/uploads/banners', 0755, true);
            }
            
            if (move_uploaded_file($_FILES['banner_image']['tmp_name'], $uploadPath)) {
                // 保存到数据库
                $data = [
                    'image_path' => $filename,
                    'title' => $_POST['title'] ?? '',
                    'url' => $_POST['url'] ?? '',
                    'target' => $_POST['target'] ?? '_blank',
                    'sort_order' => $_POST['sort_order'] ?? 0,
                    'is_active' => isset($_POST['is_active']) ? 1 : 0
                ];
                
                if (insert($conn, 'site_banners', $data)) {
                    $success = 'Banner添加成功！';
                    $_POST = []; // 清空表单
                } else {
                    $error = '保存到数据库失败';
                    // 删除已上传的图片
                    unlink($uploadPath);
                }
            } else {
                $error = '上传文件时出错';
            }
        }
    } else {
        $error = '请选择有效的Banner图片';
    }
}
?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>添加Banner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="../assets/css/admin_style.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h2><i class="fas fa-image"></i> 添加Banner</h2>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

            <div class="mb-3">
                <label for="banner_image" class="form-label">Banner图片</label>
                <input type="file" class="form-control" id="banner_image" name="banner_image" required accept="image/*">
                <small class="text-muted">建议尺寸: 1200x400像素，大小不超过2MB</small>
            </div>
            
            <div class="mb-3">
                <label for="title" class="form-label">标题（可选）</label>
                <input type="text" class="form-control" id="title" name="title" 
                       value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
            </div>
            
            <div class="mb-3">
                <label for="url" class="form-label">链接地址</label>
                <input type="url" class="form-control" id="url" name="url" 
                       value="<?php echo htmlspecialchars($_POST['url'] ?? ''); ?>">
            </div>
            
            <div class="mb-3">
                <label for="target" class="form-label">打开方式</label>
                <select class="form-select" id="target" name="target">
                    <option value="_blank" <?php echo ($_POST['target'] ?? '_blank') === '_blank' ? 'selected' : ''; ?>>新窗口打开</option>
                    <option value="_self" <?php echo ($_POST['target'] ?? '') === '_self' ? 'selected' : ''; ?>>当前窗口打开</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="sort_order" class="form-label">排序</label>
                <input type="number" class="form-control" id="sort_order" name="sort_order" 
                       value="<?php echo htmlspecialchars($_POST['sort_order'] ?? 0); ?>">
            </div>
            
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" 
                    <?php echo isset($_POST['is_active']) ? 'checked' : 'checked'; ?>>
                <label class="form-check-label" for="is_active">立即启用</label>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> 保存Banner
            </button>
            
            <a href="settings.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> 返回
            </a>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
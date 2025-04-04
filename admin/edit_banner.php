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
$banner = null;

// 获取Banner ID
$banner_id = $_GET['id'] ?? 0;
if (!$banner_id) {
    redirect('settings.php');
}

// 获取当前Banner信息
$banner = getById($conn, 'site_banners', 'id', $banner_id);
if (!$banner) {
    $error = '指定的Banner不存在';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'title' => $_POST['title'] ?? '',
        'url' => $_POST['url'] ?? '',
        'target' => $_POST['target'] ?? '_blank',
        'sort_order' => $_POST['sort_order'] ?? 0,
        'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];
    
    // 如果有新图片上传
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
            
            if (move_uploaded_file($_FILES['banner_image']['tmp_name'], $uploadPath)) {
                // 删除旧图片
                if (file_exists($banner['image_path'])) {
                    unlink($banner['image_path']);
                }
                $data['image_path'] = $filename;
            } else {
                $error = '上传文件时出错';
            }
        }
    }
    
    if (empty($error)) {
        if (update($conn, 'site_banners', 'id', $banner_id, $data)) {
            $success = 'Banner更新成功！';
            // 重新获取更新后的Banner信息
            $banner = getById($conn, 'site_banners', 'id', $banner_id);
        } else {
            $error = '更新Banner失败';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>编辑Banner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="../assets/css/admin_style.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h2><i class="fas fa-image"></i> 编辑Banner</h2>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($banner): ?>
            <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <div class="mb-3">
                    <label class="form-label">当前图片</label>
                    <div>
                        <img src="/assets/images/uploads/banners/<?php echo htmlspecialchars($banner['image_path']); ?>" style="max-width: 100%; max-height: 200px;">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="banner_image" class="form-label">更换图片（可选）</label>
                    <input type="file" class="form-control" id="banner_image" name="banner_image" accept="image/*">
                    <small class="text-muted">建议尺寸: 1200x400像素，大小不超过2MB</small>
                </div>
                
                <div class="mb-3">
                    <label for="title" class="form-label">标题（可选）</label>
                    <input type="text" class="form-control" id="title" name="title" 
                           value="<?php echo htmlspecialchars($banner['title']); ?>">
                </div>
                
                <div class="mb-3">
                    <label for="url" class="form-label">链接地址</label>
                    <input type="url" class="form-control" id="url" name="url" 
                           value="<?php echo htmlspecialchars($banner['url']); ?>">
                </div>
                
                <div class="mb-3">
                    <label for="target" class="form-label">打开方式</label>
                    <select class="form-select" id="target" name="target">
                        <option value="_blank" <?php echo $banner['target'] === '_blank' ? 'selected' : ''; ?>>新窗口打开</option>
                        <option value="_self" <?php echo $banner['target'] === '_self' ? 'selected' : ''; ?>>当前窗口打开</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="sort_order" class="form-label">排序</label>
                    <input type="number" class="form-control" id="sort_order" name="sort_order" 
                           value="<?php echo htmlspecialchars($banner['sort_order']); ?>">
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" 
                        <?php echo $banner['is_active'] ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="is_active">启用</label>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> 更新Banner
                </button>
                
                <a href="settings.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> 返回
                </a>
            </form>
        <?php else: ?>
            <div class="alert alert-warning"><?php echo $error ?: 'Banner不存在'; ?></div>
            <a href="settings.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> 返回
            </a>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
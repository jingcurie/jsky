
<?php
// 错误报告设置（开发环境）
error_reporting(E_ALL);          // 报告所有PHP错误
ini_set('display_errors', 1);    // 在页面上显示错误
ini_set('display_startup_errors', 1); // 显示启动错误

require_once __DIR__ . '/../../includes/config.php';
require INCLUDE_PATH . '/db.php';
require_once INCLUDE_PATH . '/check_ip_whitelist.php';
require INCLUDE_PATH . '/auth.php';
require_once INCLUDE_PATH . '/functions.php';

if (!isLoggedIn()) {
    redirect('/admin/login.php');
}

csrfProtect();

$error = '';
$success = '';
$banner = [
    'id' => 0,
    'image_path' => '',
    'title' => '',
    'description' => '',
    'url' => '',
    'target' => '_blank',
    'sort_order' => 0,
    'is_active' => 1
];

// 判断是编辑还是添加模式
$is_edit = false;
$banner_id = $_GET['id'] ?? 0;

if ($banner_id) {
    $is_edit = true;
    $banner = getById($conn, 'site_banners', 'id', $banner_id);
    if (!$banner) {
        $error = '指定的Banner不存在';
        redirect('settings.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'title' => $_POST['title'] ?? '',
        'url' => $_POST['url'] ?? '',
        'description' => $_POST['description'] ?? '',
        'target' => $_POST['target'] ?? '_blank',
        'sort_order' => $_POST['sort_order'] ?? 0,
        'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];
    
    // 处理图片上传
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
            $uploadPath = __DIR__ . "/../.." .BANNER_URL . $filename;
            
            // 确保上传目录存在
            if (!is_dir(__DIR__ . "/../../uploads/banners")) {
                mkdir(__DIR__ . "/../../uploads/banners", 0755, true);
            }
        
            if (move_uploaded_file($_FILES['banner_image']['tmp_name'], $uploadPath)) {
                // 如果是编辑模式，删除旧图片
                if ($is_edit && !empty($banner['image_path']) && file_exists(BANNER_URL . '/' . $banner['image_path'])) {
                    unlink(BANNER_URL . '/' . $banner['image_path']);
                }
                $data['image_path'] = $filename;
            } else {
                $error = '上传文件时出错';
            }
        }
    } elseif (!$is_edit) {
        // 添加模式下必须上传图片
        $error = '请选择有效的Banner图片';
    }
    
    if (empty($error)) {
        if ($is_edit) {
            if (update($conn, 'site_banners', 'id', $banner_id, $data)) {
                log_operation($conn, $_SESSION['user_id'], $_SESSION['username'], '更新', 'banner', $banner_id, null);
                $success = 'Banner更新成功！';
                $banner = array_merge($banner, $data);
            } else {
                $error = '更新Banner失败';
            }
        } else {
            if (insert($conn, 'site_banners', $data)) {
                log_operation($conn, $_SESSION['user_id'], $_SESSION['username'], '创建', 'banner', null, null);
                $success = 'Banner添加成功！';
                $_POST = []; // 清空表单
                // 重置banner数据，保持默认值
                $banner = [
                    'id' => 0,
                    'image_path' => '',
                    'title' => '',
                    'description' => '',
                    'url' => '',
                    'target' => '_blank',
                    'sort_order' => 0,
                    'is_active' => 1
                ];
            } else {
                $error = '保存到数据库失败';
                // 删除已上传的图片
                if (isset($uploadPath) && file_exists($uploadPath)) {
                    unlink($uploadPath);
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
    <title><?php echo $is_edit ? '编辑' : '添加'; ?>Banner</title>
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"> -->
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/all.min.css">
    <link href="<?= CSS_URL ?>/admin_style.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h2><i class="fas fa-image"></i> <?php echo $is_edit ? '编辑' : '添加'; ?>Banner</h2>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

            <?php if ($is_edit && !empty($banner['image_path'])): ?>
                <div class="mb-3">
                    <label class="form-label">当前图片</label>
                    <div>
                        <img src="<?= BANNER_URL ?>/<?php echo htmlspecialchars($banner['image_path']); ?>" style="max-width: 100%; max-height: 200px;">
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="mb-3">
                <label for="banner_image" class="form-label">Banner图片<?php echo $is_edit ? '（可选）' : ''; ?></label>
                <input type="file" class="form-control" id="banner_image" name="banner_image" <?php echo !$is_edit ? 'required' : ''; ?> accept="image/*">
                <small class="text-muted">建议尺寸: 1200x400像素，大小不超过2MB</small>
            </div>
            
            <div class="mb-3">
                <label for="title" class="form-label">标题</label>
                <input type="text" class="form-control" id="title" name="title" 
                       value="<?php echo htmlspecialchars($banner['title']); ?>">
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">描述</label>
                <textarea rows="4" cols="50" class="form-control" id="description" name="description"><?php echo htmlspecialchars($banner['description']); ?></textarea>
                <!-- <input type="text" class="form-control" id="description" name="description" 
                       value=""> -->
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
                <i class="fas fa-save"></i> <?php echo $is_edit ? '更新' : '保存'; ?>Banner
            </button>
            
            <a href="settings.php#home" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> 返回
            </a>
        </form>
    </div>

    <script src="/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
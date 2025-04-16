
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once __DIR__ . '/../../includes/config.php';
require INCLUDE_PATH . '/db.php';
require_once INCLUDE_PATH . '/check_ip_whitelist.php';
require INCLUDE_PATH . '/auth.php';
require INCLUDE_PATH . '/functions.php';

if (!isLoggedIn()) {
    redirect('/admin/login.php');
}

// 初始化变量
$success = '';
$error = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = intval($_POST['delete_id']);
    if ($_POST['category_id'] === "whitelist") {
        //csrfProtect();
        $ip = getById($conn, 'allowed_ips', 'id', $id);
        if ($ip) {
            delete($conn, 'allowed_ips', 'id', $id);
            log_operation($conn, $_SESSION['user_id'], $_SESSION['username'], '删除', 'IP白名单', $ip["id"], $ip['ip_address']);
            $_SESSION['success'] = "IP 已从白名单中移除！";
        } else {
            $_SESSION['error'] = "未找到指定 IP 白名单记录。";
        }
        redirect('settings.php#whitelist');
        exit;
    }

    if ($_POST['category_id'] === "banner") {
        // 获取 Banner 记录（假设你有获取数据的函数）
        $banner = getById($conn, "site_banners", "id", $id); // 这是你查询数据库的函数
        if ($banner) {
            $imagePath = $banner['image_path']; // 获取图片路径

            // 删除图片文件
            $imageFilePath = BANNER_PATH . $imagePath;
            if (file_exists($imageFilePath)) {
                unlink($imageFilePath); // 删除物理文件
            }

            // 删除数据库中的记录
            delete($conn, 'site_banners', 'id', $id);
            log_operation($conn, $_SESSION['user_id'], $_SESSION['username'], '删除', 'banner', $id, null);
            redirect('settings.php#home');

            $success = "Banner 和图片已删除成功！";
        } else {
            $error = "找不到要删除的 Banner！";
        }
    }
    // ===== IP 白名单删除处理 =====

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_ip'])) { //IP 白名单添加处理
    csrfProtect();

    $ip = trim($_POST['new_ip']);

    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        $error = "无效的 IP 地址格式！";
    } else {
        // 检查是否已存在
        $exists = query($conn, "SELECT COUNT(*) as count FROM allowed_ips WHERE ip_address = ?", [$ip]);
        if ($exists[0]['count'] > 0) {
            $error = "该 IP 已存在于白名单中！";
        } else {
            insert($conn, 'allowed_ips', [
                'ip_address' => $ip,
                //'created_at' => date('Y-m-d H:i:s')
            ]);
            log_operation($conn, $_SESSION['user_id'], $_SESSION['username'], '添加', 'IP白名单', null, $ip);
            $_SESSION['success'] = "IP 白名单添加成功！";
            redirect('settings.php#whitelist');
        }
    }
}else if($_SERVER['REQUEST_METHOD'] === 'POST') { // 处理大表单提交
    // CSRF防护检查
    csrfProtect();

    try {
        $conn->beginTransaction();

        // === 新增LOGO上传处理（最简版）===
        if (!empty($_FILES['site_logo']['tmp_name'])) {
            // 基础验证
            $allowedTypes = ['image/jpeg', 'image/png'];
            $maxSize = 2 * 1024 * 1024; // 2MB

            if (!in_array($_FILES['site_logo']['type'], $allowedTypes)) {
                throw new Exception('只允许上传JPEG或PNG图片');
            }

            if ($_FILES['site_logo']['size'] > $maxSize) {
                throw new Exception('图片大小不能超过2MB');
            }

            // 准备目录
            $uploadDir = '../../uploads/logos/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // 生成文件名
            $ext = pathinfo($_FILES['site_logo']['name'], PATHINFO_EXTENSION);
            $filename = 'logo_' . uniqid() . '.' . $ext;
            $destination = $uploadDir . $filename;

            // 移动文件
            if (!move_uploaded_file($_FILES['site_logo']['tmp_name'], $destination)) {
                throw new Exception('文件保存失败，请检查目录权限');
            }

            // 7. 更新数据库
            update($conn, 'site_settings', 'setting_key', 'site_logo', [
                'setting_value' => $filename,
                'setting_group' => 'basic'
            ]);
            log_operation($conn, $_SESSION['user_id'], $_SESSION['username'], '更新', 'logo', null, null);
        }
        // === 上传处理结束 ===

        //更新其他属性值
        update($conn, 'site_settings', 'setting_key', 'site_title', [
            'setting_value' => $_POST['site_title'],
            'setting_group' => 'basic'
        ]);

        update($conn, 'site_settings', 'setting_key', 'site_description', [
            'setting_value' => $_POST['site_description'],
            'setting_group' => 'basic'
        ]);

        update($conn, 'site_settings', 'setting_key', 'facebook_url', [
            'setting_value' => $_POST['facebook_url'],
            'setting_group' => 'social'
        ]);

        update($conn, 'site_settings', 'setting_key', 'twitter_url', [
            'setting_value' => $_POST['twitter_url'],
            'setting_group' => 'social'
        ]);

        update($conn, 'site_settings', 'setting_key', 'contact_email', [
            'setting_value' => $_POST['contact_email'],
            'setting_group' => 'contact'
        ]);

        update($conn, 'site_settings', 'setting_key', 'contact_phone', [
            'setting_value' => $_POST['contact_phone'],
            'setting_group' => 'contact'
        ]);

        update($conn, 'site_settings', 'setting_key', 'meta_keywords', [
            'setting_value' => $_POST['meta_keywords'],
            'setting_group' => 'seo'
        ]);

        update($conn, 'site_settings', 'setting_key', 'meta_description', [
            'setting_value' => $_POST['meta_description'],
            'setting_group' => 'seo'
        ]);

        update($conn, 'site_settings', 'setting_key', 'cache_enabled', [
            'setting_value' => isset($_POST['cache_enabled']) ? 1 : 0,
            'setting_group' => 'performance'
        ]);

        log_operation($conn, $_SESSION['user_id'], $_SESSION['username'], '更新', '站点信息', null, null);

        $conn->commit();
        $success = "设置已成功更新！";

        // 刷新CSRF令牌
        generateCsrfToken();
    } catch (PDOException $e) {
        $conn->rollBack();
        $error = "数据库错误: " . $e->getMessage();
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $error = $e->getMessage();
    }
}

//获取所有设置信息（除banner和白名单）
$settings = [];
$result = query($conn, "SELECT setting_key, setting_value FROM site_settings");
foreach ($result as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// 获取所有Banner
$banners = query($conn, "SELECT * FROM site_banners ORDER BY sort_order ASC");

// 获取 IP 白名单
$whitelisted_ips = query($conn, "SELECT * FROM allowed_ips ORDER BY created_at DESC");

// var_dump($settings["site_title"]);
// exit;

require "settings_view.php";

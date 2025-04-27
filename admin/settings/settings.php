<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once __DIR__ . '/../../includes/config.php';
require INCLUDE_PATH . '/db.php';
require_once INCLUDE_PATH . '/check_ip_whitelist.php';
require INCLUDE_PATH . '/auth.php';
require_once INCLUDE_PATH . '/functions.php';

if (!isLoggedIn()) {
    redirect('/admin/login.php');
}

// 初始化变量
$success = '';
$error = '';

function uploadLogo($fileInput, $prefix)
{
    $allowedTypes = ['image/jpeg', 'image/png'];
    $maxSize = 2 * 1024 * 1024;

    if (!in_array($fileInput['type'], $allowedTypes)) {
        throw new Exception("$prefix Logo：只允许上传JPEG或PNG图片");
    }

    if ($fileInput['size'] > $maxSize) {
        throw new Exception("$prefix Logo：图片大小不能超过2MB");
    }

    $uploadDir = '../../uploads/logos/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $ext = pathinfo($fileInput['name'], PATHINFO_EXTENSION);
    $filename = $prefix . '_' . uniqid() . '.' . $ext;
    $destination = $uploadDir . $filename;

    if (!move_uploaded_file($fileInput['tmp_name'], $destination)) {
        throw new Exception("$prefix Logo：文件保存失败，请检查目录权限");
    }

    return $filename;
}

// 处理删除请求（IP、Banner）
function isValidIpOrCidr($input)
{
    if (filter_var($input, FILTER_VALIDATE_IP)) {
        return true;
    }

    // 检查 CIDR 格式：如 192.168.1.0/24
    if (preg_match('/^(\d{1,3}\.){3}\d{1,3}\/\d{1,2}$/', $input)) {
        list($ip, $mask) = explode('/', $input);
        return filter_var($ip, FILTER_VALIDATE_IP) && $mask >= 0 && $mask <= 32;
    }

    return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = intval($_POST['delete_id']);
    if ($_POST['category_id'] === "whitelist") {
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
        $banner = getById($conn, "site_banners", "id", $id);
        if ($banner) {
            $imagePath = $banner['image_path'];
            $imageFilePath = BANNER_PATH . $imagePath;
            if (file_exists($imageFilePath)) {
                unlink($imageFilePath);
            }
            delete($conn, 'site_banners', 'id', $id);
            log_operation($conn, $_SESSION['user_id'], $_SESSION['username'], '删除', 'banner', $id, null);
            redirect('settings.php#home');
            $success = "Banner 和图片已删除成功！";
        } else {
            $error = "找不到要删除的 Banner！";
        }
    }
}elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfProtect();
    try {
        $conn->beginTransaction();
        // 清空 IP白名单，再批量插入新IP
        if (isset($_POST['ip_list'])) {
            $ipListRaw = trim($_POST['ip_list']);
            $ipLines = explode("\n", $ipListRaw);
            $ipLines = array_map('trim', $ipLines); // 去除空格
            $ipLines = array_filter($ipLines); // 去除空行

            // 重新清空 allowed_ips 表
            execute($conn, "DELETE FROM allowed_ips");

            foreach ($ipLines as $ip) {
                if (!isValidIpOrCidr($ip)) {
                    throw new Exception("无效的IP地址或CIDR格式：$ip");
                }
                insert($conn, 'allowed_ips', ['ip_address' => $ip]);
            }

            log_operation($conn, $_SESSION['user_id'], $_SESSION['username'], '更新', 'IP白名单', null, null);
        }

        // 主 Logo 上传
        if (!empty($_FILES['site_logo_large']['tmp_name'])) {
            $filename = uploadLogo($_FILES['site_logo_large'], 'logo_large');
            update($conn, 'site_settings', 'setting_key', 'site_logo_large', [
                'setting_value' => $filename,
                'setting_group' => 'basic'
            ]);
        }

        // 上传成功后立即重新加载最新设置
        $result = query($conn, "SELECT setting_key, setting_value FROM site_settings");
        foreach ($result as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        if (!empty($_POST['remove_logo_large']) && !empty($settings['site_logo_large'])) {

            @unlink('../../uploads/logos/' . $settings['site_logo_large']);
            update($conn, 'site_settings', 'setting_key', 'site_logo_large', ['setting_value' => '', 'setting_group' => 'basic']);
        }

        // small Logo 上传
        if (!empty($_FILES['site_logo_small']['tmp_name'])) {
            $filename = uploadLogo($_FILES['site_logo_small'], 'logo_small');
            update($conn, 'site_settings', 'setting_key', 'site_logo_small', [
                'setting_value' => $filename,
                'setting_group' => 'basic'
            ]);
        }

        // 上传成功后立即重新加载最新设置
        $result = query($conn, "SELECT setting_key, setting_value FROM site_settings");
        foreach ($result as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        if (!empty($_POST['remove_logo_small']) && !empty($settings['site_logo_small'])) {
            @unlink('../../uploads/logos/' . $settings['site_logo_small']);
            update($conn, 'site_settings', 'setting_key', 'site_logo_small', ['setting_value' => '', 'setting_group' => 'basic']);
        }

        // ✅ 其它设置项更新（保持原样）
        update($conn, 'site_settings', 'setting_key', 'site_title', ['setting_value' => $_POST['site_title'], 'setting_group' => 'basic']);
        update($conn, 'site_settings', 'setting_key', 'site_description', ['setting_value' => $_POST['site_description'], 'setting_group' => 'basic']);
        update($conn, 'site_settings', 'setting_key', 'facebook_url', ['setting_value' => $_POST['facebook_url'], 'setting_group' => 'social']);
        update($conn, 'site_settings', 'setting_key', 'twitter_url', ['setting_value' => $_POST['twitter_url'], 'setting_group' => 'social']);
        update($conn, 'site_settings', 'setting_key', 'contact_email', ['setting_value' => $_POST['contact_email'], 'setting_group' => 'contact']);
        update($conn, 'site_settings', 'setting_key', 'contact_phone', ['setting_value' => $_POST['contact_phone'], 'setting_group' => 'contact']);
        update($conn, 'site_settings', 'setting_key', 'meta_keywords', ['setting_value' => $_POST['meta_keywords'], 'setting_group' => 'seo']);
        update($conn, 'site_settings', 'setting_key', 'meta_description', ['setting_value' => $_POST['meta_description'], 'setting_group' => 'seo']);
        update($conn, 'site_settings', 'setting_key', 'cache_enabled', ['setting_value' => isset($_POST['cache_enabled']) ? 1 : 0, 'setting_group' => 'performance']);
        update($conn, 'site_settings', 'setting_key', 'ip_whitelist_enabled', ['setting_value' => isset($_POST['ip_whitelist_enabled']) ? 1 : 0, 'setting_group' => 'security']);
        log_operation($conn, $_SESSION['user_id'], $_SESSION['username'], '更新', '站点信息', null, null);
        $conn->commit();
        $success = "设置已成功更新！";
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

// 获取设置
$settings = [];
$result = query($conn, "SELECT setting_key, setting_value FROM site_settings");
foreach ($result as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$banners = query($conn, "SELECT * FROM site_banners ORDER BY sort_order ASC");
$whitelisted_ips = query($conn, "SELECT * FROM allowed_ips ORDER BY created_at DESC");

require "settings_view.php";

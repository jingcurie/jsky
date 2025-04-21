<?php
require_once __DIR__ . '/config.php';
require_once INCLUDE_PATH . '/db.php';
require_once INCLUDE_PATH . '/functions.php';

// 工具函数：判断 IP 是否在 CIDR 段中
function ipInCidr($ip, $cidr) {
    if (strpos($cidr, '/') === false) {
        return $ip === $cidr;
    }

    list($subnet, $mask) = explode('/', $cidr);
    return (ip2long($ip) & ~((1 << (32 - $mask)) - 1)) === (ip2long($subnet) & ~((1 << (32 - $mask)) - 1));
}

// 判断是否启用 IP 白名单
$setting = query($conn, "SELECT setting_value FROM site_settings WHERE setting_key = 'ip_whitelist_enabled' LIMIT 1");
if (!empty($setting) && intval($setting[0]['setting_value']) === 1) {
    $userIp = $_SERVER['REMOTE_ADDR'];

    // 从 allowed_ips 表中查询所有启用的 IP 或段（可加 is_active 字段判断）
    $rows = query($conn, "SELECT ip_address FROM allowed_ips");

    $allowed = false;
    foreach ($rows as $row) {
        $entry = trim($row['ip_address']);
        if ($entry && ipInCidr($userIp, $entry)) {
            $allowed = true;
            break;
        }
    }

    if (!$allowed) {
        http_response_code(403);
        echo "<h1>403 Forbidden</h1><p>您的 IP 地址未被授权访问后台。</p>";
        exit;
    }
}
?>

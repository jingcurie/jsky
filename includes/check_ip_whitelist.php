<?php
require_once __DIR__ . '/config.php';
require_once INCLUDE_PATH . '/db.php';
require_once INCLUDE_PATH . '/functions.php';

// 工具函数：判断 IP 是否在 CIDR 段中
function ipInCidr($ip, $cidr)
{
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
        die('
        <!DOCTYPE html>
        <html lang="zh">
        <head>
            <meta charset="UTF-8">
            <title>系统提示</title>
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <link href="/assets/css/bootstrap.min.css" rel="stylesheet">
            <link href="/assets/css/all.min.css" rel="stylesheet">
            <style>
                body { background-color: #f8f9fa; }
                .msg-card {
                    max-width: 460px;
                    margin: auto;
                    margin-top: 12vh;
                    padding: 2rem;
                    border-radius: 10px;
                    background: #fff;
                    box-shadow: 0 0 10px rgba(0,0,0,0.05);
                    text-align: center;
                }
                .msg-icon {
                    font-size: 3rem;
                }
            </style>
        </head>
        <body>
            <div class="msg-card">
                <div class="msg-icon mb-3">
                    <i class="fas fa-times-circle text-danger"></i>
                </div>
                <h4 class="text-danger">系统提示：警告</h4>
                <h1>403 Forbidden</h1>
                <p>非法IP不可访问</p>
            </div>
        </body>
        </html>');
        exit;
    }
}

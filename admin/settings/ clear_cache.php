<?php
// admin/clear_cache.php

require_once __DIR__ . '/../../includes/config.php';
require_once INCLUDE_PATH . '/auth.php';

// 设置 JSON 响应头
header('Content-Type: application/json');

// 登录验证
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => '未登录或登录超时']);
    exit;
}

// 你希望清理的缓存目录，例如 /cache/
$cacheDir = __DIR__ . '/../../cache/'; // 假设你有一个 cache 目录
if (!is_dir($cacheDir)) {
    echo json_encode(['success' => false, 'message' => '缓存目录不存在']);
    exit;
}

function deleteCacheFiles($dir) {
    $files = glob($dir . '*');
    $deleted = 0;
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
            $deleted++;
        }
    }
    return $deleted;
}

try {
    $count = deleteCacheFiles($cacheDir);
    log_operation($conn, $_SESSION['user_id'], $_SESSION['username'], '清除缓存', '性能设置', null, "共清除 {$count} 个缓存文件");
    echo json_encode(['success' => true, 'message' => "清除缓存成功，{$count} 个文件被删除"]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

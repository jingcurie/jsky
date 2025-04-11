<?php
require_once __DIR__ . '/../includes/config.php';
require INCLUDE_PATH . '/db.php';
require INCLUDE_PATH . '/auth.php';
require INCLUDE_PATH . '/functions.php';

if (!isLoggedIn()) {
    redirect('/admin/login.php');
}

// 文件缓存配置
$cache_dir = __DIR__ . '/../cache/';
$cache_file = $cache_dir . 'visit_stats_' . date('Y-m-d') . '.json';
$cache_ttl = 3600; // 1小时缓存

// 创建缓存目录（如果不存在）
if (!file_exists($cache_dir)) {
    mkdir($cache_dir, 0755, true);
}

// 尝试从文件缓存读取
// if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_ttl) {
//     header('Content-Type: application/json');
//     readfile($cache_file);
//     exit;
// }

try {  
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 强制设置时区（解决所有日期问题）
    $conn->exec("SET time_zone = '+08:00'");
 
    // 1. 获取基础统计（优化为单次查询）
    $stmt = $conn->query("
        SELECT 
            (SELECT COUNT(*) FROM visit_logs WHERE DATE(visited_at) = CURDATE()) as today_visits,
            (SELECT COUNT(DISTINCT ip_address) FROM visit_logs WHERE DATE(visited_at) = CURDATE()) as unique_visitors,
            (SELECT COUNT(*) FROM articles WHERE status = 'draft') as pending_articles
    ");
    $basic_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. 获取设备类型统计（关键修正）
    $stmt_devices = $conn->query("
        SELECT 
            device_type,
            COUNT(*) as total_count
        FROM visit_logs
        WHERE DATE(visited_at) = CURDATE()
        GROUP BY device_type
    ");
    $device_counts = $stmt_devices->fetchAll(PDO::FETCH_KEY_PAIR);

    // 3. 获取每日设备数据（绝对准确）
    $stmt_daily = $conn->query("
        SELECT 
            device_type,
            DATE(visited_at) as date,
            COUNT(*) as count
        FROM visit_logs
        WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)
        GROUP BY device_type, date
        ORDER BY date DESC
    ");
    $device_by_day = [];
    foreach ($stmt_daily as $row) {
        $device_by_day[$row['device_type']][$row['date']] = (int)$row['count'];
    }

    // 4. 获取趋势数据（过去7天）
    $stmt_trend = $conn->query("
        SELECT 
            DATE(visited_at) as date,
            COUNT(*) as count
        FROM visit_logs
        WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        GROUP BY date
        ORDER BY date ASC
    ");
    $trend_data = $stmt_trend->fetchAll(PDO::FETCH_ASSOC);

    // 5. 获取每小时数据（仅今天）
    $stmt_hourly = $conn->query("
        SELECT 
            HOUR(visited_at) as hour,
            COUNT(*) as count
        FROM visit_logs
        WHERE DATE(visited_at) = CURDATE()
        GROUP BY hour
        ORDER BY hour ASC
    ");
    $hourly_data = $stmt_hourly->fetchAll(PDO::FETCH_ASSOC);

    // 构建最终数据
    $output_data = [
        'today_visits' => $basic_stats['today_visits'],
        'unique_visitors' => $basic_stats['unique_visitors'],
        'pending_articles' => $basic_stats['pending_articles'],
        'device_counts' => $device_counts ?: ['Mobile' => 0, 'Desktop' => 0],
        'trend_data' => $trend_data,
        'hourly_data' => $hourly_data,
        'device_by_day' => $device_by_day
    ];

    // 写入缓存并输出
    file_put_contents($cache_file, json_encode($output_data));
    header('Content-Type: application/json');
    echo json_encode($output_data);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
<?php
header("Content-Type: text/html; charset=utf-8");

$apiKey = "1931f28b0c029fab895769281d97a27e"; // 请替换为你的高德API Key
$city = "上海"; // 固定查询上海公交
$busLine = $_GET['line'] ?? '';

if (!$busLine) {
    echo "<p>请输入公交线路号！</p>";
    exit;
}

// 调用高德公交查询 API
$url = "https://restapi.amap.com/v3/bus/linename?key=$apiKey&city=$city&keywords=" . urlencode($busLine) . "&extensions=all";

$response = file_get_contents($url);
$data = json_decode($response, true);
print_r($data);

if ($data["status"] != "1" || empty($data["buslines"])) {
    echo "<p>未找到相关公交线路，请检查线路号！</p>";
    exit;
}

// 解析返回数据
$bus = $data["buslines"][0];
echo "<h3>{$bus['name']}</h3>";
echo "<p>首班车: {$bus['start_time']} | 末班车: {$bus['end_time']}</p>";
echo "<p>全程约 {$bus['length']} 公里 | 票价：{$bus['basic_price']} 元</p>";
echo "<h4>途经站点：</h4><ul>";

foreach ($bus["stations"] as $station) {
    echo "<li>{$station['name']}</li>";
}
echo "</ul>";
?>

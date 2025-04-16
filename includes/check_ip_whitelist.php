<?php
$userIp = $_SERVER['REMOTE_ADDR'];

// 查询白名单数据库表
$stmt = $conn->prepare("SELECT COUNT(*) FROM allowed_ips WHERE ip_address = ?");
$stmt->execute([$userIp]);
$ipExists = $stmt->fetchColumn();

// if (!$ipExists) {
//     http_response_code(403); // 设置 HTTP 状态码
//     echo "<h1>403 Forbidden</h1><p>您的 IP 地址未被授权访问后台。</p>";
//     exit;
// }
?>
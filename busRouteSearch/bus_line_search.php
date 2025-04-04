<?php
// 启用错误报告，方便调试
error_reporting(E_ALL);
ini_set('display_errors', 1);



// Excel 文件存储目录
$excelDir = __DIR__ . "/lineData";

// 处理 AJAX 请求
$action = $_GET["action"] ?? $_POST["action"] ?? null;

// **获取所有线路名称**
if ($action === "getLines") {
    
    $files = array_diff(scandir($excelDir), ['.', '..']);
    $lines = [];
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === "xlsx" && !preg_match('/^[.~]/', $file)) {
            $lines[] = pathinfo($file, PATHINFO_FILENAME);
        }
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($lines, JSON_UNESCAPED_UNICODE);
    exit;
}

// **获取站点信息**
// 引入 SimpleXLSX
require 'SimpleXLSX.php';
use Shuchkin\SimpleXLSX;
if ($action === "getStations") {

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        echo json_encode(["error" => "Invalid request method"]);
        exit;
    }

    $lineName = $_POST["line"] ?? "";
    $direction = $_POST["direction"] ?? ""; // "up" 或 "down"
    $filePath = $excelDir . "/" . $lineName . ".xlsx";

    if (!file_exists($filePath)) {
        echo json_encode(["error" => "文件不存在"]);
        exit;
    }

    // **解析 Excel**
    $xlsx = SimpleXLSX::parse($filePath);
    if (!$xlsx) {
        echo json_encode(["error" => SimpleXLSX::parseError()]);
        exit;
    }

    $rows = $xlsx->rows();
    $upStations = [];
    $downStations = [];
    $upStartRow = null;
    $downStartRow = null;
    $upColumnIndex = null;
    $downColumnIndex = null;

    // **1️⃣ 找到 "上行站名" 和 "下行站名" 的起始行**
    foreach ($rows as $rowIndex => $row) {
        foreach ($row as $colIndex => $cell) {
            $cell = trim((string)$cell);
            if (strpos($cell, "上行站名") !== false) {
                $upStartRow = $rowIndex + 1;
                $upColumnIndex = $colIndex;
            }
            if (strpos($cell, "下行站名") !== false) {
                $downStartRow = $rowIndex + 1;
                $downColumnIndex = $colIndex;
            }
        }
    }

    // **2️⃣ 提取上行站点**
    if ($upStartRow !== null) {
        for ($i = $upStartRow; $i < count($rows); $i++) {
            $station = trim((string)$rows[$i][$upColumnIndex] ?? ""); // 取第一列数据
            if ($station === "" || strpos($station, "走向") !== false || strpos($station, "是否无人售票") !== false) {
                break;
            }
            $upStations[] = $station;
        }
    }

    // **3️⃣ 提取下行站点**
    if ($downStartRow !== null) {
        for ($i = $downStartRow; $i < count($rows); $i++) {
            $station = trim((string)$rows[$i][$downColumnIndex] ?? ""); // 取第一列数据
            if ($station === "" || strpos($station, "走向") !== false || strpos($station, "是否无人售票") !== false) {
                break;
            }
            $downStations[] = $station;
        }
    }

    // **返回 JSON 结果**
    echo json_encode([
        "upStations" => $upStations,
        "downStations" => $downStations
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

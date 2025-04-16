<?php
// 通用查询函数
function query($conn, $sql, $params = [])
{
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 通用执行函数（用于插入、更新、删除）
function execute($conn, $sql, $params = [])
{
    $stmt = $conn->prepare($sql);
    return $stmt->execute($params);
}

// 获取表中所有数据
function getAll($conn, $table)
{
    $sql = "SELECT * FROM $table";
    return query($conn, $sql);
}

// 根据 ID 获取单条数据
function getById($conn, $table, $idField, $id)
{
    $sql = "SELECT * FROM $table WHERE $idField = ?";
    return query($conn, $sql, [$id])[0] ?? null;
}

// 插入数据
function insert($conn, $table, $data)
{
    $columns = implode(', ', array_keys($data));
    $values = implode(', ', array_fill(0, count($data), '?'));
    $sql = "INSERT INTO $table ($columns) VALUES ($values)";
    return execute($conn, $sql, array_values($data));
}

// 更新数据
function update($conn, $table, $idField, $id, $data)
{
    $set = implode(', ', array_map(function ($key) {
        return "$key = ?";
    }, array_keys($data)));
    $sql = "UPDATE $table SET $set WHERE $idField = ?";
    $params = array_merge(array_values($data), [$id]);
    return execute($conn, $sql, $params);
}

// 删除数据
function delete($conn, $table, $idField, $id)
{
    $sql = "DELETE FROM $table WHERE $idField = ?";
    return execute($conn, $sql, [$id]);
}

// 检查字段值是否已存在
function isFieldValueExists($conn, $table, $field, $value, $excludeId = null, $idField = 'id')
{
    $sql = "SELECT * FROM $table WHERE $field = ?" . ($excludeId ? " AND $idField != ?" : "");
    $params = [$value];
    if ($excludeId) {
        $params[] = $excludeId;
    }
    return !empty(query($conn, $sql, $params));
}

// 获得网站菜单内容
function getMenus($pdo)
{
    $sql = "SELECT * FROM menu WHERE is_active = 1 ORDER BY COALESCE(parent_id, 0), sort_order;";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 重定向函数
function redirect($url)
{
    header("Location: $url");
    exit;
}

//摘取摘要
function getSummary($content)
{
    $content = htmlspecialchars_decode($content);

    // 提取所有 <p> 标签内容
    preg_match_all('/<p[^>]*>(.*?)<\/p>/s', $content, $matches);

    $texts = [];
    $count = 0;
    foreach ($matches[1] as $paragraph) {
        // 判断第一段是否仅包含 <img>
        if ($count == 0 && preg_match('/^\s*<img[^>]+>\s*$/i', $paragraph)) {
            return $paragraph; // 第一段是图片，直接返回图片
        }

        // 去掉 HTML 标签，获取纯文本
        $texts[] = strip_tags($paragraph);
        $count++;

        if ($count >= 2) {
            break; // 只获取前 2 段非图片文本
        }
    }

    return implode(" ", $texts);
}

//获得所有settings表的数据，不能用getById function
function getSettings($conn) {
    $rows = query($conn, "SELECT * FROM site_settings");
    $settings = [];
    foreach ($rows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    return $settings;
}

//以下是删除coverimage和richtext中图片当我们删除文章时
function deleteArticleWithImages($conn, $articleId) {
    $article = getById($conn, 'articles', 'id', $articleId);
    if (!$article) {
        error_log("Article not found: " . $articleId);
        return false;
    }
    
    // 删除封面图片（使用你的ARTICLE_PATH常量）
    if (!empty($article['cover_image'])) {
        $coverPath = ARTICLE_PATH . $article['cover_image'];
        if (file_exists($coverPath) && isSafeToDelete($coverPath)) {
            if (!@unlink($coverPath)) {
                error_log("Failed to delete cover image: " . $coverPath);
            }
        }
    }
    
    // 删除富文本图片
    if (!empty($article['content'])) {
        // 在解析富文本图片前添加
        $decodedContent = htmlspecialchars_decode($article['content']);
        $pattern = '/<img\s+[^>]*src\s*=\s*(["\']?)(?!data:)([^"\'>\s]+)\1/i';
        preg_match_all($pattern, $decodedContent, $matches);

        if (!empty($matches[2])) {
            foreach ($matches[2] as $imgSrc) {
                $imgPath = resolveImagePath($imgSrc);
                
                if ($imgPath && file_exists($imgPath)) {
                    if (isSafeToDelete($imgPath)) {
                        if (!@unlink($imgPath)) {
                            error_log("Failed to delete content image: " . $imgPath);
                        }
                    }
                }
            }
        }
    }
    
    return delete($conn, 'articles', 'id', $articleId);
}

function resolveImagePath($imgSrc) {
    // 移除URL参数和锚点
    $imgSrc = strtok($imgSrc, '?#');
    
    // 处理相对路径 (如../../uploads/articles/)
    if (strpos($imgSrc, '../') === 0) {
        $baseDir = dirname(dirname(ARTICLE_PATH)); // 上两级目录
        $relativePath = substr($imgSrc, strpos($imgSrc, 'uploads/'));
        return $baseDir . '/' . $relativePath;
    }
    
    // 处理绝对路径 (如/uploads/articles/)
    if (strpos($imgSrc, '/uploads/') === 0) {
        return realpath(__DIR__ . '/..' . $imgSrc); // 转到项目根目录
    }
    
    // 处理完整URL (如http://yoursite.com/uploads/articles/)
    if (strpos($imgSrc, 'http') === 0) {
        $parsed = parse_url($imgSrc);
        if (strpos($parsed['path'], '/uploads/') !== false) {
            return realpath(__DIR__ . '/..' . $parsed['path']);
        }
        return false;
    }
    
    // 默认情况（直接位于articles目录下）
    return ARTICLE_PATH . ltrim($imgSrc, './');
}

function isSafeToDelete($path) {
    $allowedPaths = [
        realpath(ARTICLE_PATH),
        realpath(dirname(dirname(ARTICLE_PATH)) . '/uploads/articles') // 处理../情况
    ];
    
    $realPath = realpath($path);
    if ($realPath === false) return false;
    
    foreach ($allowedPaths as $allowed) {
        if (strpos($realPath, $allowed) === 0) {
            return true;
        }
    }
    
    error_log("Unsafe deletion attempt: " . $path);
    return false;
}

//操作日志
function parse_user_agent($ua) {
    $os = 'Unknown OS';
    $device = 'Unknown Device';

    // 简单示例：可扩展更详细规则
    if (preg_match('/Windows NT 10.0/', $ua)) $os = 'Windows 10';
    elseif (preg_match('/Windows NT 6.1/', $ua)) $os = 'Windows 7';
    elseif (preg_match('/Mac OS X/', $ua)) $os = 'macOS';
    elseif (preg_match('/Linux/', $ua)) $os = 'Linux';
    elseif (preg_match('/Android/', $ua)) $os = 'Android';
    elseif (preg_match('/iPhone/', $ua)) $os = 'iOS';

    if (preg_match('/Chrome\/[\d.]+/', $ua, $match)) $device = $match[0];
    elseif (preg_match('/Firefox\/[\d.]+/', $ua, $match)) $device = $match[0];
    elseif (preg_match('/Safari\/[\d.]+/', $ua, $match)) $device = $match[0];
    elseif (preg_match('/MSIE\s[\d.]+/', $ua, $match)) $device = $match[0];

    return [$device, $os];
}

// 操作日志
function log_operation($pdo, $user_id, $username, $action, $module, $object_id = null, $description = null) {
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    list($device, $os) = parse_user_agent($ua);
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    // 使用 insert 封装函数插入数据
    $data = [
        'user_id' => $user_id,
        'username' => $username,
        'action' => $action,
        'module' => $module,
        'object_id' => $object_id,
        'description' => $description,
        'ip_address' => $ip,
        'device' => $device,
        'os' => $os
    ];

    return insert($pdo, 'log_operation', $data); // 调用通用的 insert 函数
}

// 登录日志
function log_login($pdo, $user_id, $username, $status, $msg = null) {
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    list($device, $os) = parse_user_agent($ua);
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $location = "未知位置";//get_ip_location($ip);  // 可通过第三方API进行IP解析来填充

    // 使用 insert 封装函数插入数据
    $data = [
        'user_id' => $user_id,
        'username' => $username,
        'ip_address' => $ip,
        'location' => $location,
        'device' => $device,
        'os' => $os,
        'status' => $status,
        'msg' => $msg
    ];

    return insert($pdo, 'log_login', $data); // 调用通用的 insert 函数
}

//分析ip位置
// function get_ip_location($ip) {
//     $url = "http://ip-api.com/json/{$ip}?lang=zh-CN";
//     $response = @file_get_contents($url);
//     if ($response) {
//         $data = json_decode($response, true);
//         if ($data && $data['status'] === 'success') {
//             return "{$data['country']} {$data['regionName']} {$data['city']}";
//         }
//     }
//     return '未知位置';
// }

function renderTable($data, $headers, $idField, $actions = null) {
    echo '<table class="table table-bordered table-hover">';
    echo '<thead><tr>';
    echo '<th>#</th>';
    foreach ($headers as $field => $title) {
        echo "<th>{$title}</th>";
    }
    echo '<th>操作</th>';
    echo '</tr></thead>';
    echo '<tbody>';

    $count = 0;
    foreach ($data as $row) {
        $count++;
        echo '<tr>';
        echo "<td>{$count}</td>";

        foreach ($headers as $field => $title) {
            $value = isset($row[$field]) ? htmlspecialchars($row[$field]) : '';
            echo "<td>{$value}</td>";
        }

        // 操作列
        echo '<td>';
        if ($actions) {
            foreach ($actions as $action) {
                if ($action['type'] === 'link') {
                    // 普通链接操作
                    $url = "{$action['url']}?id={$row[$idField]}";
                    echo "<a href='{$url}' class='btn btn-sm {$action['class']}'><i class='{$action['icon']}'></i> {$action['label']}</a> ";
                } elseif ($action['type'] === 'js') {
                    // JS 调用形式
                    $jsFunc = $action['js_func'];
                    $label = $action['label'];
                    $icon = $action['icon'];
                    $btnClass = $action['class'];
                    $args = array_map(function($argKey) use ($row) {
                        return isset($row[$argKey]) ? htmlspecialchars($row[$argKey]) : '';
                    }, $action['js_args']);
                    $jsArgsStr = implode("','", $args);

                    echo "<button class='btn btn-sm {$btnClass}' onclick=\"{$jsFunc}('{$jsArgsStr}')\">";
                    echo "<i class='{$icon}'></i> {$label}</button> ";
                }
            }
        }
        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
}

function system_message(string $message = '提示内容', string $type = 'danger', string $title = '系统提示', string $icon = 'fas fa-info-circle') {
    die('
    <!DOCTYPE html>
    <html lang="zh">
    <head>
        <meta charset="UTF-8">
        <title>' . htmlspecialchars($title) . '</title>
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
                color: var(--bs-' . $type . ');
            }
        </style>
    </head>
    <body>
        <div class="msg-card">
            <div class="msg-icon mb-3">
                <i class="' . htmlspecialchars($icon) . ' text-' . $type . '"></i>
            </div>
            <h4 class="text-' . $type . '">' . htmlspecialchars($title) . '</h4>
            <p class="text-muted mb-4">' . htmlspecialchars($message) . '</p>
            
        </div>
    </body>
    </html>');
}


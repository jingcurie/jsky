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
function getSummary($matches, $content)
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

function getSettings($conn) {
    $rows = query($conn, "SELECT * FROM site_settings");
    $settings = [];
    foreach ($rows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    return $settings;
}

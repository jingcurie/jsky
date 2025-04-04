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

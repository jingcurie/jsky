<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

header("Content-Type: application/json");

// 获取菜单列表（树形结构）
if ($_GET['action'] === 'fetch') {
    echo json_encode(getMenuTree($conn));
    exit;
}

if ($_GET['action'] === 'categories') {
    $categories = query($conn, "SELECT id, name FROM categories ORDER BY name");
    echo json_encode($categories);
    exit;
}


// 获取单个菜单信息（用于编辑）
if ($_GET['action'] === 'get' && isset($_GET['id'])) {
    $menu = getById($conn, 'menu', 'id', $_GET['id']);
    echo json_encode($menu);
    exit;
}

// 保存（新增或修改）菜单
if ($_GET['action'] === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $data = [
        'name' => trim($_POST['name']),
        'url' => trim($_POST['url']),
        'parent_id' => $_POST['parent_id'] ?: null,
        'sort_order' => $_POST['sort_order'],
        'is_active' => $_POST['is_active'] ? 1 : 0,
        'article_category_id' => !empty($_POST['article_category_id']) ? $_POST['article_category_id'] : null,
        'menu_type' => $_POST['menu_type'] ?? 'non-article'
    ];

    if ($data['menu_type'] === 'article') {
        $data['url'] = 'showSubMenuPage.php';
    }

    if ($id) {
        update($conn, 'menu', 'id', $id, $data);
    } else {
        insert($conn, 'menu', $data);
    }
    echo json_encode(["success" => true]);
    exit;
}

// 删除菜单（级联删除）
if ($_GET['action'] === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    if (!$id) {
        echo json_encode(["error" => "缺少菜单 ID"]);
        exit;
    }
    delete($conn, 'menu', 'id', $id);
    echo json_encode(["success" => true]);
    exit;
}

// 默认返回错误
echo json_encode(["error" => "无效请求"]);
exit;

// 递归获取菜单树
function getMenuTree($conn, $parentId = null)
{
    $menus = query($conn, "SELECT * FROM menu WHERE parent_id " . ($parentId === null ? "IS NULL" : "= ?") . " ORDER BY sort_order, id", $parentId === null ? [] : [$parentId]);

    foreach ($menus as &$menu) {
        $menu['children'] = getMenuTree($conn, $menu['id']);
    }
    return $menus;
}

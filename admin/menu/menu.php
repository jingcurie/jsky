<?php
require_once __DIR__ . '/../../includes/config.php';
require INCLUDE_PATH . '/db.php';
require INCLUDE_PATH . '/auth.php';
require INCLUDE_PATH . '/functions.php';

if (!isLoggedIn()) {
    redirect('/admin/login.php');
}

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
        $data['url'] = '/pages/showSubMenuPage.php';
    }

    if ($id) {
        update($conn, 'menu', 'id', $id, $data);
        log_operation($conn, $_SESSION['user_id'], $_SESSION['username'], '更新', '菜单管理', $id, $_POST['name']);
    } else {
        insert($conn, 'menu', $data);
        log_operation($conn, $_SESSION['user_id'], $_SESSION['username'], '创建', '菜单管理', null, $_POST['name']);
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
    $menu = getById($conn, "menu", "id", $id);
    log_operation($conn, $_SESSION['user_id'], $_SESSION['username'], '删除', '菜单管理', $id, $menu["name"]);
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

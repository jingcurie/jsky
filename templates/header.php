<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDE_PATH . '/db.php';
require_once INCLUDE_PATH . '/auth.php';
require_once INCLUDE_PATH . '/functions.php';
// require_once __DIR__ . '/../includes/config.php';
// require_once __DIR__ . '/../includes/db.php';
// require_once __DIR__ . '/../includes/functions.php';

$settings = getSettings($conn);
// 获取菜单数据
$menus = getMenus($conn);

function renderMenu($menus, $parent_id = 0, $level = 0)
{
    $filteredMenus = array_filter($menus, fn($menu) => $menu['parent_id'] == $parent_id);
    if (empty($filteredMenus)) return '';

    $ulClass = ($level === 0) ? 'menu' : 'submenu';
    $output = "<ul class=\"$ulClass\">";

    foreach ($filteredMenus as $menu) {
        $submenu = renderMenu($menus, $menu['id'], $level + 1);
        $hasSubmenu = !empty($submenu);
        $dropdownClass = $hasSubmenu ? ' class="dropdown"' : '';

        // 处理 URL
        if (!empty($menu['url'])) {
            // 检查是否有子菜单
            $hasChildren = array_filter($menus, fn($m) => $m['parent_id'] == $menu['id']);

            // 计算 menu_id
            if (!empty($menu['parent_id'])) {
                // 有父菜单
                $menu_id = $hasChildren ? $menu['id'] : $menu['parent_id'];
            } else {
                // 没有父菜单，也就是一级菜单
                $menu_id = $menu['id'];
            }

            // 如果 URL 是 showSubMenuPage.php，需要根据 menu_type 追加参数
            if ($menu['url'] === '/pages/showSubMenuPage.php') {
                if (!empty($menu['article_category_id'])) {
                    $url =  $menu['url'] . "?article_category_id=" . urlencode($menu['article_category_id']) . "&menu_id=" . urlencode($menu_id);
                } else {
                    $url =  $menu['url'] . "?menu_id=" . urlencode($menu_id);
                }
            } else {
                $url =  $menu['url']; // 其他情况保持原样
            }
        } else {
            $url = "#"; // 默认值
        }



        $output .= "<li{$dropdownClass}>";
        $output .= "<a href=\"" . htmlspecialchars($url) . "\">" . htmlspecialchars($menu['name']) . "</a>";
        if ($hasSubmenu) $output .= $submenu;
        $output .= '</li>';
    }

    $output .= '</ul>';
    return $output;
}

?>

<!DOCTYPE html>
<html lang="zh">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="keywords" content="<?= htmlspecialchars($settings['meta_keywords']) ?>">
    <meta name="description" content="<?= htmlspecialchars($settings['meta_description']); ?>">
    <title><?= htmlspecialchars($settings['site_title']); ?></title>
    <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"> -->
    <link rel="stylesheet" href="/assets/css/all.min.css">
    <link rel="icon" type="image/png" href="<?= IMG_URL ?>/favicon.png">
    <link href="<?= CSS_URL ?>/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= CSS_URL ?>/main.css">
    <?php if (isset($current_page_css)) : ?>
        <link rel="stylesheet" href="<?php echo $current_page_css; ?>"> <!-- 动态加载特定页面的 CSS -->
    <?php endif; ?>

    <!-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> -->
    <script src="/assets/js/jquery-3.6.0.min.js"></script>
    <script src="<?= JS_URL ?>/aos.js"></script>
    <!-- 当前页面的额外 CSS 和 JS -->
    <?php echo $extraHead ?? ''; ?>
</head>

<body>
    <header>
        <div class="header-container">
            <div class="logo">
                <h1><a href="/" onclick="handleLogoClick(event)"><img src="<?= IMG_URL ?>/jinshan_logo.png" alt="公司logo"></a></h1>
            </div>

            <script>
                function handleLogoClick(event) {
                    // 如果按住Shift键，跳转/admin
                    if (event.shiftKey) {
                        location.href = '/admin';
                        return false; // 阻止默认跳转行为
                    }
                    // 否则不做干预，默认跳转href="/"
                }
            </script>

            <div class="header-right">
                <div class="search-box">
                    <input type="text" id="search" placeholder="搜索...">
                    <a href="search.php"><i class="fas fa-search"></i></a>
                </div>
                <!-- <div class="user-login">
                    <?php if (isset($_SESSION['username'])): ?>
                        <span>👤 <?php echo $_SESSION['username']; ?></span>
                        <a href="/admin/logout.php">退出</a>
                    <?php else: ?>
                        <a href="/admin/login.php"><i class="fas fa-user"></i> 登录</a>
                    <?php endif; ?>
                </div> -->
                <div class="menu-toggle" onclick="toggleMenu()">
                    <span id="menu-icon">&#9776;</span>
                </div>
            </div>
        </div>
    </header>

    <nav>
        <!-- <div class="menu-toggle" onclick="toggleMenu()">☰</div> -->
        <?php echo renderMenu($menus); ?>
    </nav>

    <script>
        function toggleMenu() {
            const menu_icon = document.querySelector('#menu-icon');
            const menu = document.querySelector('.menu');
            menu.classList.toggle('active');
            menu_icon.classList.toggle('active');
            if (menu_icon.classList.contains('active')) {
                menu_icon.innerHTML = '&#10005;'; // 叉叉符号
                menu_icon.style.color = '#FFFFFF';
            } else {
                menu_icon.innerHTML = '&#9776;'; // 汉堡包符号
                menu_icon.style.color = '#000000';
            }
        }

        function toggleDropdown(element) {
            element.classList.toggle('active');
        }
    </script>
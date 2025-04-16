<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/config.php';
require_once INCLUDE_PATH . '/db.php';
require_once INCLUDE_PATH . '/check_ip_whitelist.php';
require_once INCLUDE_PATH . '/auth.php';
require_once INCLUDE_PATH . '/functions.php';

if (!isLoggedIn()) {
    redirect('/admin/login.php');
}

// 获取当前用户的角色 ID
$user_role_id = $_SESSION['role_id'] ?? null;


// 获取当前用户有权限访问的模块
$stmt = $conn->prepare("
    SELECT m.module_name, m.module_icon, m.module_url FROM role_permissions rp
    JOIN modules m ON rp.module_id = m.module_id
    WHERE rp.role_id = ? AND m.is_deleted = 0
    ORDER BY m.module_order ASC
");
$stmt->execute([$user_role_id]);
$modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 文章分类
$category_stmt = $conn->query("SELECT id, name FROM categories WHERE is_deleted = 0 ORDER BY name ASC");
$categories = $category_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="zh">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="icon" type="image/png" href="/assets/images/favicon.png">
    <link href="/assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/all.min.css">
    <link href="<?= CSS_URL ?>/admin_style.css" rel="stylesheet">
</head>

<body>
    <div class="logo-frame">
        <span>上海锦山汽车客运有限公司网站管理中心</span>
    </div>

    <div class="sidebar" id="sidebar">
        <!-- <div class="top-bar">
            <div class="welcome-message">
                <i class="fas fa-user-circle"></i>
                <span id="welcome-text">欢迎回来，<?= htmlspecialchars($_SESSION['username']) ?></span>
                <span id="current-time"></span>
            </div>
        </div> -->
        <div class="hamburger">
            <a href="/admin"><img src="/assets/images/jinshan_logo2.png" alt="Logo" class="logo"></a> <!-- 👈 换成你的 logo 路径 -->
            <div class="toggle-btn" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </div>
        </div>
        <div class="welcome-message">
            <div class="avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="welcome-text">
                <div id="welcome-text">欢迎您，<?= htmlspecialchars($_SESSION['username']) ?></div>
                <div id="current-time">2025-04-05</div>
            </div>
        </div>

        <div class="menu-scroll">
            <ul class="menu list-unstyled">
                <?php foreach ($modules as $module): ?>
                    <li <?php if ($module['module_name'] === '文章管理'): ?> onmouseover="showFloatingMenu(event)"
                        onmouseleave="hideFloatingMenu()" onclick="toggleCategoryList();" <?php else: ?>
                        onclick="loadPage('<?php echo htmlspecialchars($module['module_url']); ?>', this);" <?php endif; ?>>
                        <i class="fas <?php echo htmlspecialchars($module['module_icon']); ?>"></i>
                        <span><?php echo htmlspecialchars($module['module_name']); ?></span>
                        <?php if ($module['module_name'] === '文章管理'): ?>
                            <i class="fas fa-chevron-down ms-auto toggle-icon" id="categoryArrow"></i>
                        <?php endif; ?>
                    </li>
                    <?php if ($module['module_name'] === '文章管理'): ?>
                        <ul class="category-list list-unstyled" id="categoryList">
                            <?php foreach ($categories as $category): ?>
                                <li
                                    onclick="loadPage('article/articles.php?category_id=<?= htmlspecialchars($category['id']) ?>', this);">
                                    <i class="fas fa-folder"></i> <?= htmlspecialchars($category['name']) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                <?php endforeach; ?>
                <li>
                    <a href="/helpDocs/index.html" target="_blank">
                        <i class="fas fa-question-circle"></i> 帮助中心
                    </a>
                </li>
                <li><a href="logout.php" style="text-decoration:none;"><i
                            class="fas fa-right-from-bracket"></i><span>退出系统</span>
                    </a></li>
            </ul>
        </div>


    </div>

    <div class="main-content">

        <iframe id="mainFrame" src="dashboard.php" frameborder="0" width="100%" height="100%">
        </iframe>
    </div>

    <div class="floating-submenu" id="floatingSubmenu">
        <ul>
            <?php foreach ($categories as $category): ?>
                <li onclick="loadPage('article/articles.php?category_id=<?= htmlspecialchars($category['id']) ?>');">
                    <?= htmlspecialchars($category['name']) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <script>
        function loadPage(page, element) {
            let iframe = document.getElementById('mainFrame');
            iframe.src = page + '?t=' + new Date().getTime(); // 防缓存

            // 高亮主菜单项
            document.querySelectorAll('.menu li').forEach(item => item.classList.remove('active'));
            if (element) {
                element.classList.add('active');
            }

            // 👉 收缩状态下点击浮动菜单后隐藏它
            let sidebar = document.getElementById("sidebar");
            if (sidebar.classList.contains("collapsed")) {
                document.getElementById("floatingSubmenu").style.display = "none";
            }
        }

        function toggleSidebar() {
            let sidebar = document.getElementById("sidebar");
            //let welcomeMessage = document.querySelector(".welcome-message");
            sidebar.classList.toggle('collapsed');
        }

        function toggleCategoryList() {
            let categoryList = document.getElementById("categoryList");
            // categoryList.style.display = (categoryList.style.display === "block") ? "none" : "block";
            let arrowIcon = document.getElementById("categoryArrow");

            // 检查子菜单当前状态并切换显示和箭头方向
            if (categoryList.classList.contains("show")) {
                categoryList.classList.remove("show");
                arrowIcon.classList.remove("up");
            } else {
                categoryList.classList.add("show");
                arrowIcon.classList.add("up");
            }
        }

        function showFloatingMenu(event) {
            let menu = document.getElementById("floatingSubmenu");
            let sidebar = document.getElementById("sidebar");

            if (!sidebar.classList.contains("collapsed")) return; // 只有在收缩状态下才弹出

            let target = event.target.closest("li");
            let rect = target.getBoundingClientRect();

            menu.style.display = "block";
            menu.style.top = rect.top + "px"; // 让子菜单与“文章管理”对齐
            menu.style.left = rect.right + "px"; // 让子菜单出现在右侧
        }

        function hideFloatingMenu() {
            setTimeout(() => {
                let menu = document.getElementById("floatingSubmenu");
                if (!menu.matches(":hover")) { // 只有当鼠标离开图标和子菜单区域时才隐藏
                    menu.style.display = "none";
                }
            }, 200);
        }

        function updateTime() {
            let now = new Date();
            let timeString = now.toLocaleString('zh-CN', {
                hour12: false
            });
            document.getElementById("current-time").innerText = timeString;
        }
        setInterval(updateTime, 1000); // 每秒刷新一次
        updateTime(); // 先手动调用一次
    </script>
</body>

</html>
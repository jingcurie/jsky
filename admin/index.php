<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require '../includes/db.php';
require '../includes/auth.php';
require '../includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// 获取当前用户的角色 ID
$user_role_id = $_SESSION['role_id'] ?? null;


// 获取当前用户有权限访问的模块
$stmt = $conn->prepare("
    SELECT m.module_name, m.module_icon, m.module_url FROM role_permissions rp
    JOIN modules m ON rp.module_id = m.module_id
    WHERE rp.role_id = ?
    ORDER BY m.module_order ASC
");
$stmt->execute([$user_role_id]);
$modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 文章分类
$category_stmt = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
$categories = $category_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="zh">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
        }

        .logo-frame {
            height: 60px;
            background-color: #222;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            font-weight: bold;
            position: fixed;
            width: 100%;
        }

        .sidebar {
            width: 250px;
            height: calc(100vh - 60px);
            background-color: #343a40;
            color: white;
            position: fixed;
            left: 0;
            top: 60px;
            padding-top: 10px;
            transition: width 0.3s;
        }

        .sidebar.collapsed {
            width: 80px;
        }

        .toggle-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 50px;
            cursor: pointer;
        }

        .sidebar .menu li {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            cursor: pointer;
            transition: background 0.3s;
            position: relative;
        }

        .sidebar .menu li i {
            width: 40px;
            text-align: center;
            font-size: 1.2rem;
        }

        .sidebar .menu li span {
            flex-grow: 1;
            transition: opacity 0.3s;
        }

        .sidebar.collapsed .menu li {
            justify-content: center;
            padding: 12px;
        }

        .sidebar.collapsed .menu li span,
        .sidebar.collapsed .toggle-icon {
            display: none;
        }

        .sidebar .menu li:hover {
            background: #495057;
        }

        .sidebar .menu li.active {
            background: #007bff;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.3s;
        }

        .sidebar.collapsed+.main-content {
            margin-left: 80px;
        }

        iframe {
            width: 100%;
            height: calc(100vh - 80px);
            border: none;
            border-radius: 8px;
            margin-top: 5rem;
        }

        /* 文章分类子菜单 */
        .category-list {
            display: none;
            padding-left: 40px;
        }

        .sidebar.collapsed .category-list {
            display: none !important;
        }

        /* 仅在收缩时显示的浮动子菜单 */
        .floating-submenu {
            display: none;
            position: absolute;
            left: 80px;
            top: 0;
            background: #343a40;
            border-radius: 4px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            padding: 10px;
            z-index: 1000;
            white-space: nowrap;
        }

        .floating-submenu ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .floating-submenu li {
            padding: 8px 12px;
            cursor: pointer;
            transition: background 0.3s;
            color: white;
        }

        .floating-submenu li:hover {
            background: #007bff;
            color: white;
        }

        .welcome-message {
            /* background: linear-gradient(to right, #4a4e69, #222831); */
            /* 专业级渐变背景 */
            color: #f8f9fa;
            /* 浅色文字，增强对比度 */
            text-align: center;
            padding: 15px 20px;
            border-radius: 8px;
            /* 轻微圆角，显得现代 */
            /* box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2); */
            border-bottom: 1px solid #888888;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .welcome-message i {
            font-size: 40px;
            /* 用户头像大一点 */
            color: #ffc107;
            /* 高亮色，增强视觉吸引力 */
            margin-bottom: 15px;
        }

        #welcome-text {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        #current-time {
            font-size: 14px;
            font-style: italic;
            opacity: 0.8;
            /* 让时间部分稍微柔和些，不抢主视觉 */
        }

        .quitAdminBtn {
            position: absolute;
            bottom: 1rem;
            left: 1.5rem;
            width: 100%;
        }

        .quitAdminBtn a {
            color: white;
            display: inline-block;
            padding: 5px 10px;
        }
    </style>
</head>

<body>
    <div class="logo-frame">
        <span>上海锦山汽车客运有限公司网站管理中心</span>
    </div>

    <div class="sidebar" id="sidebar">
        <div class="top-bar">
            <div class="welcome-message">
                <i class="fas fa-user-circle"></i>
                <span id="welcome-text">欢迎回来，<?= htmlspecialchars($_SESSION['username']) ?></span>
                <span id="current-time"></span>
            </div>
        </div>

        <div class="toggle-btn" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </div>
        <ul class="menu list-unstyled">
            <?php foreach ($modules as $module): ?>
                <li
                    <?php if ($module['module_name'] === '文章管理'): ?>
                    onmouseover="showFloatingMenu(event)"
                    onmouseleave="hideFloatingMenu()"
                    onclick="toggleCategoryList();"
                    <?php else: ?>
                    onclick="loadPage('<?php echo htmlspecialchars($module['module_url']); ?>', this);"
                    <?php endif; ?>>
                    <i class="fas <?php echo htmlspecialchars($module['module_icon']); ?>"></i>
                    <span><?php echo htmlspecialchars($module['module_name']); ?></span>
                    <?php if ($module['module_name'] === '文章管理'): ?>
                        <i class="fas fa-chevron-down ms-auto toggle-icon"></i>
                    <?php endif; ?>
                </li>
                <?php if ($module['module_name'] === '文章管理'): ?>
                    <ul class="category-list list-unstyled" id="categoryList">
                        <?php foreach ($categories as $category): ?>
                            <li onclick="loadPage('articles.php?category_id=<?= htmlspecialchars($category['id']) ?>', this);">
                                <i class="fas fa-folder"></i> <?= htmlspecialchars($category['name']) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>

        <div class="quitAdminBtn"><img src="../assets/images/favicon.png" alt="" width="32"> <a href="logout.php"> 退出系统 </a></div>
    </div>

    <div class="main-content">
        <iframe id="mainFrame"></iframe>
    </div>

    <div class="floating-submenu" id="floatingSubmenu">
        <ul>
            <?php foreach ($categories as $category): ?>
                <li onclick="loadPage('articles.php?category_id=<?= htmlspecialchars($category['id']) ?>');">
                    <?= htmlspecialchars($category['name']) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <script>
        // function loadPage(page, element) {
        //     document.getElementById('mainFrame').src = page;
        //     document.querySelectorAll('.menu li').forEach(item => item.classList.remove('active'));
        //     element.classList.add('active');
        // }

        function loadPage(page, element) {
            let iframe = document.getElementById('mainFrame');
            iframe.src = page + '?t=' + new Date().getTime(); // 添加时间戳，防止缓存

            // 高亮当前选中的菜单项
            document.querySelectorAll('.menu li').forEach(item => item.classList.remove('active'));
            element.classList.add('active');
        }

        // function loadPage(page, element) {
        //     fetch(page) // 发送请求获取 HTML
        //         .then(response => response.text()) // 获取文本内容
        //         .then(html => {
        //             document.getElementById('mainFrame').style.display = "none"; // 隐藏 iframe
        //             document.querySelector('.main-content').innerHTML = html; // 插入 HTML
        //         })
        //         .catch(error => console.error('加载页面失败:', error));

        //     // 高亮当前选中的菜单项
        //     document.querySelectorAll('.menu li').forEach(item => item.classList.remove('active'));
        //     element.classList.add('active');
        // }

        function toggleSidebar() {
            let sidebar = document.getElementById("sidebar");
            sidebar.classList.toggle('collapsed');
        }

        function toggleCategoryList() {
            let categoryList = document.getElementById("categoryList");
            categoryList.style.display = (categoryList.style.display === "block") ? "none" : "block";
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
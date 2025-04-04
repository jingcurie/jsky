<?php
$current_page_css = "/assets/css/travelGuide.css"; // 该页面独有的 CSS
include 'header.php';
?>

<main class="article-main">
    <?php
    // 获取 "出行指南" 的 ID
    $parentMenu = query($conn, "SELECT id FROM menu WHERE name = '出行指南' AND is_active = 1 LIMIT 1");

    if (!empty($parentMenu)) {
        $parentId = $parentMenu[0]['id'];

        // 获取 "出行指南" 的子菜单
        $subMenus = query($conn, "SELECT * FROM menu WHERE parent_id = ? AND is_active = 1 ORDER BY sort_order", [$parentId]);
    } else {
        $subMenus = []; // 如果找不到，返回空数组
    }
    ?>

    <aside class="article-sidebar">
        <h2>出行指南</h2>
        <ul>
            <?php foreach ($subMenus as $menu) : ?>
                <li>
                    <a href="javascript:void(0);"
                        class="menu-link"
                        data-name="<?= htmlspecialchars($menu['name']) ?>"
                        data-url="<?= htmlspecialchars($menu['url']) ?>"
                        data-menu-type="<?= htmlspecialchars($menu['menu_type']) ?>"
                        data-article-category-id="<?= htmlspecialchars($menu['article_category_id']) ?>">
                        <?= htmlspecialchars($menu['name']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </aside>

    <div class="article-container">

    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const links = document.querySelectorAll(".menu-link");
            const mainContent = document.querySelector(".article-container");

            links.forEach(link => {
                link.addEventListener("click", function(e) {
                    e.preventDefault(); // 阻止默认跳转

                    const url = this.getAttribute("data-url");
                    const menuType = this.getAttribute("data-menu-type");
                    const categoryId = this.getAttribute("data-article-category-id");

                    // 移除所有 li 的 active 类
                    document.querySelectorAll(".article-sidebar li").forEach(li => {
                        li.classList.remove("active");
                    });

                    // 给当前点击的 li 父元素添加 active 类
                    this.parentElement.classList.add("active");

                    if (menuType === "url") {
                        window.location.href = url; // 直接跳转
                    } else if (menuType === "single_article") {
                        fetch(`get_articles.php?type=single&category_id=${categoryId}`)
                            .then(response => response.text())
                            .then(html => {
                                mainContent.innerHTML = html;
                            })
                            .catch(error => {
                                console.error("加载失败:", error);
                                mainContent.innerHTML = "<p>加载失败，请重试。</p>";
                            });
                    } else if (menuType === "multiple_article") {
                        fetch(`get_articles.php?type=multiple&category_id=${categoryId}`)
                            .then(response => response.text())
                            .then(html => {
                                mainContent.innerHTML = html;
                            })
                            .catch(error => {
                                console.error("加载失败:", error);
                                mainContent.innerHTML = "<p>加载失败，请重试。</p>";
                            });
                    }
                });
            });
        });
    </script>
</main>



<?php include "header.php" ?>
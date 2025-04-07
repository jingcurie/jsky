<?php
require_once __DIR__ . "/../includes/config.php";
$current_page_css = CSS_URL . "/subMenuPage.css"; // 该页面独有的 CSS
require_once __DIR__ . '/../templates/header.php';
 

$menu_id = $_GET['menu_id'] ?? null;
$article_category_id = $_GET['article_category_id'] ?? null;

$menuName = "[暂无菜单]"; // 默认名称
$subMenus = [];

$menu = "";

// 获取当前菜单信息
if ($menu_id) {
    $menu = query($conn, "SELECT * FROM menu WHERE id = ? AND is_active = 1 LIMIT 1", [$menu_id]);

    if (!empty($menu)) {
        $menuName = $menu[0]['name'];

        // 获取子菜单
        $subMenus = query($conn, "SELECT * FROM menu WHERE parent_id = ? AND is_active = 1 ORDER BY sort_order", [$menu_id]);
    }
}

// 获取文章信息（如果有 article_category_id）
$articles = [];
if ($article_category_id) {
    $articles = query($conn, "SELECT * FROM articles WHERE category_id = ? and status = ? ORDER BY created_at DESC", [$article_category_id, "published"]);
}
?>

<main class="article-main">
    <aside class="article-sidebar">
        <h2><?= htmlspecialchars($menuName) ?></h2>
        <?php if (!empty($subMenus)): ?>
            <ul>
                <?php foreach ($subMenus as $menu): ?>
                    <li class="<?= ($menu['article_category_id'] == $article_category_id) ? 'active' : '' ?>">
                        <?php if ($menu['menu_type'] === 'article'): ?>
                            <a href="showSubMenuPage.php?menu_id=<?= urlencode($menu_id) ?>&article_category_id=<?= urlencode($menu['article_category_id']) ?>">
                                <?= htmlspecialchars($menu['name']) ?>
                            </a>
                        <?php else: ?>
                            <a href="<?= $menu['url'] ?>">
                                <?= htmlspecialchars($menu['name']) ?>
                            </a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </aside>

    <div class="articles-container">
        <?php if (!empty($articles)): ?>
            <?php if (count($articles) > 1): ?>
                <div class="articles-grid">
                    <?php foreach ($articles as $article): ?>
                        <article class="card" data-aos="fade-up">
                            <div>
                                <a href="client_view_article.php?id=<?= $article['id']; ?>"> <img src="<?= ARTICLE_URL ?><?= htmlspecialchars($article['cover_image']) ?: 'assets/images/default_article_image.jpg'; ?>" alt="<?= $article['title']; ?> 封面图片"></a>
                                <p class="summary">
                                    <?php
                                    // 尝试提取第一段文本作为摘要
                                    //preg_match('/<p>(.*?)<\/p>/', htmlspecialchars_decode($article['content']), $matches);
                                    // preg_match('/<p[^>]*>(.*?)<\/p>/s', htmlspecialchars_decode($article['content']), $matches);

                                    $summary = getSummary(htmlspecialchars_decode($article['content']));
                                    echo isset($summary) ? $summary : '暂无摘要...';
                                    ?>
                                </p>
                            </div>
                            <div>
                                <h3><a href="client_view_article.php?id=<?= $article['id']; ?>"><?= htmlspecialchars($article['title']); ?></a></h3>
                                <p>
                                    <span class="publish-date"><?= date("Y年m月d日", strtotime($article['created_at'])); ?></span> |
                                    <span class="publisher"><?= htmlspecialchars($article['author']); ?></span>
                                </p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <?php $article = $articles[0]; ?>
                <article>
                    <h2><?= htmlspecialchars($article['title']) ?></h2>
                    <?php
                     $conn->prepare("UPDATE articles SET view_count = view_count + 1 WHERE id = ?")->execute([$article["id"]]);
                     ?>
                    <?php if (in_array($article['category_id'], [1, 2])): ?>
                        <p><strong>发布时间：</strong><?= date("Y年m月d日", strtotime($article['published_at'])); ?></p>
                    <?php endif; ?>
                    <div class="article-content">
                        <?= htmlspecialchars_decode($article['content']); ?>
                    </div>
                </article>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>
<script>

    AOS.init({ duration: 1000 });
  </script>
<?php include "../templates/footer.php"; ?>
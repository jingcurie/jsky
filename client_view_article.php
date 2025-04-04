<?php

// 设置页面元数据
$meta_description = !empty($article['meta_description']) ?
    $article['meta_description'] :
    mb_substr(strip_tags($article['content']), 0, 100, 'UTF-8');

$current_page_css = '/assets/css/article.css';
$extraHead = <<<HTML
<meta name="description" content="{$article['title']} - {$meta_description}">
<meta property="og:title" content="{$article['title']}">
<meta property="og:description" content="{$meta_description}">
<meta property="og:image" content="{$article['cover_image']}">
HTML;

// 加载头部
require 'header.php';

// 获取文章ID
$article_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$article_id) {
    echo "id不存在, 无法查到指定记录";
    exit;
}

// 获取文章数据
$article = query($conn, "SELECT articles.created_at as ct, articles.*, menu.id AS menu_id, menu.* FROM articles LEFT JOIN menu ON articles.category_id = menu.article_category_id WHERE articles.id = ? and articles.status = ?", [$article_id, "published"])[0];
// $article = getById($conn, "articles", "id", $article_id);

if (!$article) {
    echo "查询错误，请联系管理员";
    // header("Location: /404.php");
    exit;
}

// 更新阅读量
$conn->prepare("UPDATE articles SET view_count = view_count + 1 WHERE id = ?")->execute([$article_id]);

?>

<main class="article-container">
    <?php
    // // 获取当前菜单ID和分类ID
    // $menu_id = isset($_GET['menu_id']) ? intval($_GET['menu_id']) : 0;
    // $article_category_id = isset($_GET['article_category_id']) ? intval($_GET['article_category_id']) : 0;

    // // 查询菜单信息
    // $menu = [];

    // if ($menu_id > 0) {
    //     $menu = query($conn, "SELECT * FROM menu WHERE id = ?", [$menu_id]);
    // }

    // // 查询分类信息
    // $category = [];
    // if ($article_category_id > 0) {
    //     $category = query($conn, "SELECT * FROM categories WHERE id = ?", [$article_category_id]);
    // }
    //$stmt = query($conn, "SELECT articles.*, menu.* FROM articles LEFT JOIN menu ON articles.category_id = menu.article_category_id WHERE articles.id = 6?", [$article_id])[0];
    ?>

    <div class="breadcrumb">
        <!-- 首页链接 -->
        <a href="/">首页</a>
        <i class="fas fa-chevron-right"></i>

        <!-- 一级菜单 -->
   
            <a href="/showSubMenuPage.php?article_category_id=<?= $article["category_id"] ?>&menu_id=<?= $article["menu_id"] ?>">
            <?= htmlspecialchars($article['name']) ?>
            </a>
            <i class="fas fa-chevron-right"></i>
     

        <!-- 分类名称（如果有） -->
<!--        
            <a href="/showSubMenuPage.php?article_category_id=<?= $article_category_id ?>&menu_id=<?= $menu_id ?>">
            <?= htmlspecialchars($category[0]['name']) ?>
            </a>
            <i class="fas fa-chevron-right"></i> -->
       

        <!-- 当前文章标题 -->
        <span>正文</span>
    </div>

    <!-- 文章主体 -->
    <article class="article-content">
        <header class="article-header">
            <h1><?= htmlspecialchars($article['title']) ?></h1>
            <div class="article-meta">
                <span><i class="far fa-user"></i> <?= htmlspecialchars($article['author']) ?></span>
                <span><i class="far fa-clock"></i> <?= date('Y-m-d H:i', strtotime($article['ct'])) ?></span>
                <span><i class="far fa-eye"></i> <?= $article['view_count'] ?>次阅读</span>
            </div>
        </header>



        <div class="article-body">
            <?= htmlspecialchars_decode($article['content']) ?>
        </div>

        <?php if (!empty($article['tags'])): ?>
            <div class="article-tags">
                <i class="fas fa-tags"></i>
                <?php
                $tags = array_filter(explode(',', $article['tags']), function ($tag) {
                    return !empty(trim($tag));
                });
                foreach ($tags as $tag): ?>
                    <a href="/tag.php?name=<?= urlencode(trim($tag)) ?>"><?= htmlspecialchars(trim($tag)) ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>

    <!-- 相关文章 -->
    
    <!-- <section class="related-articles">
        <h3><i class="fas fa-link"></i> 相关文章</h3>
        <div class="row"> -->
            <?php /*
            $stmt = $conn->prepare("
                SELECT id, title, cover_image, created_at 
                FROM articles 
                WHERE category_id = ? AND id != ? 
                ORDER BY view_count DESC 
                LIMIT 3
            ");
            $stmt->execute([$article['category_id'], $article_id]);
            while ($item = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                <div class="col-md-4">
                    <a href="client_view_article.php?id=<?= $item['id'] ?>" class="related-item">
                        <div class="related-image">
                            <img src="/assets/images/uploads/<?= !empty($item['cover_image']) ?
                                            htmlspecialchars($item['cover_image']) :
                                            '/assets/images/default-article.jpg' ?>"
                                alt="<?= htmlspecialchars($item['title']) ?>"
                                loading="lazy">
                        </div>
                        <h4><?= htmlspecialchars($item['title']) ?></h4>
                        <time><?= date('Y-m-d', strtotime($item['created_at'])) ?></time>
                    </a>
                </div>
            <?php endwhile; */?>
        </div>
    </section>
</main>

<?php
// 加载页脚
require 'footer.php';
?>

<script>
    $(document).ready(function() {
        // 图片灯箱效果
        $('.article-body img').click(function() {
            const src = $(this).attr('src');
            window.open(src, '_blank');
        });

        // 平滑滚动
        $('a[href^="#"]').on('click', function(e) {
            e.preventDefault();
            const target = $($(this).attr('href'));
            if (target.length) {
                $('html, body').animate({
                    scrollTop: target.offset().top - 20
                }, 500);
            }
        });
    });
</script>
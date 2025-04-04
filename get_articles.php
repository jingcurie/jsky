<?php

// 启用错误报告，方便调试
error_reporting(E_ALL);
ini_set('display_errors', 1);


include 'includes/db.php'; // 连接数据库
include 'includes/functions.php'; // 连接数据库

$type = $_GET['type'] ?? '';
$categoryId = $_GET['category_id'] ?? '';

if ($type === 'single' && !empty($categoryId)) {
    // 获取最新的一篇文章
    $article = query($conn, "SELECT * FROM articles WHERE category_id = ? ORDER BY created_at DESC LIMIT 1", [$categoryId]);
        
    if (!empty($article)) {
        $article = $article[0]; // 取第一篇文章
        
        ?>
        <article class="article-content">
            <h2><?= htmlspecialchars($article['title']); ?></h2>
            <p>
                <span class="publish-date"><?= date("Y年m月d日", strtotime($article['created_at'])); ?></span> |
                <span class="publisher"><?= htmlspecialchars($article['author']); ?></span>
            </p>
            <div class="article-body">
                <?= htmlspecialchars_decode($article['content']); ?>
            </div>
        </article>
        <?php
    } else {
        echo "<p>暂无相关文章。</p>";
    }
} elseif ($type === 'multiple' && !empty($categoryId)) {
    // 获取所有相关文章，按时间倒序
    $articles = query($conn, "SELECT * FROM articles WHERE category_id = ? ORDER BY created_at DESC", [$categoryId]);

    ?>
    <div class="articles-grid">
        <?php if (!empty($articles)): ?>
            <?php foreach ($articles as $article): ?>
                <article class="card" data-aos="fade-up">
                    <div>
                        <img src="<?= htmlspecialchars($article['cover_image']) ?: 'assets/images/default_article_iamge.jpg'; ?>" alt="文章图片">
                        <p class="summary">
                            <?php
                            // 尝试提取第一段文本作为摘要
                            preg_match('/<p>(.*?)<\/p>/', htmlspecialchars_decode($article['content']), $matches);
                            echo isset($matches[1]) ? $matches[1] : '暂无摘要...';
                            ?>
                        </p>
                    </div>
                    <div>
                        <h3><a href="article.php?id=<?= $article['id']; ?>"><?= htmlspecialchars($article['title']); ?></a></h3>
                        <p>
                            <span class="publish-date"><?= date("Y年m月d日", strtotime($article['created_at'])); ?></span> |
                            <span class="publisher"><?= htmlspecialchars($article['author']); ?></span>
                        </p>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <p>暂无相关文章。</p>
        <?php endif; ?>
    </div>
    <?php
} else {
    echo "<p>无效请求。</p>";
}
?>

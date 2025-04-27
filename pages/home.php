<?php
$banners = query($conn, "SELECT * FROM site_banners");

// $device_type = (preg_match('/mobile|android|iphone|ipad/i', $_SERVER['HTTP_USER_AGENT'])) ? 'Mobile' : 'Desktop';
// $ip = $_SERVER['REMOTE_ADDR'];
// $user_agent = $_SERVER['HTTP_USER_AGENT'];

// $stmt = $conn->prepare("INSERT INTO visit_logs (device_type, ip_address, user_agent) VALUES (?, ?, ?)");
// $stmt->execute([$device_type, $ip, $user_agent]);

if (!isset($_SESSION['device_logged'])) {
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $device_type = (preg_match('/mobile|android|iphone|ipad/i', $user_agent)) ? 'Mobile' : 'Desktop';
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $stmt = $conn->prepare("INSERT INTO visit_logs (device_type, ip_address, user_agent) VALUES (?, ?, ?)");
    $stmt->execute([$device_type, $ip, $user_agent]);
    $_SESSION['device_logged'] = true; // 防止重复记录
}
?>

<main>
    <section class="banner">
        <div>
            <!-- 初始加载第一个Banner -->
            <?php if (!empty($banners)): ?>
                <img src="<?= BANNER_URL?><?= htmlspecialchars($banners[0]['image_path']) ?>" alt="Banner" id="banner-image">
                <div class="banner-desc">
                    <h2 data-aos="fade-down"><?= htmlspecialchars($banners[0]['title']) ?></h2>
                    <p data-aos="fade-right"><?= htmlspecialchars($banners[0]['description']) ?></p>
                </div>
            <?php else: ?>
                <!-- 默认Banner -->
                <img src="<?= BANNER_URL?>/default-banner.jpg" alt="Banner" id="banner-image">
                <div class="banner-desc">
                    <h2 data-aos="fade-down">欢迎光临</h2>
                    <p data-aos="fade-right">上海锦山汽车客运有限公司欢迎您</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="content-wrapper">
        <div class="routes">
            <h2>线路查询</h2>
			<p>锦山客运始终秉持“锦山客运，服务金山”的理念，将安全行车，优良服务作为永恒的管理主题，为乘客提供安全无忧、舒适惬意、便捷高效的出行服务。</p>
            <div class="button">
                <a href="pages/bus_line_search_form.php">查看线路</a>
            </div>
        </div>

        <?php
        //require "includes/db.php";
        //var_dump($conn);
        // 查询分类为 1 的最新一篇文章
        $sql = "SELECT id, title, created_at, author, cover_image, content FROM articles WHERE category_id = 1 and status = 'published' ORDER BY created_at DESC LIMIT 1";
        $stmt = $conn->query($sql);
        // 获取查询结果
        $article = $stmt->fetch(PDO::FETCH_ASSOC); // fetch() 只获取一行数据
        // var_dump($article);

        if ($article) {
            // echo "查询到数据！";
            $id = $article['id'];
            $title = htmlspecialchars($article['title']);
            $publish_date = date("Y年m月d日", strtotime($article['created_at']));
            $author = htmlspecialchars($article['author']);
            $cover_image = htmlspecialchars($article['cover_image']);
            //$summary = mb_substr(htmlspecialchars_decode($article['content']), 0, 300) . '...';
            //preg_match('/<p[^>]*>(.*?)<\/p>/s', htmlspecialchars_decode($article['content']), $matches);
            // print_r($matches[1]);
            $summary = getSummary(htmlspecialchars_decode($article['content']));
            //echo isset($summary) ? $summary : '暂无摘要...';
        } else {
            // echo "没有查询到数据";
            // 设置默认值
            $title = "暂无新闻";
            $publish_date = date("Y年m月d日");
            $author = "管理员";
            $cover_image = IMG_URL . "/default-news.jpg";
            $summary = "暂无内容...";
        }
        ?>

        <div class="news" data-aos="fade-up" data-aos-duration="1500">
            <article class="card">
               <h2>最新新闻</h2>
               
                <div>
                    
                    <a href="/pages/client_view_article.php?id=<?php echo $article['id']; ?>"><img src="<?= ARTICLE_URL ?><?php echo $cover_image; ?>" alt="新闻图片"></a>

                    <div class="summary"><?php echo $summary; ?>
                        <!-- <a href="client_view_article.php?id=$id">更多</a> -->
                    </div>

                </div>
                <div>
                    <h3><a href="/pages/client_view_article.php?id=<?php echo $article['id']; ?>"><?php echo $title; ?></a></h3>
                    <p><span class="publish-date"><?php echo $publish_date; ?></span> | <span class="publisher"><?php echo $author; ?></span></p>
                </div>
            </article>
        </div>
    </section>

    <?php
    // 查询分类为 2 的最新 4 篇文章
    $sql = "SELECT id, title, created_at, author, cover_image, content FROM articles WHERE category_id = 2 and status = 'published' ORDER BY created_at DESC LIMIT 4";
    $stmt = $conn->query($sql);
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC); // 获取所有符合条件的文章

    // 处理数据
    ?>
    <section class="announcements">
        <div class="announcements-container">
            <h2>最新公告</h2>
            <div class="announcement-grid">
                <?php if (!empty($announcements)): ?>
                    <?php foreach ($announcements as $article): ?>
                        <article class="announcement card" data-aos="fade-up">
                            <div>
                                <a href="/pages/client_view_article.php?id=<?= $article['id']; ?>"><img src="<?= ARTICLE_URL ?><?= htmlspecialchars($article['cover_image']) ?: 'assets/images/default_article_iamge.jpg'; ?>" alt="公告图片"></a>
                                <p class="summary">
                                    <?php
                                    // 尝试提取第一段文本作为摘要
                                    //preg_match('/<p[^>]*>(.*?)<\/p>/s', htmlspecialchars_decode($article['content']), $matches);
                                    $summary = getSummary(htmlspecialchars_decode($article['content']));
                                    echo isset($summary) ? $summary : '暂无摘要...';
                                    ?>
                                </p>
                            </div>
                            <div>
                                <h3><a href="/pages/client_view_article.php?id=<?= $article['id']; ?>"><?= htmlspecialchars($article['title']); ?></a></h3>
                                <p>
                                    <span class="publish-date"><?= date("Y年m月d日", strtotime($article['created_at'])); ?></span> |
                                    <span class="publisher"><?= htmlspecialchars($article['author']); ?></span>
                                </p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- 如果没有公告，显示默认占位 -->
                    <p>暂无公告...</p>
                <?php endif; ?>
            </div>
            <div class="more-announcements button">
                <a href="/pages//showSubMenuPage.php?article_category_id=2&menu_id=24">查看更多</a>
            </div>
        </div>


    </section>

<?php
require_once __DIR__ . '/../../includes/config.php';
require_once INCLUDE_PATH . '/db.php';
require_once INCLUDE_PATH . '/check_ip_whitelist.php';
require_once INCLUDE_PATH . '/auth.php';
require_once INCLUDE_PATH . '/functions.php';
 
 if (!isLoggedIn()) {
     redirect('login.php');
 }
 
if (isset($_GET['category_id'])) {
    $category_id = intval($_GET['category_id']);
}

// 获取文章 ID
$article_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($article_id <= 0) {
    die("无效的文章 ID。");
}

// 查询文章详情
$sql = "SELECT * FROM articles WHERE id = :article_id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':article_id', $article_id);
$stmt->execute();
$article = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$article) {
    die("文章未找到。");
}
?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>文章详情</title>

    <!-- Bootstrap + FontAwesome -->
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"> -->
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/all.min.css">

    <style>
        body {
            background-color: #f8f9fa;
        }

        .container {
            margin-top: 30px;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .article-header {
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .article-title {
            font-size: 28px;
            font-weight: bold;
        }

        .article-meta {
            font-size: 14px;
            color: #666;
        }

        .article-content {
            font-size: 18px;
            line-height: 1.6;
        }

        .btn-back {
            background-color: #6c757d;
            color: white;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="article-header">
            <h2 class="article-title"><?= htmlspecialchars($article['title']) ?></h2>
            <p class="article-meta">
                <i class="fas fa-user"></i> 作者: <?= htmlspecialchars($article['author']) ?> | 
                <i class="fas fa-calendar-alt"></i> 时间: <?= $article['created_at'] ?>
            </p>
        </div>

        <div class="article-content">
            <?= htmlspecialchars_decode($article['content']) ?>
        </div>

        <a href="articles.php?category_id=<?= $category_id ?>" class="btn btn-back mt-3">
            <i class="fas fa-arrow-left"></i> 返回列表
        </a>
    </div>

    <!-- Bootstrap JS -->
    <script src="/assets/js/bootstrap.bundle.min.js"></script>

</body>
</html>

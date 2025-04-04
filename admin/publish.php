<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require '../includes/db.php';
require '../includes/auth.php';
require '../includes/functions.php';

$article_id = isset($_POST['id'])?$_POST['id']:"";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = trim($_POST['category']);
    $title = trim($_POST['title']);
    $content = $_POST['content'];
    $author = trim($_POST['author']);
    $created_at= trim($_POST['created_at']);
    $cover_image = trim($_POST['cover_image'] ?? 'assets/images/uploads/default_cover_image.jpg');  // 默认封面
    $status = trim($_POST['status']);

    if (empty($title) || empty($content) || empty($author)) {
        die("所有字段不能为空！");
    }

    // 预防 XSS
    $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
    if ($article_id > 0) {
        // **编辑模式：更新文章**
        $sql = "UPDATE articles 
                SET category_id = :category_id, title = :title, content = :content, 
                    author = :author, cover_image = :cover_image, status = :status, created_at = :created_at
                WHERE id = :article_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':article_id', $article_id);
    } else {
        // **新增模式：插入文章**
        $sql = "INSERT INTO articles (category_id, title, content, author, cover_image, status, created_at) 
                VALUES (:category_id, :title, :content, :author, :cover_image, :status, :created_at)";
        $stmt = $conn->prepare($sql);
    }

    // 绑定参数
    $stmt->bindParam(':category_id', $category);
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':content', $content);
    $stmt->bindParam(':author', $author);
    $stmt->bindParam(':created_at', $created_at);
    $stmt->bindParam(':cover_image', $cover_image);
    $stmt->bindParam(':status', $status);

    if ($stmt->execute()) {
        header("Location: articles.php?category_id=$category");
    } else {
        echo "发布失败，请重试！";
    }
}
?>

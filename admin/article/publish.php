
<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

require_once __DIR__ . '/../../includes/config.php';
require_once INCLUDE_PATH . '/db.php';
require_once INCLUDE_PATH . '/check_ip_whitelist.php';
require_once INCLUDE_PATH . '/auth.php';
require_once INCLUDE_PATH . '/functions.php';

csrfProtect();

$article_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = intval($_POST['category'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $content = $_POST['content'] ?? '';
    $author = trim($_POST['author'] ?? '');
    $created_at = trim($_POST['created_at'] ?? '');
    $cover_image = trim($_POST['cover_image'] ?? IMG_URL . '/default_cover_image.jpg');
    $status = trim($_POST['status'] ?? 'draft');

    if (empty($title) || empty($content) || empty($author)) {
        die("所有字段不能为空！");
    }

    // 🚫 检查分类是否被删除
    $category = getById($conn, "categories", "id", $category_id);
    if (!$category || $category['is_deleted'] == 1) {
        die("该文章所属分类不存在或已被删除，无法保存！");
    }

    // 🚫 如果是编辑文章，还要检查文章本身是否已删除
    if ($article_id > 0) {
        $article = getById($conn, "articles", "id", $article_id);
        if (!$article || $article['is_deleted'] == 1) {
            die("该文章已被删除，无法更新！");
        }
    }

    // ✅ XSS 处理（仅 content）
    $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');

    if ($article_id > 0) {
        // 更新文章
        $sql = "UPDATE articles 
                SET category_id = :category_id, title = :title, content = :content, 
                    author = :author, cover_image = :cover_image, status = :status, created_at = :created_at
                WHERE id = :article_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':article_id', $article_id);

        // 日志
        log_operation($conn, $_SESSION['user_id'], $_SESSION['username'], '更新', $category['name'] . '类文章', $article_id, $title);
    } else {
        // 新建文章
        $sql = "INSERT INTO articles (category_id, title, content, author, cover_image, status, created_at) 
                VALUES (:category_id, :title, :content, :author, :cover_image, :status, :created_at)";
        $stmt = $conn->prepare($sql);

        // 日志
        log_operation($conn, $_SESSION['user_id'], $_SESSION['username'], '新建', $category['name'] . '类文章', null, $title);
    }

    // 参数绑定
    $stmt->bindParam(':category_id', $category_id);
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':content', $content);
    $stmt->bindParam(':author', $author);
    $stmt->bindParam(':created_at', $created_at);
    $stmt->bindParam(':cover_image', $cover_image);
    $stmt->bindParam(':status', $status);

    if ($stmt->execute()) {
        header("Location: articles.php?category_id=$category_id");
        exit;
    } else {
        echo "发布失败，请重试！";
    }
}
?>

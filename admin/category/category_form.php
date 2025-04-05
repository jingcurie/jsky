<?php
require_once __DIR__ . '/../../includes/config.php';
require INCLUDE_PATH . '/db.php';
require INCLUDE_PATH . '/auth.php';
require INCLUDE_PATH . '/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// 初始化变量
$category_id = $_GET['id'] ?? null;
$category_name = '';
$category_desc = '';
$error = '';

// 如果是编辑模式，获取当前分类信息
if ($category_id) {
    $sql = "SELECT * FROM categories WHERE id = :category_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':category_id', $category_id);
    $stmt->execute();
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($category) {
        $category_name = $category['name'];
        $category_desc = $category['description'];
    } else {
        $error = "分类不存在！";
    }
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_name = trim($_POST['name']);
    $category_desc = trim($_POST['description']);

    if (empty($category_name)) {
        $error = "分类名称不能为空！";
    } elseif (empty($category_desc)) {
        $error = "分类描述不能为空！";
    } else {
        // 检查分类名称是否已存在（排除自身）
        $sql = "SELECT * FROM categories WHERE name = :category_name" . ($category_id ? " AND id != :category_id" : "");
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':category_name', $category_name);
        if ($category_id) {
            $stmt->bindParam(':category_id', $category_id);
        }
        $stmt->execute();
        $existingCategory = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingCategory) {
            $error = "该分类名称已存在！";
        } else {
            if ($category_id) {
                $sql = "UPDATE categories SET name = :category_name, description = :category_desc WHERE id = :category_id";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':category_name', $category_name);
                $stmt->bindParam(':category_desc', $category_desc);
                $stmt->bindParam(':category_id', $category_id);
            } else {
                $sql = "INSERT INTO categories (name, description) VALUES (:category_name, :category_desc)";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':category_name', $category_name);
                $stmt->bindParam(':category_desc', $category_desc);
            }

            if ($stmt->execute()) {
                header("Location: categories.php");
                exit;
            } else {
                $error = "保存失败，请重试！";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $category_id ? '编辑分类' : '创建分类'; ?></title>

    <!-- Bootstrap & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        body {
            background-color: #f8f9fa;
        }

        .form-container {
            max-width: 500px;
            margin: 50px auto;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .form-container h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .btn {
            border-radius: 5px;
        }

        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }

        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
        }

        .form-control {
            border-radius: 5px;
        }

        .error {
            color: red;
            font-weight: bold;
            margin-bottom: 10px;
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="form-container">
        <h2><i class="fas fa-folder"></i> <?php echo $category_id ? '编辑分类' : '创建分类'; ?></h2>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label for="category_name" class="form-label"><i class="fas fa-tag"></i> 分类名称</label>
                <input type="text" class="form-control" id="category_name" name="name" placeholder="输入分类名称"
                    value="<?php echo htmlspecialchars($category_name); ?>" required maxlength="50">
            </div>

            <div class="mb-3">
                <label for="category_desc" class="form-label"><i class="fas fa-align-left"></i> 分类描述</label>
                <textarea class="form-control" id="category_desc" name="description" rows="3" placeholder="输入分类描述"
                    required maxlength="200"><?php echo htmlspecialchars($category_desc); ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-save"></i> <?php echo $category_id ? '更新分类' : '创建分类'; ?>
            </button>
        </form>

        <div class="text-center mt-3">
            <a href="categories.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> 返回</a>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>

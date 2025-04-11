<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../includes/config.php';
require INCLUDE_PATH . '/db.php';
require INCLUDE_PATH . '/auth.php';
require INCLUDE_PATH . '/functions.php';

csrfProtect();

if (!isLoggedIn()) {
    redirect('/admin/login.php');
}

$module_id = '';
$module_name = '';
$description = '';
$module_icon = '';
$module_url = '';
$module_order = '';
$error = '';

// 处理表单提交
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $module_id = $_POST['module_id'] ?? '';
    $module_name = trim($_POST['module_name']);
    $description = trim($_POST['description']);
    $module_icon = trim($_POST['module_icon']);
    $module_url = trim($_POST['module_url']);
    $module_order = intval($_POST['module_order']);

    if (empty($module_name) || empty($module_url)) {
        $error = "模块名称和 URL 不能为空！";
    } else {
        $data = [
            'module_name' => $module_name,
            'description' => $description,
            'module_icon' => $module_icon,
            'module_url' => $module_url,
            'module_order' => $module_order
        ];

        if (!empty($module_id)) {
            // 更新模块
            $success = update($conn, "modules", "module_id", $id, $data);
            log_operation($conn, $_SESSION['user_id'], $_SESSION['username'], '更新', '模块管理', $module_id, $module_name);
        } else {
            // 新增模块
            $success = insert($conn, "modules", $data);
            log_operation($conn, $_SESSION['user_id'], $_SESSION['username'], '创建', '模块管理', null, $data["name"]);
        }

        if ($success) {
            header("Location: modules.php");
            exit;
        } else {
            $error = "保存模块时发生错误！";
        }
    }
}

// 处理编辑请求
if (isset($_GET['module_id'])) {
    $module_id = intval($_GET['module_id']);
    $sql = "SELECT * FROM modules WHERE module_id = :module_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':module_id', $module_id);
    $stmt->execute();
    $module = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($module) {
        $module_name = $module['module_name'];
        $description = $module['description'];
        $module_icon = $module['module_icon'];
        $module_url = $module['module_url'];
        $module_order = $module['module_order'];
    } else {
        $error = "找不到该模块！";
    }
}
?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $module_id ? '编辑模块' : '创建新模块'; ?></title>

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
            max-width: 600px;
        }

        .form-group label {
            font-weight: bold;
        }

        .btn {
            border-radius: 5px;
        }

        .btn-save {
            background-color: #28a745;
            color: white;
        }

        .btn-back {
            background-color: #6c757d;
            color: white;
        }

        .error {
            color: red;
        }
    </style>
</head>
<body>

    <div class="container">
        <h2><i class="fas fa-cube"></i> <?php echo $module_id ? '编辑模块' : '创建新模块'; ?></h2>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="module_form.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()) ?>">
            <input type="hidden" name="module_id" value="<?php echo htmlspecialchars($module_id); ?>">

            <div class="form-group mb-3">
                <label for="module_name"><i class="fas fa-cogs"></i> 名称</label>
                <input type="text" id="module_name" name="module_name" class="form-control" value="<?php echo htmlspecialchars($module_name); ?>" required>
            </div>

            <div class="form-group mb-3">
                <label for="description"><i class="fas fa-align-left"></i> 描述</label>
                <textarea id="description" name="description" class="form-control"><?php echo htmlspecialchars($description); ?></textarea>
            </div>

            <div class="form-group mb-3">
                <label for="module_icon"><i class="fas fa-icons"></i> 图标（FontAwesome 类名）</label>
                <input type="text" id="module_icon" name="module_icon" class="form-control" placeholder="fas fa-cube" value="<?php echo htmlspecialchars($module_icon); ?>">
                <small class="text-muted">示例：fas fa-cog，fas fa-home</small>
            </div>

            <div class="form-group mb-3">
                <label for="module_url"><i class="fas fa-link"></i> URL</label>
                <input type="text" id="module_url" name="module_url" class="form-control" value="<?php echo htmlspecialchars($module_url); ?>" required>
            </div>

            <div class="form-group mb-3">
                <label for="module_order"><i class="fas fa-sort-numeric-up"></i> 显示排序</label>
                <input type="number" id="module_order" name="module_order" class="form-control" value="<?php echo htmlspecialchars($module_order); ?>">
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-save"></i> <?php echo $module_id ? '更新模块' : '创建模块'; ?>
            </button>
            <!-- <a href="modules.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> 返回</a> -->
        </form>

        <div class="text-center mt-3">
            <a href="modules.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> 返回</a>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>

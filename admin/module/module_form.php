<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../includes/config.php';
require INCLUDE_PATH . '/db.php';
require_once INCLUDE_PATH . '/check_ip_whitelist.php';
require INCLUDE_PATH . '/auth.php';
require_once INCLUDE_PATH . '/functions.php';

csrfProtect();

if (!isLoggedIn()) {
    redirect('/admin/login.php');
}

// 初始化变量
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
        // ✅ 判断模块名称是否已存在（排除软删除、排除自身）
        if (!empty($module_id)) {
            $check = query($conn, "SELECT COUNT(*) AS cnt FROM modules WHERE module_name = ? AND module_id != ? AND is_deleted = 0", [$module_name, $module_id]);
        } else {
            $check = query($conn, "SELECT COUNT(*) AS cnt FROM modules WHERE module_name = ? AND is_deleted = 0", [$module_name]);
        }

        if ($check[0]['cnt'] > 0) {
            $error = "模块名称已存在，请更换！";
        } else {
            $data = [
                'module_name' => $module_name,
                'description' => $description,
                'module_icon' => $module_icon,
                'module_url' => $module_url,
                'module_order' => $module_order
            ];

            if (!empty($module_id)) {
                $success = update($conn, "modules", "module_id", $module_id, $data);
                log_operation($conn, $_SESSION['user_id'], $_SESSION['username'], '更新', '模块管理', $module_id, $module_name);
            } else {
                $success = insert($conn, "modules", $data);
                log_operation($conn, $_SESSION['user_id'], $_SESSION['username'], '创建', '模块管理', null, $module_name);
            }

            if ($success) {
                header("Location: modules.php");
                exit;
            } else {
                $error = "保存模块时发生错误！";
            }
        }
    }
}

// 处理编辑请求
if (isset($_GET['module_id'])) {
    $module_id = intval($_GET['module_id']);
    $sql = "SELECT * FROM modules WHERE module_id = :module_id AND is_deleted = 0";
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
    <title><?= $module_id ? '编辑模块' : '创建新模块'; ?></title>
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

        .error {
            color: red;
        }
    </style>
</head>
<body>

    <div class="container">
        <h2><i class="fas fa-cube"></i> <?= $module_id ? '编辑模块' : '创建新模块'; ?></h2>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <form action="module_form.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()) ?>">
            <input type="hidden" name="module_id" value="<?= htmlspecialchars($module_id); ?>">

            <div class="form-group mb-3">
                <label for="module_name"><i class="fas fa-cogs"></i> 名称</label>
                <input type="text" id="module_name" name="module_name" class="form-control" value="<?= htmlspecialchars($module_name); ?>" required>
            </div>

            <div class="form-group mb-3">
                <label for="description"><i class="fas fa-align-left"></i> 描述</label>
                <textarea id="description" name="description" class="form-control"><?= htmlspecialchars($description); ?></textarea>
            </div>

            <div class="form-group mb-3">
                <label for="module_icon"><i class="fas fa-icons"></i> 图标（FontAwesome 类名）</label>
                <input type="text" id="module_icon" name="module_icon" class="form-control" placeholder="fas fa-cube" value="<?= htmlspecialchars($module_icon); ?>">
                <small class="text-muted">示例：fas fa-cog，fas fa-home</small>
            </div>

            <div class="form-group mb-3">
                <label for="module_url"><i class="fas fa-link"></i> URL</label>
                <input type="text" id="module_url" name="module_url" class="form-control" value="<?= htmlspecialchars($module_url); ?>" required>
            </div>

            <div class="form-group mb-3">
                <label for="module_order"><i class="fas fa-sort-numeric-up"></i> 显示排序</label>
                <input type="number" id="module_order" name="module_order" class="form-control" value="<?= htmlspecialchars($module_order); ?>">
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-save"></i> <?= $module_id ? '更新模块' : '创建模块'; ?>
            </button>
        </form>

        <div class="text-center mt-3">
            <a href="modules.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> 返回</a>
        </div>
    </div>

    <script src="/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>

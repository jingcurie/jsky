<?php
require_once __DIR__ . '/../../includes/config.php';
require INCLUDE_PATH . '/db.php';
require INCLUDE_PATH . '/auth.php';
require INCLUDE_PATH . '/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$role_id = $_GET['role_id'] ?? null;
if (!$role_id) {
    die("角色 ID 不存在！");
}

$role = getById($conn, 'roles', 'role_id', $role_id);
if (!$role) {
    die("角色不存在！");
}

$role_name = strtolower($role['role_name']);

// 获取所有模块
$modules = getAll($conn, 'modules');

// 获取当前角色的已分配模块
$sql = "SELECT module_id FROM role_permissions WHERE role_id = ?";
$assigned_modules = query($conn, $sql, [$role_id]);
$assigned_module_ids = array_column($assigned_modules, 'module_id');

// 限制 editor 可分配模块名称
$editor_allowed_modules = ['文章管理', '文章分类管理'];

// 表单提交处理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_modules = $_POST['modules'] ?? [];

    // admin 强制保留“模块管理”
    if ($role_name === 'admin') {
        foreach ($modules as $m) {
            if ($m['module_name'] === '模块管理' && !in_array($m['module_id'], $selected_modules)) {
                $selected_modules[] = $m['module_id'];
            }
        }
    }

    // editor 只能选择特定模块
    if ($role_name === 'editor') {
        $allowed_ids = array_column(array_filter($modules, function ($m) use ($editor_allowed_modules) {
            return in_array($m['module_name'], $editor_allowed_modules);
        }), 'module_id');
        $selected_modules = array_intersect($selected_modules, $allowed_ids);
    }

    // 更新数据库
    execute($conn, "DELETE FROM role_permissions WHERE role_id = ?", [$role_id]);
    $stmt = $conn->prepare("INSERT INTO role_permissions (role_id, module_id) VALUES (?, ?)");
    
    foreach ($selected_modules as $module_id) {
        $stmt->execute([$role_id, $module_id]);
    }
    log_operation($conn, $_SESSION['user_id'], $_SESSION['username'], '修改', '模块分配', null, "角色分配模块操作");

    redirect('roles.php');
}
?>

<!DOCTYPE html>
<html lang="zh">

<head>
    <meta charset="UTF-8">
    <title>分配模块</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .form-container {
            max-width: 600px;
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

        .module-list {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 5px;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 40px;
            height: 22px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: 0.4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            border-radius: 50%;
            left: 4px;
            bottom: 3px;
            background-color: white;
            transition: 0.4s;
        }

        input:checked+.slider {
            background-color: #4CAF50;
        }

        input:checked+.slider:before {
            transform: translateX(18px);
        }
    </style>
</head>

<body>
    <div class="form-container">
        <h2><i class="fas fa-tasks"></i> 分配模块</h2>

        <?php if ($role_name === 'editor'): ?>
            <div class="alert alert-info text-center">
                该角色仅可分配 “文章管理” 和 “文章分类管理” 模块。
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="module-list">
                <?php foreach ($modules as $module): ?>
                    <?php
                    $module_id = $module['module_id'];
                    $module_name = $module['module_name'];
                    $is_checked = in_array($module_id, $assigned_module_ids);
                    $is_required = ($role_name === 'admin' && $module_name === '模块管理');
                    $is_disabled = ($role_name === 'editor' && !in_array($module_name, $editor_allowed_modules));
                    ?>
                    <div class="form-check mb-2 <?= $is_required ? 'bg-warning bg-opacity-25 rounded p-2' : '' ?>">
                        <label class="form-check-label d-flex justify-content-between align-items-center w-100">
                            <span>
                                <?= htmlspecialchars($module_name) ?>
                                <?php if ($is_required): ?>
                                    <span class="badge bg-warning text-dark ms-2">必选</span>
                                <?php elseif ($is_disabled): ?>
                                    <span class="badge bg-secondary ms-2">不可更改</span>
                                <?php endif; ?>
                            </span>
                            <label class="switch mb-0">
                                <input type="checkbox"
                                    name="modules[]"
                                    value="<?= $module_id ?>"
                                    <?= $is_checked ? 'checked' : '' ?>
                                    <?= $is_required ? 'class="required-module"' : '' ?>
                                    <?= $is_disabled ? 'disabled' : '' ?>>
                                <span class="slider"></span>
                            </label>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="submit" class="btn btn-primary w-100 mt-3"><i class="fas fa-save"></i> 保存分配</button>
        </form>

        <div class="text-center mt-3">
            <a href="roles.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> 返回</a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const requiredCheckboxes = document.querySelectorAll('.required-module');
            requiredCheckboxes.forEach(cb => {
                cb.addEventListener('change', function () {
                    if (!cb.checked) {
                        alert('Admin 角色必须保留“模块管理”权限，无法取消勾选。');
                        cb.checked = true;
                    }
                });
            });
        });
    </script>
</body>

</html>

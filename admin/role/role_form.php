
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../includes/config.php';
require INCLUDE_PATH . '/db.php';
require_once INCLUDE_PATH . '/check_ip_whitelist.php';
require INCLUDE_PATH . '/auth.php';
require INCLUDE_PATH . '/functions.php';

csrfProtect();

if (!isLoggedIn()) {
    redirect('/admin/login.php');
}

$role_id = $_GET['role_id'] ?? null;
$role_name = '';
$role_desc = '';
$error = '';

if ($role_id) {
    $role = getById($conn, 'roles', 'role_id', $role_id);
    if ($role) {
        $role_name = $role['role_name'];
        $role_desc = $role['role_desc'];
    } else {
        $error = "角色不存在。";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role_name = trim($_POST['role_name']);
    $role_desc = trim($_POST['role_desc']);

    if (empty($role_name)) {
        $error = "角色名称不能为空。";
    } elseif (empty($role_desc)) {
        $error = "角色描述不能为空。";
    } else {
        // ✅ 检查角色名称是否重复（排除软删除）
        if ($role_id) {
            $check = query($conn, "SELECT COUNT(*) AS cnt FROM roles WHERE role_name = ? AND role_id != ? AND is_deleted = 0", [$role_name, $role_id]);
        } else {
            $check = query($conn, "SELECT COUNT(*) AS cnt FROM roles WHERE role_name = ? AND is_deleted = 0", [$role_name]);
        }

        if ($check[0]['cnt'] > 0) {
            $error = "该角色名称已存在。";
        } else {
            $data = [
                'role_name' => $role_name,
                'role_desc' => $role_desc
            ];

            if ($role_id) {
                $success = update($conn, 'roles', 'role_id', $role_id, $data);
                if ($success) {
                    log_operation($conn, $_SESSION['user_id'], $_SESSION['username'], '更新', '角色管理', $role_id, $data['role_name']);
                    redirect('roles.php');
                } else {
                    $error = "更新角色失败。";
                }
            } else {
                $success = insert($conn, 'roles', $data);
                if ($success) {
                    log_operation($conn, $_SESSION['user_id'], $_SESSION['username'], '创建', '角色管理', null, $data['role_name']);
                    redirect('roles.php');
                } else {
                    $error = "创建角色失败。";
                }
            }
        }
    }
}

// 判断是否为只读角色
$readonly = in_array(strtolower($role_name), ['admin', 'editor']);
?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $role_id ? '编辑角色' : '创建角色'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">

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
        .form-control {
            border-radius: 5px;
        }
    </style>
</head>
<body>
<div class="form-container">
    <h2><i class="fas fa-user-shield"></i> <?= $role_id ? '编辑角色' : '创建角色'; ?></h2>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= $error; ?></div>
    <?php endif; ?>

    <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()) ?>">
        <div class="mb-3">
            <label for="role_name" class="form-label"><i class="fas fa-id-badge"></i> 角色名称</label>
            <input type="text" class="form-control" id="role_name" name="role_name"
                   value="<?= htmlspecialchars($role_name); ?>"
                   placeholder="请输入角色名称" maxlength="50"
                   <?= $readonly ? 'readonly' : '' ?> required>
        </div>

        <div class="mb-3">
            <label for="role_desc" class="form-label"><i class="fas fa-align-left"></i> 描述</label>
            <textarea class="form-control" id="role_desc" name="role_desc" rows="3" maxlength="200"
                      placeholder="请输入角色描述" required><?= htmlspecialchars($role_desc); ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary w-100">
            <i class="fas fa-save"></i> <?= $role_id ? '更新角色' : '创建角色'; ?>
        </button>
    </form>

    <div class="text-center mt-3">
        <a href="roles.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> 返回</a>
    </div>
</div>

<script src="/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>

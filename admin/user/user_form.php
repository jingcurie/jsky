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

$user_id = $_GET['user_id'] ?? null;
$username = '';
$email = '';
$role_id = '';
$password = '';
$generated_password = bin2hex(random_bytes(4));
$error = '';
$avatar = 'a1.png'; // 默认头像

$roles = query($conn, "SELECT * FROM roles WHERE is_deleted = 0");

$role_name = "";
// 获取旧用户信息
if ($user_id) {
    $user = getById($conn, 'users', 'user_id', $user_id);
    if ($user && $user['is_deleted'] == 0) {
        $username = $user['username'];
        $email = $user['email'];
        $role_id = $user['role_id'];
        $password = $user['password_hash'];
        $avatar = $user['avatar'] ?? 'a1.png';
        $generated_password = '';

        $role = getById($conn, "roles", "role_id", $role_id);
    } else {
        $error = "用户不存在或已被删除";
    }
}

// 处理提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role_id = trim($_POST['role_id']);
    $password = trim($_POST['password']) ?: $generated_password;
    $avatar = $_POST['avatar'] ?? 'a1.png';

    if (empty($username) || empty($email) || (!$user_id && empty($password))) {
        $error = "所有字段都是必填的。";
    } else {
        if (!$user_id) {
            $exists = query($conn, "SELECT COUNT(*) as cnt FROM users WHERE (username = ? OR email = ?) AND is_deleted = 0", [$username, $email])[0]['cnt'];
            if ($exists > 0) {
                $error = "用户名或邮箱已被使用，请更换。";
            }

            if (empty($error)) {
                $deleted = query($conn, "SELECT * FROM users WHERE (username = ? OR email = ?) AND is_deleted = 1", [$username, $email]);
                if ($deleted) {
                    $error = "该用户名或邮箱已存在于回收站中，请联系管理员恢复该账户。";
                }
            }
        } else {
            $exists = query($conn, "SELECT COUNT(*) as cnt FROM users WHERE user_id != ? AND (username = ? OR email = ?) AND is_deleted = 0", [$user_id, $username, $email])[0]['cnt'];
            if ($exists > 0) {
                $error = "该用户名或邮箱已被其他用户占用，请更换。";
            }
        }
    }

    if (empty($error)) {
        if ($user_id) {
            if (!empty($password)) {
                $sql = "UPDATE users SET username = ?, email = ?, role_id = ?, password_hash = ?, avatar = ? WHERE user_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$username, $email, $role_id, password_hash($password, PASSWORD_DEFAULT), $avatar, $user_id]);
            } else {
                $sql = "UPDATE users SET username = ?, email = ?, role_id = ?, avatar = ? WHERE user_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$username, $email, $role_id, $avatar, $user_id]);
            }
            log_operation($conn, $_SESSION['user_id'], $_SESSION['username'], '更新', '用户管理', $user_id, $username);
        } else {
            $sql = "INSERT INTO users (username, email, password_hash, role_id, avatar, must_change_password) VALUES (?, ?, ?, ?, ?, 1)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), $role_id, $avatar]);
            log_operation($conn, $_SESSION['user_id'], $_SESSION['username'], '创建', '用户管理', null, $username);
        }
        header("Location: users.php");
        exit;
    }
}

$selected_avatar = $_POST['avatar'] ?? ($avatar ?? 'a1.png');
?>
<!DOCTYPE html>
<html lang="zh">

<head>
    <meta charset="UTF-8">
    <title><?= $user_id ? '编辑用户' : '新增用户'; ?></title>
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/all.min.css">
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

        .btn {
            border-radius: 5px;
        }

        .modal-body img {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            cursor: pointer;
            border: 2px solid transparent;
        }

        .modal-body img.selected {
            border-color: #007bff;
            border-width: 3px;
        }
    </style>
</head>

<body>
    <div class="form-container">
        <h2><i class="fas fa-user"></i> <?= $user_id ? '编辑用户' : '新增用户'; ?></h2>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger text-center"><?= $error ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()) ?>">

            <div class="mb-3">
                <label for="username" class="form-label">用户名</label>
                <input type="text" class="form-control" id="username" name="username"
                    value="<?= htmlspecialchars($username) ?>" required <?= $username === 'admin' ? 'readonly' : '' ?>>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">邮箱</label>
                <input type="email" class="form-control" id="email" name="email"
                    value="<?= htmlspecialchars($email) ?>" required <?= $username === 'admin' ? 'readonly' : '' ?>>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">密码</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="password" name="password" value="<?= $generated_password ?>">
                    <button type="button" class="btn btn-outline-secondary" onclick="copyPassword()">复制</button>
                </div>
            </div>

            <div class="mb-3">
                <label for="role_id" class="form-label">角色</label>
                <?php if ($username === 'admin' && $user_id): ?>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($role['role_name']) ?>" readonly>
                    <input type="hidden" name="role_id" value="<?= $role_id ?>">
                <?php else: ?>
                    <select class="form-select" id="role_id" name="role_id" required>
                        <option value="">请选择角色</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= $role['role_id'] ?>" <?= $role_id == $role['role_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($role['role_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>

            <!-- 头像预览 + 弹窗选择器 -->
            <div class="mb-3">
                <label class="form-label">头像</label>
                <div class="d-flex align-items-center gap-3">
                    <img id="avatarPreview" src="/assets/images/avatars/<?= htmlspecialchars($selected_avatar) ?>" width="64" height="64" class="rounded-circle border">
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="openAvatarModal()">选择头像</button>
                    <input type="hidden" name="avatar" id="avatarInput" value="<?= htmlspecialchars($selected_avatar) ?>">
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-save"></i> <?= $user_id ? '更新用户' : '创建用户' ?>
            </button>
        </form>

        <div class="text-center mt-3">
            <a href="users.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> 返回</a>
        </div>
    </div>

    <!-- 头像选择弹窗 -->
    <div class="modal fade" id="avatarModal" tabindex="-1" aria-labelledby="avatarModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">选择头像</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="avatarList" class="d-flex flex-wrap gap-3 justify-content-start"></div>
                    <nav class="mt-3">
                        <ul class="pagination pagination-sm justify-content-center" id="avatarPagination"></ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <script src="/assets/js/bootstrap.bundle.min.js"></script>
    <script>
        const avatars = Array.from({
            length: 30
        }, (_, i) => `a${i + 1}.png`);
        const perPage = 9;
        let currentPage = 1;

        function copyPassword() {
            const input = document.getElementById('password');
            input.select();
            document.execCommand('copy');
            alert('密码已复制');
        }

        function openAvatarModal() {
            renderAvatars();
            new bootstrap.Modal(document.getElementById('avatarModal')).show();
        }

        function renderAvatars() {
            const list = document.getElementById('avatarList');
            const pagination = document.getElementById('avatarPagination');
            list.innerHTML = '';
            pagination.innerHTML = '';

            const totalPages = Math.ceil(avatars.length / perPage);
            const start = (currentPage - 1) * perPage;
            const visible = avatars.slice(start, start + perPage);
            const selected = document.getElementById('avatarInput').value;

            visible.forEach(name => {
                const img = document.createElement('img');
                img.src = `/assets/images/avatars/${name}`;
                img.className = name === selected ? 'selected' : '';
                img.onclick = () => {
                    document.getElementById('avatarInput').value = name;
                    document.getElementById('avatarPreview').src = `/assets/images/avatars/${name}`;
                    bootstrap.Modal.getInstance(document.getElementById('avatarModal')).hide();
                };
                list.appendChild(img);
            });

            for (let i = 1; i <= totalPages; i++) {
                const li = document.createElement('li');
                li.className = 'page-item' + (i === currentPage ? ' active' : '');
                li.innerHTML = `<a class="page-link" href="#">${i}</a>`;
                li.onclick = (e) => {
                    e.preventDefault();
                    currentPage = i;
                    renderAvatars();
                };
                pagination.appendChild(li);
            }
        }
    </script>
</body>

</html>
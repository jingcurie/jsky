<?php
require '../includes/db.php';
require '../includes/auth.php';
require '../includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// 初始化变量
$user_id = $_GET['user_id'] ?? null;
$username = '';
$email = '';
$role_id = '';
$password = '';
$error = '';

// 查询所有角色
$sql = "SELECT * FROM roles";
$stmt = $conn->query($sql);
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 如果是编辑模式，获取当前用户信息
if ($user_id) {
    $sql = "SELECT * FROM users WHERE user_id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $username = $user['username'];
        $email = $user['email'];
        $role_id = $user['role_id'];
    } else {
        $error = "User not found.";
    }
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role_id = trim($_POST['role_id']);
    
    if (!$user_id) { // 仅在新增用户时处理密码
        $password = trim($_POST['password']);
    }

    if (empty($username) || empty($email) || (!$user_id && empty($password))) {
        $error = "All fields are required.";
    } else {
        if ($user_id) {
            // 更新用户角色
            $sql = "UPDATE users SET role_id = :role_id WHERE user_id = :user_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':role_id', $role_id);
            $stmt->bindParam(':user_id', $user_id);
        } else {
            // 新增用户
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, email, password_hash, role_id) VALUES (:username, :email, :password_hash, :role_id)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password_hash', $hashedPassword);
            $stmt->bindParam(':role_id', $role_id);
        }

        if ($stmt->execute()) {
            header("Location: users.php");
            exit;
        } else {
            $error = "Error saving user.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title><?php echo $user_id ? '分配角色' : '新增用户'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .form-container {
            max-width: 500px; margin: 50px auto; background: #fff;
            padding: 30px; border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .form-container h2 { text-align: center; margin-bottom: 20px; }
        .btn { border-radius: 5px; }
    </style>
</head>
<body>

    <div class="form-container">
        <h2><?php echo $user_id ? '分配角色' : '新增用户'; ?></h2>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label for="username" class="form-label"><i class="fas fa-user"></i> 用户名</label>
                <input type="text" class="form-control" id="username" name="username" 
                    value="<?php echo htmlspecialchars($username); ?>" required 
                    <?php echo $user_id ? 'readonly' : ''; ?>>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label"><i class="fas fa-envelope"></i> 邮箱</label>
                <input type="email" class="form-control" id="email" name="email" 
                    value="<?php echo htmlspecialchars($email); ?>" required 
                    <?php echo $user_id ? 'readonly' : ''; ?>>
            </div>

            <?php if (!$user_id): // 只有新增用户时显示密码字段 ?>
            <div class="mb-3">
                <label for="password" class="form-label"><i class="fas fa-lock"></i> 密码</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <?php endif; ?>

            <div class="mb-3">
                <label for="role_id" class="form-label"><i class="fas fa-user-shield"></i> 角色</label>
                <select class="form-control" id="role_id" name="role_id" required>
                    <option value="">请选择角色</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?php echo $role['role_id']; ?>" 
                            <?php echo ($role_id == $role['role_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($role['role_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-save"></i> <?php echo $user_id ? '更新用户' : '创建用户'; ?>
            </button>
        </form>

        <div class="text-center mt-3">
            <a href="users.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> 返回</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

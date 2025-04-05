<?php
require_once __DIR__ . '/../../includes/config.php';
require INCLUDE_PATH . '/db.php';
require INCLUDE_PATH . '/auth.php';
require INCLUDE_PATH . '/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// 初始化变量
$role_id = $_GET['role_id'] ?? null;
$role_name = '';
$role_desc = '';
$error = '';

// 如果是编辑模式，获取当前角色信息
if ($role_id) {
    $sql = "SELECT * FROM roles WHERE role_id = :role_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':role_id', $role_id);
    $stmt->execute();
    $role = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($role) {
        $role_name = $role['role_name'];
        $role_desc = $role['role_desc'];
    } else {
        $error = "Role not found.";
    }
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role_name = trim($_POST['role_name']);
    $role_desc = trim($_POST['role_desc']);

    if (empty($role_name)) {
        $error = "Role name is required.";
    } elseif (empty($role_desc)) {
        $error = "Role description is required.";
    } else {
        $sql = "SELECT * FROM roles WHERE role_name = :role_name" . ($role_id ? " AND role_id != :role_id" : "");
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':role_name', $role_name);
        if ($role_id) {
            $stmt->bindParam(':role_id', $role_id);
        }
        $stmt->execute();
        $existingRole = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingRole) {
            $error = "Role name already exists.";
        } else {
            if ($role_id) {
                $sql = "UPDATE roles SET role_name = :role_name, role_desc = :role_desc WHERE role_id = :role_id";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':role_name', $role_name);
                $stmt->bindParam(':role_desc', $role_desc);
                $stmt->bindParam(':role_id', $role_id);
            } else {
                $sql = "INSERT INTO roles (role_name, role_desc) VALUES (:role_name, :role_desc)";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':role_name', $role_name);
                $stmt->bindParam(':role_desc', $role_desc);
            }

            if ($stmt->execute()) {
                header("Location: roles.php");
                exit;
            } else {
                $error = "Error saving role.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $role_id ? '编辑角色' : '创建角色'; ?></title>

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
        <h2><i class="fas fa-user-shield"></i> <?php echo $role_id ? '编辑角色' : '创建角色'; ?></h2>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label for="role_name" class="form-label"><i class="fas fa-id-badge"></i> 角色名称</label>
                <input type="text" class="form-control" id="role_name" name="role_name" placeholder="Enter role name"
                    value="<?php echo htmlspecialchars($role_name); ?>" required maxlength="50">
            </div>

            <div class="mb-3">
                <label for="role_desc" class="form-label"><i class="fas fa-align-left"></i> 描述</label>
                <textarea class="form-control" id="role_desc" name="role_desc" rows="3" placeholder="Enter role description"
                    required maxlength="200"><?php echo htmlspecialchars($role_desc); ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-save"></i> <?php echo $role_id ? '更新角色' : '创建角色'; ?>
            </button>
        </form>

        <div class="text-center mt-3">
            <a href="roles.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> 返回</a>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>

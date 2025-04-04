<?php
require '../includes/db.php';
require '../includes/auth.php';
require '../includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$role_id = $_GET['role_id'] ?? null;
if (!$role_id) {
    die("角色 ID 不存在！");
}

// 获取所有模块
$modules = $conn->query("SELECT * FROM modules")->fetchAll(PDO::FETCH_ASSOC);

// 获取当前角色的已分配模块
$sql = "SELECT module_id FROM role_permissions WHERE role_id = :role_id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':role_id', $role_id);
$stmt->execute();
$assigned_modules = $stmt->fetchAll(PDO::FETCH_COLUMN);

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $selected_modules = $_POST['modules'] ?? [];
    
    // 清空当前角色的模块分配
    $conn->prepare("DELETE FROM role_permissions WHERE role_id = ?")->execute([$role_id]);

    // 插入新的模块分配
    $stmt = $conn->prepare("INSERT INTO role_permissions (role_id, module_id) VALUES (?, ?)");
    
    foreach ($selected_modules as $module_id) {
        $stmt->execute([$role_id, $module_id]);
    }

    header("Location: roles.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>分配模块</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .form-container { max-width: 600px; margin: 50px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }
        .form-container h2 { text-align: center; margin-bottom: 20px; }
        .module-list { max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>

    <div class="form-container">
        <h2><i class="fas fa-tasks"></i> 分配模块</h2>

        <form method="POST">
            <div class="module-list">
                <?php foreach ($modules as $module): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="modules[]" value="<?= $module['module_id'] ?>"
                            <?= in_array($module['module_id'], $assigned_modules) ? 'checked' : '' ?>>
                        <label class="form-check-label"><?= htmlspecialchars($module['module_name']) ?></label>
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="submit" class="btn btn-primary w-100 mt-3"><i class="fas fa-save"></i> 保存分配</button>
        </form>

        <div class="text-center mt-3">
            <a href="roles.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> 返回</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

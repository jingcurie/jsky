<?php
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDE_PATH . '/db.php';
require_once INCLUDE_PATH . '/check_ip_whitelist.php';
require_once INCLUDE_PATH . '/auth.php';
require_once INCLUDE_PATH . '/functions.php';

if (!isLoggedIn()) {
    redirect('/admin/login.php');
}

$is_admin = ($_SESSION['role_id'] ?? 0) == 1;
if (!$is_admin) {
    system_message('您无权限访问该页面', 'danger', '权限不足', 'fas fa-ban');
}

$allowed_modules = [
    'articles' => ['table' => 'articles', 'pk' => 'id', 'name_field' => 'title', 'label' => '文章管理', 'fields' => ['author', 'status']],
    'categories' => ['table' => 'categories', 'pk' => 'id', 'name_field' => 'name', 'label' => '分类管理', 'fields' => ['description']],
    'modules' => ['table' => 'modules', 'pk' => 'module_id', 'name_field' => 'module_name', 'label' => '模块管理', 'fields' => ['module_url']],
    'users' => ['table' => 'users', 'pk' => 'user_id', 'name_field' => 'username', 'label' => '用户管理', 'fields' => ['email']],
    'roles' => ['table' => 'roles', 'pk' => 'role_id', 'name_field' => 'role_name', 'label' => '角色管理', 'fields' => ['role_desc']],
];

$module_key = $_GET['module'] ?? 'articles';
if (!isset($allowed_modules[$module_key])) die("模块非法");

$module = $allowed_modules[$module_key];
$table = $module['table'];
$pk = $module['pk'];
$name_field = $module['name_field'];
$fields = $module['fields'];

$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$keyword = $_GET['keyword'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$page_size = 20;
$offset = ($page - 1) * $page_size;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfProtect();
    $selected_ids = $_POST['selected_ids'] ?? [];

    if (isset($_POST['bulk_recover']) && is_array($selected_ids)) {
        $recovered_count = 0;
        foreach ($selected_ids as $id) {
            $record = getById($conn, $table, $pk, $id);
            $desc = $record[$name_field] ?? '[已无法获取名称]';
            $success = update($conn, $table, $pk, $id, ['is_deleted' => 0, 'deleted_at' => null, 'deleted_by' => null]);
            if ($success) {
                log_operation($conn, $_SESSION['user_id'], $_SESSION['username'], '恢复', '回收站 - ' . $module['label'], $id, $desc);
                $recovered_count++;
            }
        }
        $_SESSION['success_message'] = "成功恢复 {$recovered_count} 条记录。";
    }

    if (isset($_POST['bulk_delete']) && is_array($selected_ids)) {
        $deleted_count = 0;
        foreach ($selected_ids as $id) {
            $record = getById($conn, $table, $pk, $id);
            $desc = $record[$name_field] ?? '[已无法获取名称]';
            if (delete($conn, $table, $pk, $id)) {
                log_operation($conn, $_SESSION['user_id'], $_SESSION['username'], '彻底删除', '回收站 - ' . $module['label'], $id, $desc);
                $deleted_count++;
            }
        }
        $_SESSION['success_message'] = "已彻底删除 {$deleted_count} 条记录。";
    }

    if (isset($_POST['clear_all'])) {
        $deleted_count = execute($conn, "DELETE FROM $table WHERE is_deleted = 1");
        log_operation($conn, $_SESSION['user_id'], $_SESSION['username'], '清空回收站', '回收站 - ' . $module['label']);
        $_SESSION['success_message'] = "已清空回收站，共删除 {$deleted_count} 条记录。";
    }

    $query_params = http_build_query([
        'module' => $module_key,
        'page' => $page,
        'start_date' => $start_date,
        'end_date' => $end_date,
        'keyword' => $keyword,
    ]);
    redirect("trash.php?$query_params");
}

$where = "t.is_deleted = 1";
$params = [];

if ($start_date) {
    $where .= " AND t.deleted_at >= ?";
    $params[] = "$start_date 00:00:00";
}
if ($end_date) {
    $where .= " AND t.deleted_at <= ?";
    $params[] = "$end_date 23:59:59";
}
if ($keyword) {
    $where .= " AND t.$name_field LIKE ?";
    $params[] = "%$keyword%";
}

$total = query($conn, "SELECT COUNT(*) as count FROM $table t WHERE $where", $params)[0]['count'];
$items = query($conn,
    "SELECT t.*, u.username FROM $table t LEFT JOIN users u ON t.deleted_by = u.user_id WHERE $where ORDER BY deleted_at DESC LIMIT $page_size OFFSET $offset",
    $params
);

$total_pages = max(1, ceil($total / $page_size));
?>
<!DOCTYPE html>
<html lang="zh">
<head>
  <meta charset="UTF-8">
  <title>回收站 - <?= $module['label'] ?></title>
  <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="/assets/css/all.min.css">
  <link href="<?= CSS_URL ?>/admin_style.css" rel="stylesheet">
  <style>
    .toast-container {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 9999;
    }
  </style>
</head>
<body>
<div class="container mt-4">
  <h2><i class="fas fa-trash-alt"></i> 回收站 - <?= $module['label'] ?></h2>

  <!-- 标签切换 -->
  <ul class="nav nav-tabs mb-3">
    <?php foreach ($allowed_modules as $key => $mod): ?>
      <li class="nav-item">
        <a class="nav-link <?= $key === $module_key ? 'active' : '' ?>" href="?module=<?= $key ?>&<?= http_build_query(['start_date' => $start_date, 'end_date' => $end_date, 'keyword' => $keyword]) ?>">
          <?= $mod['label'] ?>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>

  <!-- 提示信息作为 Toast 弹出 -->
  <?php if (!empty($_SESSION['success_message'])): ?>
    <div class="toast-container">
      <div class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
          <div class="toast-body">
            <?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- 筛选表单 -->
  <form method="get" class="row mb-3">
    <input type="hidden" name="module" value="<?= $module_key ?>">
    <div class="col-md-3"><input type="date" class="form-control" name="start_date" value="<?= htmlspecialchars($start_date) ?>"></div>
    <div class="col-md-3"><input type="date" class="form-control" name="end_date" value="<?= htmlspecialchars($end_date) ?>"></div>
    <div class="col-md-3"><input type="text" class="form-control" name="keyword" value="<?= htmlspecialchars($keyword) ?>" placeholder="关键词"></div>
    <div class="col-md-2"><button class="btn btn-edit btn-sm w-100" type="submit"><i class="fas fa-search"></i> 筛选</button></div>
  </form>

  <!-- 操作按钮 -->
  <form method="post" onsubmit="return confirm('确认执行该操作？')">
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
    <div class="mb-2 d-flex gap-2">
      <a href="?module=<?= $module_key ?>" class="btn btn-create btn-sm"><i class="fas fa-sync-alt"></i> 刷新</a>
      <button name="bulk_recover" class="btn btn-edit btn-sm"><i class="fas fa-undo"></i> 批量恢复</button>
      <button name="bulk_delete" class="btn btn-delete btn-sm"><i class="fas fa-times"></i> 彻底删除</button>
      <button name="clear_all" class="btn btn-danger btn-sm" value="1" onclick="return confirm('确定清空该模块回收站中所有记录吗？此操作不可恢复！')">
        <i class="fas fa-trash"></i> 清空全部
      </button>
    </div>

    <!-- 数据表格 -->
    <table class="table table-bordered table-hover">
      <thead>
        <tr>
          <th><input type="checkbox" onclick="toggleAll(this)"></th>
          <th>ID</th>
          <th>名称</th>
          <?php foreach ($fields as $f): ?><th><?= $f ?></th><?php endforeach; ?>
          <th>删除时间</th>
          <th>操作人</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $item): ?>
        <tr>
          <td><input type="checkbox" name="selected_ids[]" value="<?= $item[$pk] ?>"></td>
          <td><?= $item[$pk] ?></td>
          <td>
            <?= htmlspecialchars($item[$name_field]) ?>
            <i class="fas fa-info-circle text-muted" title="<?php foreach ($fields as $f) echo $f . ': ' . htmlspecialchars($item[$f]) . '&#10;'; ?>"></i>
          </td>
          <?php foreach ($fields as $f): ?>
            <td><?= $f === 'content' ? mb_substr(strip_tags(htmlspecialchars_decode($item[$f])), 0, 40) . '...' : htmlspecialchars($item[$f]) ?></td>
          <?php endforeach; ?>
          <td><?= $item['deleted_at'] ?></td>
          <td><?= htmlspecialchars($item['username'] ?? '未知') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </form>

  <!-- 分页区域 -->
  <div class="d-flex justify-content-between align-items-center mt-3">
    <div>共 <?= $total ?> 条记录，第 <?= $page ?>/<?= $total_pages ?> 页</div>
    <div class="d-flex align-items-center gap-2">
      <?php if ($page > 1): ?>
        <a class="btn btn-sm btn-secondary" href="?<?= http_build_query(['module' => $module_key, 'page' => $page - 1, 'start_date' => $start_date, 'end_date' => $end_date, 'keyword' => $keyword]) ?>">上一页</a>
      <?php endif; ?>

      <select class="form-select form-select-sm" style="width:auto;" onchange="location.href='?<?= http_build_query(['module' => $module_key, 'start_date' => $start_date, 'end_date' => $end_date, 'keyword' => $keyword]) ?>&page=' + this.value">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
          <option value="<?= $i ?>" <?= $i == $page ? 'selected' : '' ?>>跳转第 <?= $i ?> 页</option>
        <?php endfor; ?>
      </select>

      <?php if ($page < $total_pages): ?>
        <a class="btn btn-sm btn-secondary" href="?<?= http_build_query(['module' => $module_key, 'page' => $page + 1, 'start_date' => $start_date, 'end_date' => $end_date, 'keyword' => $keyword]) ?>">下一页</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
  function toggleAll(master) {
    document.querySelectorAll('input[name="selected_ids[]"]').forEach(cb => cb.checked = master.checked);
  }

  window.addEventListener('load', function () {
    document.querySelectorAll('.toast').forEach(function(toastEl) {
      new bootstrap.Toast(toastEl, { delay: 4000 }).show();
    });
  });
</script>
<script src="/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
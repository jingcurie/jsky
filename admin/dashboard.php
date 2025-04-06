<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once INCLUDE_PATH . '/db.php';
require_once INCLUDE_PATH . '/auth.php';
require_once INCLUDE_PATH . '/functions.php';
?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= CSS_URL ?>/admin_style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            background-color: #f0f2f5;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #333;
        }
        .dashboard-header h2 {
            font-weight: 600;
        }
        .card-custom {
            border: 1px solid #e0e0e0;
            background-color: #fff;
            transition: box-shadow .3s;
        }
        .card-custom:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .card-icon {
            font-size: 1.8rem;
            color: #4A6FA5;
            margin-right: 15px;
        }
        .table th, .table td {
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">

        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card card-custom p-3 d-flex flex-row align-items-center">
                    <i class="fas fa-newspaper card-icon"></i>
                    <div>
                        <h6 class="mb-1">文章总数</h6>
                        <strong>128 篇</strong>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-custom p-3 d-flex flex-row align-items-center">
                    <i class="fas fa-users card-icon"></i>
                    <div>
                        <h6 class="mb-1">用户数量</h6>
                        <strong>24 位</strong>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-custom p-3 d-flex flex-row align-items-center">
                    <i class="fas fa-eye card-icon"></i>
                    <div>
                        <h6 class="mb-1">今日浏览</h6>
                        <strong>320 次</strong>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-custom p-3 d-flex flex-row align-items-center">
                    <i class="fas fa-exclamation-triangle card-icon"></i>
                    <div>
                        <h6 class="mb-1">待处理</h6>
                        <strong>3 条</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-custom shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">近期文章</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>标题</th>
                            <th>作者</th>
                            <th>发布时间</th>
                            <th>浏览量</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>关于公司搬迁通知</td>
                            <td>Admin</td>
                            <td>2025-04-01</td>
                            <td>84</td>
                        </tr>
                        <!-- 可循环加载更多 -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        setInterval(() => {
            const now = new Date();
            document.getElementById("refresh-time").innerText = now.toLocaleTimeString('zh-CN', { hour12: false });
        }, 1000);
    </script>
</body>
</html>

    <!DOCTYPE html>
    <html lang="zh">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>站点设置</title>
        <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"> -->
        <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
        <link rel="stylesheet" href="/assets/css/all.min.css">
        <link href="<?= CSS_URL ?>/admin_style.css" rel="stylesheet">
        <style>
            .tab-content {
                padding: 20px;
                border-left: 1px solid #dee2e6;
                border-right: 1px solid #dee2e6;
                border-bottom: 1px solid #dee2e6;
                border-radius: 0 0 5px 5px;
            }

            .nav-tabs .nav-link.active {
                font-weight: bold;
            }

            .logo-preview {
                max-width: 200px;
                max-height: 100px;
                margin-top: 10px;
            }

            .banner-item {
                position: relative;
                margin-bottom: 15px;
                padding: 10px;
                border: 1px solid #ddd;
                border-radius: 5px;
            }

            .banner-actions {
                position: absolute;
                top: 10px;
                right: 10px;
            }
        </style>
    </head>

    <body>
        <div class="container">
            <h2><i class="fas fa-cog"></i> 站点设置</h2>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

                <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic" type="button" role="tab">基本设置</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="home-tab" data-bs-toggle="tab" data-bs-target="#home" type="button" role="tab">首页内容</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="seo-tab" data-bs-toggle="tab" data-bs-target="#seo" type="button" role="tab">SEO设置</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="social-tab" data-bs-toggle="tab" data-bs-target="#social" type="button" role="tab">社交媒体</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="contact-tab" data-bs-toggle="tab" data-bs-target="#contact" type="button" role="tab">联系方式</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="ip-tab" data-bs-toggle="tab" data-bs-target="#ip-whitelist" type="button" role="tab">IP白名单</button>
                    </li>

                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="performance-tab" data-bs-toggle="tab" data-bs-target="#performance" type="button" role="tab">性能设置</button>
                    </li>
                </ul>

                <div class="tab-content" id="settingsTabsContent">
                    <!-- 基本设置 -->
                    <div class="tab-pane fade show active" id="basic" role="tabpanel">
                        <div class="mb-3">
                            <label for="site_title" class="form-label">网站标题</label>
                            <input type="text" class="form-control" id="site_title" name="site_title"
                                value="<?php echo htmlspecialchars($settings['site_title'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="site_description" class="form-label">网站描述</label>
                            <textarea class="form-control" id="site_description" name="site_description" rows="3"><?php
                                                                                                                    echo htmlspecialchars($settings['site_description'] ?? '');
                                                                                                                    ?></textarea>
                        </div>

                        <!-- <div class="mb-3">
                            <label for="site_logo" class="form-label">网站LOGO</label>
                            <input type="file" class="form-control" id="site_logo" name="site_logo"
                                accept="image/jpeg, image/png, image/webp">

                    
                            <?php if (!empty($settings['site_logo'])): ?>
                                <div class="mt-2">
                                    <img src="<?= LOGO_URL ?>/<?= htmlspecialchars($settings['site_logo']) ?>"
                                        class="img-thumbnail" style="max-height: 100px;">
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox"
                                            id="remove_logo" name="remove_logo" value="1">
                                        <label class="form-check-label" for="remove_logo">移除LOGO</label>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div> -->
                        <!--  主 LOGO（PC端） -->
                        <div class="mb-3">
                            <label for="site_logo_large" class="form-label">大 LOGO</label>
                            <input type="file" class="form-control" name="site_logo_large" accept="image/png,image/jpeg">

                            <?php if (!empty($settings['site_logo_large'])): ?>
                                <div class="mt-2">
                                    <img src="/uploads/logos/<?= htmlspecialchars($settings['site_logo_large'] ?? '') ?>" class="img-thumbnail logo-preview">
                                </div>
                            <?php endif; ?>

                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="remove_logo_large" value="1" id="remove_logo_large">
                                <label class="form-check-label" for="remove_logo_large">移除主 LOGO</label>
                            </div>
                        </div>

                        <!-- small LOGO（Mobile） -->
                        <div class="mb-3">
                            <label for="site_logo_small" class="form-label">小 LOGO</label>
                            <input type="file" class="form-control" name="site_logo_small" accept="image/png,image/jpeg">

                            <?php if (!empty($settings['site_logo_small'])): ?>
                                <div class="mt-2">
                                    <img src="/uploads/logos/<?= htmlspecialchars($settings['site_logo_small'] ?? '') ?>" class="img-thumbnail logo-preview">
                                </div>
                            <?php endif; ?>

                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="remove_logo_small" value="1" id="remove_logo_small">
                                <label class="form-check-label" for="remove_logo_small">移除小 LOGO</label>
                            </div>
                        </div>
                    </div>

                    <!-- 首页内容 -->
                    <div class="tab-pane fade" id="home" role="tabpanel">
                        <h4>Banner管理</h4>
                        <div id="banners-container">
                            <?php foreach ($banners as $banner): ?>
                                <div class="banner-item">
                                    <div class="banner-actions">
                                        <a href="banner_form.php?id=<?php echo $banner['id']; ?>" class="btn btn-sm btn-edit">
                                            <i class="fas fa-edit">修改</i>
                                        </a>

                                        <button type="button" class="btn btn-sm btn-delete"
                                            onclick="openDeleteModal2('Banner', '<?= $banner['id'] ?>', 'banner')">
                                            <i class="fas fa-trash">删除</i>
                                        </button>
                                    </div>
                                    <img src="<?= BANNER_URL ?>/<?php echo htmlspecialchars($banner['image_path']); ?>" style="max-width: 100%; max-height: 150px;">
                                    <div class="mt-2">
                                        <strong>描述:</strong> <?php echo htmlspecialchars($banner['description']); ?>
                                    </div>
                                    <div class="mt-2">
                                        <strong>链接:</strong> <?php echo htmlspecialchars($banner['url']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="mt-3">
                            <a href="banner_form.php" class="btn btn-create">
                                <i class="fas fa-plus"></i> 添加Banner
                            </a>
                        </div>
                    </div>

                    <!-- SEO设置 -->
                    <div class="tab-pane fade" id="seo" role="tabpanel">
                        <div class="mb-3">
                            <label for="meta_keywords" class="form-label">Meta关键词</label>
                            <textarea class="form-control" id="meta_keywords" name="meta_keywords" rows="3"><?php
                                                                                                            echo htmlspecialchars($settings['meta_keywords'] ?? '');
                                                                                                            ?></textarea>
                            <small class="text-muted">多个关键词用逗号分隔</small>
                        </div>

                        <div class="mb-3">
                            <label for="meta_description" class="form-label">Meta描述</label>
                            <textarea class="form-control" id="meta_description" name="meta_description" rows="3"><?php
                                                                                                                    echo htmlspecialchars($settings['meta_description'] ?? '');
                                                                                                                    ?></textarea>
                        </div>
                    </div>

                    <!-- 社交媒体 -->
                    <div class="tab-pane fade" id="social" role="tabpanel">
                        <div class="mb-3">
                            <label for="facebook_url" class="form-label">Facebook链接</label>
                            <input type="url" class="form-control" id="facebook_url" name="facebook_url"
                                value="<?php echo htmlspecialchars($settings['facebook_url'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="twitter_url" class="form-label">Twitter链接</label>
                            <input type="url" class="form-control" id="twitter_url" name="twitter_url"
                                value="<?php echo htmlspecialchars($settings['twitter_url'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- 联系方式 -->
                    <div class="tab-pane fade" id="contact" role="tabpanel">
                        <div class="mb-3">
                            <label for="contact_email" class="form-label">联系邮箱</label>
                            <input type="email" class="form-control" id="contact_email" name="contact_email"
                                value="<?php echo htmlspecialchars($settings['contact_email'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="contact_phone" class="form-label">联系电话</label>
                            <input type="tel" class="form-control" id="contact_phone" name="contact_phone"
                                value="<?php echo htmlspecialchars($settings['contact_phone'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- IP白名单设置 -->
                    <div class="tab-pane fade" id="ip-whitelist" role="tabpanel">

                        <div class="mb-3">
                            <label for="ip_list" class="form-label">IP白名单列表</label>
                            <textarea class="form-control" id="ip_list" name="ip_list" rows="10" placeholder="每行输入一个IP或CIDR，例如：192.168.1.1 或 10.0.0.0/24"><?php
                                                                                                                                                            if (!empty($whitelisted_ips)) {
                                                                                                                                                                $ips = array_column($whitelisted_ips, 'ip_address');
                                                                                                                                                                echo htmlspecialchars(implode("\n", $ips));
                                                                                                                                                            }
                                                                                                                                                            ?></textarea>
                            <small class="form-text text-muted">支持IP或CIDR格式，每行一个。例如：123.45.67.89 或 192.168.1.0/24。系统会自动去重和排序。</small>

                            <div id="ip_list_error" class="text-danger mt-2" style="display: none;"></div>
                        </div>


                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="ip_whitelist_enabled" name="ip_whitelist_enabled" <?= !empty($settings['ip_whitelist_enabled']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="ip_whitelist_enabled">启用 IP 白名单限制</label>
                            <small class="text-muted d-block mt-1">启用后，只有白名单内的 IP 可以访问后台管理系统。</small>
                        </div>
                    </div>

                    <!-- 性能设置 -->
                    <div class="tab-pane fade" id="performance" role="tabpanel">
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="cache_enabled" name="cache_enabled"
                                <?php echo (!empty($settings['cache_enabled']) && $settings['cache_enabled'] == 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="cache_enabled">启用缓存</label>
                        </div>

                        <div class="mb-3">
                            <button type="button" class="btn btn-warning" onclick="clearCache()">
                                <i class="fas fa-broom"></i> 清除缓存
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 保存设置
                    </button>
                </div>
            </form>
        </div>

        <!-- 引入通用删除模态框 -->
        <?php require INCLUDE_PATH . '/delete_modal.php'; ?>

        <!-- Bootstrap JS -->
        <script src="/assets/js/bootstrap.bundle.min.js"></script>
        <script src="<?= JS_URL ?>/admin.js"></script>

        <script>
            // 根据哈希值激活对应标签页
            if (window.location.hash === '#home') {
                new bootstrap.Tab(document.getElementById('home-tab')).show();
            }

            if (window.location.hash === '#whitelist') {
                new bootstrap.Tab(document.getElementById('ip-tab')).show();
            }

            function clearCache() {
                if (confirm('确定要清除所有缓存吗？')) {
                    fetch('clear_cache.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('缓存已清除！');
                            } else {
                                alert('清除缓存失败: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('清除缓存时出错');
                        });
                }
            }
        </script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const ipListTextarea = document.getElementById('ip_list');
                const errorDiv = document.getElementById('ip_list_error');
                const form = ipListTextarea.closest('form');

                function validateAndCleanIpList() {
                    let lines = ipListTextarea.value.split('\n').map(line => line.trim()).filter(line => line !== '');
                    let validIps = [];
                    let invalidIps = [];

                    const ipRegex = /^(?:\d{1,3}\.){3}\d{1,3}(?:\/\d{1,2})?$/;

                    lines.forEach(ip => {
                        if (ipRegex.test(ip)) {
                            validIps.push(ip);
                        } else {
                            invalidIps.push(ip);
                        }
                    });

                    // 如果有非法IP
                    if (invalidIps.length > 0) {
                        errorDiv.style.display = 'block';
                        errorDiv.textContent = '检测到无效的IP地址：' + invalidIps.join(', ');
                        form.querySelector('button[type="submit"]').disabled = true;
                    } else {
                        errorDiv.style.display = 'none';
                        form.querySelector('button[type="submit"]').disabled = false;

                        // 去重并排序
                        validIps = Array.from(new Set(validIps));
                        validIps.sort((a, b) => a.localeCompare(b, undefined, {
                            numeric: true,
                            sensitivity: 'base'
                        }));

                        ipListTextarea.value = validIps.join('\n');
                    }
                }

                ipListTextarea.addEventListener('blur', validateAndCleanIpList); // 离开时校验
            });
        </script>

    </body>

    </html>
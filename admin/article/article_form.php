<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../includes/config.php';
require_once INCLUDE_PATH . '/db.php';
require_once INCLUDE_PATH . '/auth.php';
require_once INCLUDE_PATH . '/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if (isset($_GET['category_id'])) {
    $category_id = intval($_GET['category_id']);
}

// 获取所有分类
// $categories = $conn->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
// $category_id = $article['category_id'] ?? ''; // 如果是编辑模式，则获取文章的分类ID


// 获取文章 ID
$article_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$title = "";
$content = "";
$author = "";
$created_at = "";

$cover_image = ""; // 默认值，避免未定义
$category_name = "";

if ($category_id > 0){
    $category = getById($conn, "categories", "id", $category_id);
    $category_name = $category["name"];
}

// 如果是编辑模式，获取文章数据
if ($article_id > 0) {
    $article = getById($conn, "articles", "id", $article_id);

    if ($article) {
        $title = $article['title'];
        $content = $article['content'];
        $author = $article['author'];
        $created_at = $article['created_at'];
        $cover_image = $article['cover_image'];
        $status = $article['status'] ?? 'draft'; // 新增状态字段
    }

}
?>

<!DOCTYPE html>
<html lang="zh">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $article_id > 0 ? "编辑文章" : "发布文章" ?></title>

    <!-- Bootstrap + FontAwesome -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- TinyMCE -->
    <script src="<?= JS_URL ?>/tinymce/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            license_key: 'gpl',
            selector: '#content',
            language: 'zh_CN',
            language_url: '<?= JS_URL ?>/tinymce/langs/zh_CN.js',

            setup: (editor) => {
                // 定义多个模板
                const templates = [{
                        title: '新闻模板',
                        content: '<h2 style="color: #2e6c80;">新闻标题</h2><p>正文内容...</p>'
                    },
                    {
                        title: '产品介绍',
                        content: '<div class="product"><h3>产品名称</h3><p>特点：...</p></div>'
                    },
                    {
                        title: '两栏布局',
                        content: '<div style="display: flex;"><div style="flex:1;">左栏</div><div style="flex:1;">右栏</div></div>'
                    }
                ];

                // 添加下拉菜单按钮
                editor.ui.registry.addMenuButton('templates', {
                    text: '插入模板',
                    fetch: (callback) => {
                        // 将模板转换为菜单项
                        const items = templates.map(tpl => ({
                            type: 'menuitem',
                            text: tpl.title,
                            onAction: () => editor.insertContent(tpl.content)
                        }));
                        callback(items);
                    }
                });

                editor.on('NodeChange', function(e) {
                    var img = editor.selection.getNode();
                    if (img.nodeName === 'IMG' && img.style.float === 'left') {
                        img.style.marginRight = '15px'; // ✅ 自动添加右边距
                        img.style.marginBottom = '10px'; // ✅ 也可以加一点底部间距
                    }
                });

                // ✅ 添加自定义 SVG 图标
                editor.ui.registry.addIcon('custom-padding-icon', `
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="2" y="2" width="20" height="20" stroke="black" stroke-width="2" fill="none"/>
        <line x1="6" y1="6" x2="18" y2="6" stroke="black" stroke-width="2"/>
        <line x1="6" y1="18" x2="18" y2="18" stroke="black" stroke-width="2"/>
        <line x1="6" y1="6" x2="6" y2="18" stroke="black" stroke-width="2"/>
        <line x1="18" y1="6" x2="18" y2="18" stroke="black" stroke-width="2"/>
      </svg>
    `);
                editor.ui.registry.addButton('customTableCellProps', {
                    icon: 'custom-padding-icon',
                    // editor.ui.registry.addButton('customTableCellProps', {
                    //     icon: 'table-cell-properties', // 使用内置图标
                    tooltip: '设置单元格 Padding',
                    onAction: function() {
                        var cell = editor.selection.getNode();
                        if (cell.nodeName !== 'TD' && cell.nodeName !== 'TH') {
                            editor.windowManager.alert('请先选中一个表格单元格！');
                            return;
                        }

                        // 获取当前的 padding 值，默认为 0px
                        var computedStyle = window.getComputedStyle(cell);
                        var paddingTop = computedStyle.paddingTop.replace('px', '') || '0';
                        var paddingRight = computedStyle.paddingRight.replace('px', '') || '0';
                        var paddingBottom = computedStyle.paddingBottom.replace('px', '') || '0';
                        var paddingLeft = computedStyle.paddingLeft.replace('px', '') || '0';

                        editor.windowManager.open({
                            title: '设置单元格内边距',
                            body: {
                                type: 'panel',
                                items: [{
                                        type: 'input',
                                        name: 'paddingTop',
                                        label: '上边距',
                                        inputMode: 'numeric',
                                        value: paddingTop
                                    },
                                    {
                                        type: 'input',
                                        name: 'paddingRight',
                                        label: '右边距',
                                        inputMode: 'numeric',
                                        value: paddingRight
                                    },
                                    {
                                        type: 'input',
                                        name: 'paddingBottom',
                                        label: '下边距',
                                        inputMode: 'numeric',
                                        value: paddingBottom
                                    },
                                    {
                                        type: 'input',
                                        name: 'paddingLeft',
                                        label: '左边距',
                                        inputMode: 'numeric',
                                        value: paddingLeft
                                    }
                                ]
                            },
                            buttons: [{
                                    type: 'cancel',
                                    text: '取消'
                                },
                                {
                                    type: 'submit',
                                    text: '应用',
                                    primary: true
                                }
                            ],
                            onSubmit: function(api) {
                                var data = api.getData();
                                cell.style.paddingTop = data.paddingTop + 'px';
                                cell.style.paddingRight = data.paddingRight + 'px';
                                cell.style.paddingBottom = data.paddingBottom + 'px';
                                cell.style.paddingLeft = data.paddingLeft + 'px';
                                api.close();
                            }
                        });
                    }
                });

                // ✅ 自定义 "浮动 <span>" 按钮
                editor.ui.registry.addToggleButton('floatSpan', {
                    text: '浮动文字',
                    tooltip: '让选中的 <span> 左浮动/右浮动',
                    onAction: function() {
                        let node = editor.selection.getNode();
                        if (node.nodeName === 'SPAN') {
                            if (node.style.float === 'left') {
                                node.style.float = 'right';
                                node.classList.remove('float-left');
                                node.classList.add('float-right');
                            } else {
                                node.style.float = 'left';
                                node.classList.remove('float-right');
                                node.classList.add('float-left');
                            }
                        } else {
                            editor.windowManager.alert('请先选中一个 <span> 才能设置浮动！');
                        }
                    }
                });

                // ✅ 监听 <span> 变化，自动加 margin
                editor.on('NodeChange', function() {
                    let node = editor.selection.getNode();
                    if (node.nodeName === 'SPAN') {
                        if (node.style.float === 'left') {
                            node.style.marginRight = '15px';
                            node.style.marginBottom = '10px';
                        }
                        if (node.style.float === 'right') {
                            node.style.marginLeft = '15px';
                            node.style.marginBottom = '10px';
                        }
                    }
                });
            },


            plugins: 'advlist anchor autolink autosave charmap code codesample directionality emoticons fullscreen help image insertdatetime link lists media nonbreaking pagebreak preview quickbars save searchreplace table visualblocks visualchars wordcount',
            toolbar: [
                'undo redo | blocks fontsizeinput fontfamily ｜ bold italic underline strikethrough ｜ forecolor backcolor removeformat alignleft aligncenter alignright alignjustify floatSpan',
                'outdent indent | numlist bullist checklist subscript superscript | link image media table customTableCellProps charmap emoticons | ltr rtl | fullscreen preview visualblocks'
            ],
            table_advtab: true,
            table_cell_styles: true,
            fontsize_input: true, // 允许手动输入字体大小
            font_size_input_default_unit: "pt",
            font_family_formats: 'Arial=arial,helvetica,sans-serif; ' +
                '宋体=SimSun, serif; ' +
                '黑体=SimHei, sans-serif; ' +
                '微软雅黑=Microsoft YaHei, sans-serif; ' +
                '楷体=KaiTi, serif; ' +
                '仿宋=FangSong, serif;' + ",Andale Mono=andale mono,times; Arial=arial,helvetica,sans-serif; Arial Black=arial black,avant garde; Book Antiqua=book antiqua,palatino; Comic Sans MS=comic sans ms,sans-serif; Courier New=courier new,courier; Georgia=georgia,palatino; Helvetica=helvetica; Impact=impact,chicago; Oswald=oswald; Symbol=symbol; Tahoma=tahoma,arial,helvetica,sans-serif; Terminal=terminal,monaco; Times New Roman=times new roman,times; Trebuchet MS=trebuchet ms,geneva; Verdana=verdana,geneva; Webdings=webdings; Wingdings=wingdings,zapf dingbats",
            content_style: "body.mce-content-body { font-family: 'Microsoft YaHei', 'SimSun', 'SimHei', 'KaiTi', 'FangSong', Arial, sans-serif !important; }",
            toolbar_mode: 'scrolling',
            branding: false,
            min_height: 1200,
            image_advtab: true,
            images_upload_url: 'upload_richText_image.php', // 处理图片上传的 PHP 脚本
            automatic_uploads: true, // 允许自动上传
            file_picker_types: 'image', // 仅限图片
            file_picker_callback: function(cb, value, meta) {
                var input = document.createElement('input');
                input.setAttribute('type', 'file');
                input.setAttribute('accept', 'image/*');
                input.onchange = function() {
                    var file = this.files[0];
                    var formData = new FormData();
                    formData.append('file', file);
                    formData.append('is_rich_text', '1'); // 关键标识

                    fetch('upload_richText_image.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(result => {
                            if (result.location) {
                                cb(result.location);
                            } else {
                                alert('上传失败：' + (result.error || '未知错误'));
                            }
                        })
                        .catch(error => console.error('上传出错:', error));
                };
                input.click();
            },
            content_css: "<?= CSS_URL ?>/article_content_styles.css",
            image_class_list: [{
                    title: '无浮动',
                    value: ''
                },
                {
                    title: '左浮动',
                    value: 'align-left'
                },
                {
                    title: '右浮动',
                    value: 'align-right'
                },
                {
                    title: '居中',
                    value: 'mx-auto d-block'
                }
            ]
        });
    </script>



    <script src="<?= JS_URL ?>/upload.js"></script>
</head>

<body class="bg-light">
    <div class="container mt-5">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h2><i class="fas fa-edit"></i> <?= $article_id > 0 ? "编辑文章" : "发布文章" ?>（<?= htmlspecialchars($category_name) ?>）</h2>
            </div>
            <div class="card-body">
                <form action="publish.php" method="POST" onsubmit="return validateForm()">
                    <input type="hidden" name="id" value="<?= $article_id ?>">
                    <input type="hidden" name="category_id" value="<?= $category_id ?>">

                    <div class="mb-3">
                        <label hidden class="form-label">分类:</label>
                        <input hidden type="text" name="category" value="<?php echo $category_id ?>">
                    </div>


                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-file-alt"></i> 标题:</label>
                        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($title) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-image"></i> 封面图片:</label>
                        <div id="drop-area" class="border p-3 text-center">
                            <p>拖放图片到此处或点击选择文件</p>
                            <input type="file" id="cover_image_input" class="form-control" accept="image/jpeg, image/png">
                            <progress id="uploadProgress" value="0" max="100" style="width: 100%; display: none;"></progress>
                            <img id="cover_preview" src="<?php echo $cover_image ? ARTICLE_URL . htmlspecialchars($cover_image) : IMG_URL . "/default_cover_image.jpg" ?>"
                                class="img-thumbnail mt-2" style="max-width: 200px; display: <?= $cover_image ? 'block' : 'none' ?>;">
                        </div>
                        <input type="hidden" name="cover_image" id="cover_image" value="<?= htmlspecialchars($cover_image) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-newspaper"></i> 内容:</label>
                        <textarea id="content" name="content" class="form-control" height="500px"><?= htmlspecialchars_decode($content) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-feather-alt"></i> 发布者</label>
                        <input type="text" name="author" class="form-control" value="<?= !empty($author) ? htmlspecialchars($author) : "上海锦山汽车客运有限公司" ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-newspaper"></i> 发布时间:</label>

                        <?php
                        date_default_timezone_set('Asia/Shanghai');
                        ?>

                        <input type="datetime-local" class="form-control"
                            name="created_at"
                            value="<?= !empty($created_at)
                                        ? htmlspecialchars(date('Y-m-d\TH:i', strtotime($created_at)))
                                        : htmlspecialchars(date('Y-m-d\TH:i')) ?>"
                            required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-flag"></i> 文章状态:</label>
                        <select name="status" class="form-select" required>
                            <option value="draft" <?= ($status ?? 'draft') === 'draft' ? 'selected' : '' ?>>草稿</option>
                            <option value="published" <?= ($status ?? 'draft') === 'published' ? 'selected' : '' ?>>已发布</option>
                            <option value="archived" <?= ($status ?? 'draft') === 'archived' ? 'selected' : '' ?>>已归档</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> <?= $article_id > 0 ? "更新文章" : "发布文章" ?>
                    </button>
                    <a href="articles.php?category_id=<?php echo $category_id ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> 返回</a>
                </form>
            </div>
        </div>
    </div>

    <div id="alert-container" class="position-fixed top-50 start-50 translate-middle-x" style="z-index: 1050;"></div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
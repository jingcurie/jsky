<?php
//路径与 URL 设置
define('INCLUDE_PATH', __DIR__); //获得一个父亲路径

//物理PATH
define('UPLOAD_PATH', __DIR__ . '/../uploads');
define('BANNER_PATH', UPLOAD_PATH . '/banners/');
define('ARTICLE_PATH', UPLOAD_PATH . '/articles/');
define('LOGO_PATH', UPLOAD_PATH . '/logos/');

//相对URL
define('UPLOAD_URL','/uploads');
define('BANNER_URL', UPLOAD_URL . '/banners/');
define('ARTICLE_URL', UPLOAD_URL . '/articles/');
define('LOGO_URL', UPLOAD_URL . '/logos/');

define('IMG_URL', '/assets/images');
define('CSS_URL', '/assets/css');
define('JS_URL', '/assets/js');

#数据库连接配置
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'jsky');
?>
<?php
session_start();
$code = '';
$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
for ($i = 0; $i < 4; $i++) { // 验证码位数减为4位
    $code .= $chars[rand(0, strlen($chars) - 1)];
}
$_SESSION['captcha'] = $code;

$width = 90;
$height = 12;
$image = imagecreatetruecolor($width, $height);

// 背景色
$bgColor = imagecolorallocate($image, 255, 255, 255);
imagefill($image, 0, 0, $bgColor);

// 文本颜色
$textColor = imagecolorallocate($image, 0, 0, 0);

// 可选：增加一些干扰线
for ($i = 0; $i < 3; $i++) {
    $lineColor = imagecolorallocate($image, rand(100, 200), rand(100, 200), rand(100, 200));
    imageline($image, 0, rand(0, $height), $width, rand(0, $height), $lineColor);
}

// 写入验证码
$fontSize = 1;
$textX = 35;
$textY = 2.5;
imagestring($image, $fontSize, $textX, $textY, $code, $textColor);

header('Content-Type: image/png');
imagepng($image);
imagedestroy($image);

<?php
$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$code = '';
for ($i = 0; $i < 6; $i++) $code .= $chars[random_int(0, strlen($chars) - 1)];
$_SESSION['captcha'] = hash_hmac('sha256', strtolower($code), (string)cfg('captcha_secret'));   // answer lives in the session only

$img = imagecreatetruecolor(160, 50);
imagefill($img, 0, 0, imagecolorallocate($img, 15, 23, 42));
for ($i = 0; $i < 10; $i++) {
    $c = imagecolorallocate($img, random_int(70, 140), random_int(70, 140), random_int(120, 210));
    imageline($img, random_int(0, 160), random_int(0, 50), random_int(0, 160), random_int(0, 50), $c);
}
for ($i = 0; $i < 6; $i++)
    imagestring($img, 5, 14 + $i * 23, random_int(10, 26), $code[$i], imagecolorallocate($img, 165, 180, 252));
for ($i = 0; $i < 150; $i++) imagesetpixel($img, random_int(0, 159), random_int(0, 49), imagecolorallocate($img, 130, 130, 210));
header('Content-Type: image/png');
imagepng($img);

<?php

if (! extension_loaded('gd')) {
    fwrite(STDERR, "GD extension is required\n");
    exit(1);
}

$directory = __DIR__.'/../public/images/icons';
if (! is_dir($directory)) {
    mkdir($directory, 0777, true);
}

$sizes = [192, 512];

foreach ($sizes as $size) {
    $image = imagecreatetruecolor($size, $size);

    $background = imagecolorallocate($image, 2, 6, 23);
    imagefilledrectangle($image, 0, 0, $size, $size, $background);

    $accent = imagecolorallocate($image, 14, 165, 233);
    imagefilledellipse($image, (int) ($size / 2), (int) ($size / 2), (int) ($size * 0.72), (int) ($size * 0.72), $accent);

    $inner = imagecolorallocate($image, 56, 189, 248);
    imagefilledellipse($image, (int) ($size / 2), (int) ($size / 2), (int) ($size * 0.45), (int) ($size * 0.45), $inner);

    $textColor = imagecolorallocate($image, 248, 250, 252);
    $text = $size >= 256 ? 'WPS' : 'WP';
    $fontSize = $size >= 256 ? 5 : 4;
    $textWidth = imagefontwidth($fontSize) * strlen($text);
    $textHeight = imagefontheight($fontSize);
    $textX = (int) (($size - $textWidth) / 2);
    $textY = (int) (($size - $textHeight) / 2);
    imagestring($image, $fontSize, $textX, $textY, $text, $textColor);

    $path = sprintf('%s/icon-%d.png', $directory, $size);
    imagepng($image, $path, 9);
    imagedestroy($image);
}

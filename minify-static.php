<?php

// Iterate over all PNG files in the current directory and create a 100px thumbnail for each, named "thumb-<filename>.png", maintaining aspect ratio.

$files = glob('static/*.png');

foreach ($files as $file) {
  $image = imagecreatefrompng($file);
  $width = imagesx($image);
  $height = imagesy($image);
  $newWidth = 100;
  $newHeight = floor($height * ($newWidth / $width));
  $newImage = imagecreatetruecolor($newWidth, $newHeight);
  imagecopyresized($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
  imagepng($newImage, './static/thumb-' . $file);
  imagedestroy($image);
  imagedestroy($newImage);
}

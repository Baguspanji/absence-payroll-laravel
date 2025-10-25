<?php

if (!function_exists('compressImage')) {
    function compressImage($sourcePath, $destinationPath, $quality)
    {
        $info = getimagesize($sourcePath);

        if ($info['mime'] == 'image/jpeg') {
            $image = imagecreatefromjpeg($sourcePath);
        } elseif ($info['mime'] == 'image/gif') {
            $image = imagecreatefromgif($sourcePath);
        } elseif ($info['mime'] == 'image/png') {
            $image = imagecreatefrompng($sourcePath);
        } else {
            return false; // Unsupported image type
        }

        // Save the image with specified quality
        imagejpeg($image, $destinationPath, $quality);
        imagedestroy($image); // Free up memory
        return true;
    }
}

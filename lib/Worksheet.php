<?php
declare(strict_types=1);

class Worksheet
{
    /**
     * Build a grid of RGB pixels from an image
     */
    public static function buildGrid($img, int $grid): array
    {
        $w = imagesx($img);
        $h = imagesy($img);

        $cellW = max(1, (int)floor($w / $grid));
        $cellH = max(1, (int)floor($h / $grid));

        $data = [];
        $pixels = [];

        for ($y = 0; $y < $grid; $y++) {
            for ($x = 0; $x < $grid; $x++) {
                $px = min($w - 1, $x * $cellW);
                $py = min($h - 1, $y * $cellH);

                $rgb = imagecolorat($img, $px, $py);

                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                $pixel = [$r, $g, $b];
                $pixels[] = $pixel;
                $data[$y][$x] = $pixel;
            }
        }

        return [$data, $pixels];
    }

    /**
     * Resize image to fit within max dimensions while maintaining aspect ratio
     */
    public static function resizeImage($img, int $maxWidth, int $maxHeight, int $quality = 85)
    {
        $origWidth = imagesx($img);
        $origHeight = imagesy($img);

        // Calculate new dimensions maintaining aspect ratio
        $ratio = min($maxWidth / $origWidth, $maxHeight / $origHeight);
        
        if ($ratio >= 1) {
            return $img; // No resize needed
        }

        $newWidth = (int)($origWidth * $ratio);
        $newHeight = (int)($origHeight * $ratio);

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        if ($resized === false) {
            return null;
        }

        if (!imagecopyresampled($resized, $img, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight)) {
            imagedestroy($resized);
            return null;
        }

        imagedestroy($img);
        return $resized;
    }
}
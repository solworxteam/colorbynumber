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

    /**
     * Generate a colored preview image from the number grid and palette
     */
    public static function generateColoredPreview(array $numberGrid, array $palette, int $cellSize = 20): ?resource
    {
        $rows = count($numberGrid);
        if ($rows === 0) {
            return null;
        }

        $cols = count($numberGrid[0]);
        if ($cols === 0) {
            return null;
        }

        $width = $cols * $cellSize;
        $height = $rows * $cellSize;

        $img = imagecreatetruecolor($width, $height);
        if ($img === false) {
            return null;
        }

        // white background
        $white = imagecolorallocate($img, 255, 255, 255);
        imagefill($img, 0, 0, $white);

        // Fill cells with palette colors
        foreach ($numberGrid as $y => $row) {
            foreach ($row as $x => $Number) {
                $paletteIndex = (int)$Number - 1;
                
                if ($paletteIndex >= 0 && $paletteIndex < count($palette)) {
                    $colorData = $palette[$paletteIndex];
                    $r = (int)$colorData['rgb'][0];
                    $g = (int)$colorData['rgb'][1];
                    $b = (int)$colorData['rgb'][2];
                    $color = imagecolorallocate($img, $r, $g, $b);

                    $x1 = $x * $cellSize;
                    $y1 = $y * $cellSize;
                    $x2 = $x1 + $cellSize - 1;
                    $y2 = $y1 + $cellSize - 1;

                    imagefilledrectangle($img, $x1, $y1, $x2, $y2, $color);
                }
            }
        }

        return $img;
    }
}
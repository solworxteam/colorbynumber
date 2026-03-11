<?php
declare(strict_types=1);

class Worksheet
{
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

    public static function preprocessImage($img) {
        imagefilter($img, IMG_FILTER_CONTRAST, 20);
        imagefilter($img, IMG_FILTER_BRIGHTNESS, 5);
        imagefilter($img, IMG_FILTER_SMOOTH, 1);
        return $img;
    }

    public static function removeBackground($img, $tolerance = 40) {
        $w = imagesx($img);
        $h = imagesy($img);
        $cornerColors = [imagecolorat($img, 0, 0), imagecolorat($img, $w - 1, 0), imagecolorat($img, 0, $h - 1), imagecolorat($img, $w - 1, $h - 1)];
        $bgColor = $cornerColors[0];
        $bgR = ($bgColor >> 16) & 0xFF;
        $bgG = ($bgColor >> 8) & 0xFF;
        $bgB = $bgColor & 0xFF;
        $brightness = ($bgR + $bgG + $bgB) / 3;
        if ($brightness < 200) return $img;
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $rgb = imagecolorat($img, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $diff = abs($r - $bgR) + abs($g - $bgG) + abs($b - $bgB);
                if ($diff < $tolerance) {
                    $col = imagecolorallocate($img, 240, 240, 240);
                    imagesetpixel($img, $x, $y, $col);
                }
            }
        }
        return $img;
    }

    public static function buildAdaptiveGrid($img, $baseGridSize): array {
        $w = imagesx($img);
        $h = imagesy($img);
        $data = [];
        $pixels = [];
        $baseCellW = max(1, (int)floor($w / $baseGridSize));
        $baseCellH = max(1, (int)floor($h / $baseGridSize));
        for ($y = 0; $y < $baseGridSize; $y++) {
            for ($x = 0; $x < $baseGridSize; $x++) {
                // Sample 9 points in 3x3 grid and average for better color accuracy
                $avgR = 0; $avgG = 0; $avgB = 0;
                for ($dy = 0; $dy < 3; $dy++) {
                    for ($dx = 0; $dx < 3; $dx++) {
                        $px = min($w - 1, (int)($x * $baseCellW + $baseCellW * ($dx + 1) / 3));
                        $py = min($h - 1, (int)($y * $baseCellH + $baseCellH * ($dy + 1) / 3));
                        $rgb = imagecolorat($img, (int)$px, (int)$py);
                        $avgR += ($rgb >> 16) & 0xFF;
                        $avgG += ($rgb >> 8) & 0xFF;
                        $avgB += $rgb & 0xFF;
                    }
                }
                $r = (int)round($avgR / 9);
                $g = (int)round($avgG / 9);
                $b = (int)round($avgB / 9);
                $pixel = [$r, $g, $b];
                $pixels[] = $pixel;
                $data[$y][$x] = $pixel;
            }
        }
        return [$data, $pixels];
    }

    public static function resizeImage($img, int $maxWidth, int $maxHeight, int $quality = 85) {
        $origWidth = imagesx($img);
        $origHeight = imagesy($img);
        $ratio = min($maxWidth / $origWidth, $maxHeight / $origHeight);
        if ($ratio >= 1) return $img;
        $newWidth = (int)($origWidth * $ratio);
        $newHeight = (int)($origHeight * $ratio);
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        if ($resized === false) return null;
        if (!imagecopyresampled($resized, $img, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight)) {
            imagedestroy($resized);
            return null;
        }
        imagedestroy($img);
        return $resized;
    }

    public static function smoothAnomalies(array &$numberGrid) {
        $rows = count($numberGrid);
        if ($rows < 3) return;
        $cols = count($numberGrid[0]);
        if ($cols < 3) return;
        for ($y = 1; $y < $rows - 1; $y++) {
            for ($x = 1; $x < $cols - 1; $x++) {
                $neighbors = [];
                for ($dy = -1; $dy <= 1; $dy++) {
                    for ($dx = -1; $dx <= 1; $dx++) {
                        $neighbors[] = $numberGrid[$y + $dy][$x + $dx];
                    }
                }
                sort($neighbors);
                $median = $neighbors[4];
                $center = $numberGrid[$y][$x];
                $count = count(array_filter($neighbors, fn($n) => $n === $center));
                if ($count <= 2) $numberGrid[$y][$x] = $median;
            }
        }
    }

    public static function generateColoredPreview(array $numberGrid, array $palette, int $cellSize = 20) {
        $rows = count($numberGrid);
        if ($rows === 0) return null;
        $cols = count($numberGrid[0]);
        if ($cols === 0) return null;
        $width = $cols * $cellSize;
        $height = $rows * $cellSize;
        $img = imagecreatetruecolor($width, $height);
        if ($img === false) return null;
        $white = imagecolorallocate($img, 255, 255, 255);
        imagefill($img, 0, 0, $white);
        foreach ($numberGrid as $y => $row) {
            foreach ($row as $x => $num) {
                $paletteIndex = (int)$num - 1;
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
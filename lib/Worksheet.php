<?php
declare(strict_types=1);

/**
 * Worksheet Generation Pipeline
 * 
 * Converts uploaded images into clean, printable color-by-number worksheets
 * using a quantized kids palette and noise reduction.
 */
class Worksheet
{
    /**
     * Main pipeline: Image → Pixelated Grid → Number Grid with Kids Colors
     * 
     * @param resource $img Image resource
     * @param int $gridSize Grid resolution (e.g., 50 for 50x50)
     * @param array $palette Kids color palette with 'rgb' and 'name' keys
     * @return array ['numberGrid' => array, 'colorGrid' => array]
     */
    public static function generateWorksheet($img, int $gridSize, array $palette): array
    {
        // Step 1: Create pixelated color grid via block averaging
        $colorGrid = self::createPixelatedGrid($img, $gridSize);
        
        // Step 2: Quantize colors to kids palette
        $quantizedGrid = self::quantizeToKidsPalette($colorGrid, $palette);
        
        // Step 3: Reduce noise (single-cell anomalies)
        $numberGrid = self::reduceCellNoise($quantizedGrid);
        
        return [
            'numberGrid' => $numberGrid,
            'colorGrid' => $colorGrid
        ];
    }

    /**
     * Step 1: Create pixelated grid via block averaging
     * Divides image into blocks and samples colors from each block
     * 
     * @param resource $img Image resource
     * @param int $gridSize Number of blocks per side (e.g., 50 → 50x50 grid)
     * @return array 2D array of [r, g, b] pixel colors
     */
    private static function createPixelatedGrid($img, int $gridSize): array
    {
        $w = imagesx($img);
        $h = imagesy($img);
        $blockW = max(1, (int)floor($w / $gridSize));
        $blockH = max(1, (int)floor($h / $gridSize));
        
        $grid = [];
        
        for ($y = 0; $y < $gridSize; $y++) {
            $grid[$y] = [];
            
            for ($x = 0; $x < $gridSize; $x++) {
                // Sample multiple points across the block and average
                $avgColor = self::sampleBlockAverage($img, $x, $y, $blockW, $blockH, $w, $h);
                $grid[$y][$x] = $avgColor;
            }
        }
        
        return $grid;
    }

    /**
     * Sample multiple points across a block and return averaged color
     * Uses 9-point sampling for better representation
     */
    private static function sampleBlockAverage($img, int $blockX, int $blockY, 
                                               int $blockW, int $blockH, 
                                               int $imgW, int $imgH): array
    {
        $samples = 9;
        $sumR = 0;
        $sumG = 0;
        $sumB = 0;
        
        // Sample 3x3 grid across the block
        for ($dy = 0; $dy < 3; $dy++) {
            for ($dx = 0; $dx < 3; $dx++) {
                $px = min($imgW - 1, (int)($blockX * $blockW + $blockW * ($dx + 1) / 3));
                $py = min($imgH - 1, (int)($blockY * $blockH + $blockH * ($dy + 1) / 3));
                
                $rgb = imagecolorat($img, $px, $py);
                $sumR += ($rgb >> 16) & 0xFF;
                $sumG += ($rgb >> 8) & 0xFF;
                $sumB += $rgb & 0xFF;
            }
        }
        
        return [
            (int)round($sumR / $samples),
            (int)round($sumG / $samples),
            (int)round($sumB / $samples)
        ];
    }

    /**
     * Step 2: Quantize pixelated colors to kids palette
     * Maps each color to the nearest kids color by CIELAB distance
     * Returns 1-indexed color numbers (1 = first palette color)
     */
    private static function quantizeToKidsPalette(array $colorGrid, array $palette): array
    {
        $numberGrid = [];
        
        foreach ($colorGrid as $y => $row) {
            $numberGrid[$y] = [];
            
            foreach ($row as $x => $rgb) {
                // Find nearest palette color
                $colorNumber = self::findNearestColor($rgb, $palette);
                $numberGrid[$y][$x] = $colorNumber;
            }
        }
        
        return $numberGrid;
    }

    /**
     * Find nearest color in palette using CIELAB distance
     * Converts RGB to LAB color space for perceptually accurate matching
     * Returns 1-indexed color number
     */
    private static function findNearestColor(array $rgb, array $palette): int
    {
        $bestIndex = 0;
        $bestDistance = PHP_FLOAT_MAX;
        
        $labPixel = self::rgbToLab($rgb);
        
        foreach ($palette as $i => $entry) {
            $paletteRgb = $entry['rgb'];
            $labPalette = self::rgbToLab($paletteRgb);
            
            $distance = self::labDistance($labPixel, $labPalette);
            
            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $bestIndex = $i;
            }
        }
        
        return $bestIndex + 1; // 1-indexed
    }

    /**
     * Step 3: Reduce noise by replacing isolated cells
     * Cells that are surrounded by different colors get replaced with median color
     * Prevents single-pixel anomalies from appearing in the final worksheet
     */
    private static function reduceCellNoise(array &$numberGrid): array
    {
        $rows = count($numberGrid);
        if ($rows < 3) return $numberGrid;
        
        $cols = count($numberGrid[0]);
        if ($cols < 3) return $numberGrid;
        
        // Process interior cells only
        for ($y = 1; $y < $rows - 1; $y++) {
            for ($x = 1; $x < $cols - 1; $x++) {
                // Get 8 neighbors
                $neighbors = [
                    $numberGrid[$y - 1][$x - 1], $numberGrid[$y - 1][$x], $numberGrid[$y - 1][$x + 1],
                    $numberGrid[$y][$x - 1],                          $numberGrid[$y][$x + 1],
                    $numberGrid[$y + 1][$x - 1], $numberGrid[$y + 1][$x], $numberGrid[$y + 1][$x + 1],
                ];
                
                $center = $numberGrid[$y][$x];
                $matchCount = count(array_filter($neighbors, fn($n) => $n === $center));
                
                // If center color appears in 2 or fewer neighbors, it's isolated → replace with majority
                if ($matchCount <= 2) {
                    $counts = array_count_values($neighbors);
                    arsort($counts);
                    $majorityColor = key($counts);
                    $numberGrid[$y][$x] = $majorityColor;
                }
            }
        }
        
        return $numberGrid;
    }

    /**
     * Convert RGB to CIELAB color space for perceptually accurate distance
     */
    private static function rgbToLab(array $rgb): array
    {
        $r = $rgb[0] / 255.0;
        $g = $rgb[1] / 255.0;
        $b = $rgb[2] / 255.0;

        // Apply gamma correction (sRGB)
        if ($r > 0.04045) $r = pow(($r + 0.055) / 1.055, 2.4);
        else $r = $r / 12.92;
        
        if ($g > 0.04045) $g = pow(($g + 0.055) / 1.055, 2.4);
        else $g = $g / 12.92;
        
        if ($b > 0.04045) $b = pow(($b + 0.055) / 1.055, 2.4);
        else $b = $b / 12.92;

        // Convert to XYZ
        $x = $r * 0.4124 + $g * 0.3576 + $b * 0.1805;
        $y = $r * 0.2126 + $g * 0.7152 + $b * 0.0722;
        $z = $r * 0.0193 + $g * 0.1192 + $b * 0.9505;

        // Normalize by D65 illuminant
        $x = $x / 0.95047;
        $y = $y / 1.00000;
        $z = $z / 1.08883;

        // Convert to LAB
        $delta = 6 / 29;
        $deltaSquare = $delta * $delta;
        $deltaCube = $delta * $deltaSquare;

        if ($x > $deltaCube) $x = pow($x, 1/3);
        else $x = $x / (3 * $deltaSquare) + 4/29;

        if ($y > $deltaCube) $y = pow($y, 1/3);
        else $y = $y / (3 * $deltaSquare) + 4/29;

        if ($z > $deltaCube) $z = pow($z, 1/3);
        else $z = $z / (3 * $deltaSquare) + 4/29;

        $L = 116 * $y - 16;
        $a = 500 * ($x - $y);
        $b_comp = 200 * ($y - $z);

        return [$L, $a, $b_comp];
    }

    /**
     * Calculate Euclidean distance in CIELAB space
     * Lower = more similar, higher = less similar
     */
    private static function labDistance(array $lab1, array $lab2): float
    {
        $dL = $lab1[0] - $lab2[0];
        $da = $lab1[1] - $lab2[1];
        $db = $lab1[2] - $lab2[2];
        
        return sqrt($dL * $dL + $da * $da + $db * $db);
    }

    /**
     * Generate colored preview image from number grid
     * Used for parent reference and download
     */
    public static function generateColoredPreview(array $numberGrid, array $palette, int $cellSize = 20): ?resource
    {
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
            foreach ($row as $x => $colorNum) {
                $paletteIndex = (int)$colorNum - 1;
                
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

    /**
     * Resize image while maintaining aspect ratio
     */
    public static function resizeImage($img, int $maxWidth, int $maxHeight, int $quality = 85): ?resource
    {
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
}

<?php
declare(strict_types=1);

/**
 * Worksheet Generation Pipeline - Improved Version
 * 
 * Converts uploaded images into clean, printable color-by-number worksheets:
 * 1. Downsample/pixelate image to grid size via block averaging
 * 2. Detect background pixels (high brightness, low saturation)
 * 3. Quantize foreground colors to kids palette using Euclidean RGB distance
 * 4. Smooth/merge isolated cells
 * 5. Background cells remain blank (value 0)
 */
class Worksheet
{
    /**
     * Main pipeline: Image → Downsampled Grid → Background Detection → Quantization → Smoothing → Number Grid
     * 
     * @param resource $img Image resource
     * @param int $gridSize Grid resolution (e.g., 30 for 30x30)
     * @param array $palette Kids color palette with 'rgb' and 'name' keys
     * @return array ['numberGrid' => 2D array, 'backgroundGrid' => 2D bool array]
     */
    public static function generateWorksheet($img, int $gridSize, array $palette): array
    {
        // Step 1: Downsample image to grid size via block averaging
        $colorGrid = self::downsampleImage($img, $gridSize);
        
        // Step 2: Detect background pixels (bright, low saturation)
        $backgroundGrid = self::detectBackground($colorGrid);
        
        // Step 3: Quantize foreground colors to kids palette using Euclidean RGB
        $quantizedGrid = self::quantizeToKidsPalette($colorGrid, $backgroundGrid, $palette);
        
        // Step 4: Smooth and merge isolated cells
        $numberGrid = self::smoothCells($quantizedGrid);
        
        return [
            'numberGrid' => $numberGrid,
            'backgroundGrid' => $backgroundGrid
        ];
    }

    /**
     * Step 1: Downsample image to grid size via block averaging
     * 
     * Divides the image into grid cells and computes the average RGB per cell.
     * This creates a pixelated representation of the image at the target grid resolution.
     * 
     * @param resource $img Image resource
     * @param int $gridSize Number of cells per side (e.g., 30 → 30x30 grid)
     * @return array 2D array where grid[$y][$x] = [r, g, b]
     */
    private static function downsampleImage($img, int $gridSize): array
    {
        $imgW = imagesx($img);
        $imgH = imagesy($img);
        $blockW = max(1, (int)floor($imgW / $gridSize));
        $blockH = max(1, (int)floor($imgH / $gridSize));
        
        $colorGrid = [];
        
        for ($y = 0; $y < $gridSize; $y++) {
            $colorGrid[$y] = [];
            
            for ($x = 0; $x < $gridSize; $x++) {
                // Compute average color for this cell's block
                $avgColor = self::averageBlockColor($img, $x, $y, $blockW, $blockH, $imgW, $imgH);
                $colorGrid[$y][$x] = $avgColor;
            }
        }
        
        return $colorGrid;
    }

    /**
     * Compute average RGB color for a block of pixels
     * 
     * Samples 9 points distributed across the block, then averages.
     */
    private static function averageBlockColor($img, int $blockX, int $blockY, 
                                              int $blockW, int $blockH, 
                                              int $imgW, int $imgH): array
    {
        $sumR = 0;
        $sumG = 0;
        $sumB = 0;
        $count = 0;
        
        // Sample 3x3 grid across the block
        for ($dy = 0; $dy < 3; $dy++) {
            for ($dx = 0; $dx < 3; $dx++) {
                $px = min($imgW - 1, (int)($blockX * $blockW + $blockW * ($dx + 1) / 3));
                $py = min($imgH - 1, (int)($blockY * $blockH + $blockH * ($dy + 1) / 3));
                
                $rgb = imagecolorat($img, $px, $py);
                $sumR += ($rgb >> 16) & 0xFF;
                $sumG += ($rgb >> 8) & 0xFF;
                $sumB += $rgb & 0xFF;
                $count++;
            }
        }
        
        return [
            (int)($sumR / $count),
            (int)($sumG / $count),
            (int)($sumB / $count)
        ];
    }

    /**
     * Step 2: Detect background pixels
     * 
     * Background criteria:
     * - Brightness (average of RGB) > 230
     * - Saturation (color range) < 15% of max
     * 
     * This prevents light backgrounds (white, light gray, light colored) from being
     * incorrectly mapped to colors like green.
     * 
     * @param array $colorGrid 2D array of [r,g,b] colors
     * @return array 2D array where backgroundGrid[$y][$x] = bool (true = background)
     */
    private static function detectBackground(array $colorGrid): array
    {
        $backgroundGrid = [];
        $rows = count($colorGrid);
        
        foreach ($colorGrid as $y => $row) {
            $backgroundGrid[$y] = [];
            $cols = count($row);
            
            foreach ($row as $x => $rgb) {
                $r = $rgb[0];
                $g = $rgb[1];
                $b = $rgb[2];
                
                // Brightness check
                $brightness = (int)(($r + $g + $b) / 3);
                
                // Saturation check: range / max ratio
                $min = min($r, $g, $b);
                $max = max($r, $g, $b);
                $range = $max - $min;
                $saturation = $max > 0 ? $range / $max : 0;
                
                // Cell is background if bright AND desaturated
                $isBackground = ($brightness > 230) && ($saturation < 0.15);
                
                $backgroundGrid[$y][$x] = $isBackground;
            }
        }
        
        return $backgroundGrid;
    }

    /**
     * Step 3: Quantize colors to kids palette
     * 
     * For foreground cells: find nearest palette color using Euclidean RGB distance.
     * For background cells: assign 0 (blank, no number).
     * 
     * Returns 1-indexed color numbers (1 = first palette color, 0 = background/blank).
     */
    private static function quantizeToKidsPalette(array $colorGrid, array $backgroundGrid, array $palette): array
    {
        $numberGrid = [];
        
        foreach ($colorGrid as $y => $row) {
            $numberGrid[$y] = [];
            
            foreach ($row as $x => $rgb) {
                if ($backgroundGrid[$y][$x]) {
                    // Background cell: remains blank (0)
                    $numberGrid[$y][$x] = 0;
                } else {
                    // Foreground cell: find nearest palette color
                    $colorNumber = self::findNearestColorEuclidean($rgb, $palette);
                    $numberGrid[$y][$x] = $colorNumber;
                }
            }
        }
        
        return $numberGrid;
    }

    /**
     * Find nearest color in palette using Euclidean RGB distance
     * 
     * Simple and reliable for shared hosting; returns 1-indexed color number.
     */
    private static function findNearestColorEuclidean(array $rgb, array $palette): int
    {
        $bestIndex = 0;
        $bestDistance = PHP_FLOAT_MAX;
        
        foreach ($palette as $i => $entry) {
            $pr = $entry['rgb'][0];
            $pg = $entry['rgb'][1];
            $pb = $entry['rgb'][2];
            
            $dr = $rgb[0] - $pr;
            $dg = $rgb[1] - $pg;
            $db = $rgb[2] - $pb;
            
            $distance = sqrt($dr * $dr + $dg * $dg + $db * $db);
            
            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $bestIndex = $i;
            }
        }
        
        return $bestIndex + 1; // 1-indexed
    }

    /**
     * Step 4: Smooth cells and remove isolated noise
     * 
     * If a cell differs from its 8 neighbors (majority vote), replace it with majority.
     * Background cells (0) are preserved.
     */
    private static function smoothCells(array &$numberGrid): array
    {
        $rows = count($numberGrid);
        if ($rows < 3) return $numberGrid;
        
        $cols = count($numberGrid[0]);
        if ($cols < 3) return $numberGrid;
        
        // Process interior cells only (preserve edges)
        for ($y = 1; $y < $rows - 1; $y++) {
            for ($x = 1; $x < $cols - 1; $x++) {
                $center = $numberGrid[$y][$x];
                
                // Skip background cells
                if ($center === 0) continue;
                
                // Get 8 neighbors
                $neighbors = [
                    $numberGrid[$y - 1][$x - 1], $numberGrid[$y - 1][$x], $numberGrid[$y - 1][$x + 1],
                    $numberGrid[$y][$x - 1],                          $numberGrid[$y][$x + 1],
                    $numberGrid[$y + 1][$x - 1], $numberGrid[$y + 1][$x], $numberGrid[$y + 1][$x + 1],
                ];
                
                // Count how many neighbors match center color
                $matchCount = 0;
                foreach ($neighbors as $neighbor) {
                    if ($neighbor === $center) $matchCount++;
                }
                
                // If isolated (2 or fewer matches), replace with neighbor majority
                if ($matchCount <= 2) {
                    // Count non-background neighbors
                    $colorCounts = [];
                    foreach ($neighbors as $neighbor) {
                        if ($neighbor === 0) continue; // Skip background
                        $colorCounts[$neighbor] = ($colorCounts[$neighbor] ?? 0) + 1;
                    }
                    
                    if (!empty($colorCounts)) {
                        arsort($colorCounts);
                        $majorityColor = key($colorCounts);
                        $numberGrid[$y][$x] = $majorityColor;
                    }
                }
            }
        }
        
        return $numberGrid;
    }

    /**
     * Generate colored preview image from number grid
     * 
     * Background cells (0) remain white. Colored cells use their palette color.
     * Used for parent reference and PDF output.
     */
    public static function generateColoredPreview(array $numberGrid, array $palette, int $cellSize = 20)
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
                // Skip background cells (0)
                if ($colorNum === 0) continue;
                
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
     * Used to fit large images into memory before downsampling.
     * PHP 8 compatible: returns GdImage object, not resource type.
     */
    public static function resizeImage($img, int $maxWidth, int $maxHeight, int $quality = 85)
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

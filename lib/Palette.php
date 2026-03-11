<?php
declare(strict_types=1);

require_once __DIR__ . '/ColorReducer.php';

class Palette
{
    /**
     * Extract dominant colors from image using K-means clustering
     */
    public static function extractFromImage($pixels, int $maxColors): array
    {
        if (empty($pixels)) {
            return self::getKidsPalette($maxColors);
        }

        // Use K-means to find dominant colors
        $dominantColors = ColorReducer::reduce($pixels, $maxColors, 15);

        // Convert to palette format with names
        $palette = [];
        $colorNames = ['Red', 'Blue', 'Green', 'Yellow', 'Orange', 'Purple', 'Pink', 'Brown', 'Grey', 'Black', 'White'];

        foreach ($dominantColors as $i => $color) {
            $palette[] = [
                'name' => $colorNames[$i % count($colorNames)],
                'rgb' => array_map('intval', $color)
            ];
        }

        return $palette;
    }

    /**
     * Get kid-friendly color palette (fallback)
     */
    public static function getKidsPalette(int $maxColors): array
    {
        $base = [
            ['name' => 'Red',    'rgb' => [220, 60, 60]],
            ['name' => 'Blue',   'rgb' => [70, 120, 220]],
            ['name' => 'Yellow', 'rgb' => [245, 210, 60]],
            ['name' => 'Green',  'rgb' => [70, 170, 90]],
            ['name' => 'Orange', 'rgb' => [245, 150, 60]],
            ['name' => 'Purple', 'rgb' => [140, 90, 190]],
            ['name' => 'Pink',   'rgb' => [240, 150, 190]],
            ['name' => 'Brown',  'rgb' => [140, 100, 70]],
            ['name' => 'Grey',   'rgb' => [160, 160, 160]],
            ['name' => 'Black',  'rgb' => [40, 40, 40]],
            ['name' => 'White',  'rgb' => [245, 245, 245]],
        ];

        $maxColors = max(2, min(count($base), $maxColors));
        return array_slice($base, 0, $maxColors);
    }

    /**
     * Find nearest color in palette using CIELAB distance
     */
    public static function nearestPaletteIndex(array $pixel, array $palette): int
    {
        $bestIndex = 0;
        $bestDistance = PHP_FLOAT_MAX;

        $labPixel = self::rgbToLab($pixel);

        foreach ($palette as $i => $entry) {
            $color = $entry['rgb'];
            $labColor = self::rgbToLab($color);

            // Use CIELAB distance for perceptually accurate matching
            $dL = $labPixel[0] - $labColor[0];
            $da = $labPixel[1] - $labColor[1];
            $db = $labPixel[2] - $labColor[2];
            $distance = sqrt($dL * $dL + $da * $da + $db * $db);

            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $bestIndex = $i;
            }
        }

        return $bestIndex + 1;
    }

    /**
     * Convert RGB to CIE LAB
     */
    private static function rgbToLab($rgb) {
        $r = $rgb[0] / 255;
        $g = $rgb[1] / 255;
        $b = $rgb[2] / 255;

        if ($r > 0.04045) $r = pow(($r + 0.055) / 1.055, 2.4);
        else $r = $r / 12.92;
        
        if ($g > 0.04045) $g = pow(($g + 0.055) / 1.055, 2.4);
        else $g = $g / 12.92;
        
        if ($b > 0.04045) $b = pow(($b + 0.055) / 1.055, 2.4);
        else $b = $b / 12.92;

        $x = $r * 0.4124 + $g * 0.3576 + $b * 0.1805;
        $y = $r * 0.2126 + $g * 0.7152 + $b * 0.0722;
        $z = $r * 0.0193 + $g * 0.1192 + $b * 0.9505;

        $x = $x / 0.95047;
        $y = $y / 1.00000;
        $z = $z / 1.08883;

        $delta = 6 / 29;
        $deltaSquare = $delta * $delta;
        $deltaCube = $delta * $delta * $delta;

        if ($x > $deltaCube) $x = pow($x, 1/3);
        else $x = $x / (3 * $deltaSquare) + 4/29;

        if ($y > $deltaCube) $y = pow($y, 1/3);
        else $y = $y / (3 * $deltaSquare) + 4/29;

        if ($z > $deltaCube) $z = pow($z, 1/3);
        else $z = $z / (3 * $deltaSquare) + 4/29;

        $L = 116 * $y - 16;
        $a = 500 * ($x - $y);
        $b = 200 * ($y - $z);

        return [$L, $a, $b];
    }

    public static function getPaletteEntryByNumber(array $palette, int $number): ?array
    {
        $index = $number - 1;
        return $palette[$index] ?? null;
    }
}
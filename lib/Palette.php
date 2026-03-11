<?php
declare(strict_types=1);

require_once __DIR__ . '/ColorReducer.php';

class Palette
{
    /**
     * Get intelligent name for a color based on RGB values
     */
    private static function getColorName($rgb): string {
        $r = $rgb[0];
        $g = $rgb[1];
        $b = $rgb[2];

        // Convert to HSL for better color naming
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $delta = $max - $min;

        // Brightness
        $l = ($max + $min) / 510; // 0-1

        // Saturation
        if ($l < 0.5) {
            $s = $delta / ($max + $min);
        } else {
            $s = $delta / (510 - $max - $min);
        }

        // Hue
        if ($delta === 0) {
            $h = 0;
        } elseif ($max === $r) {
            $h = (($g - $b) / $delta) % 6;
        } elseif ($max === $g) {
            $h = ($b - $r) / $delta + 2;
        } else {
            $h = ($r - $g) / $delta + 4;
        }
        $h = $h * 60;
        if ($h < 0) $h += 360;

        // Determine if grayscale
        if ($s < 0.1) {
            if ($l > 0.8) return 'White';
            if ($l > 0.6) return 'Light Gray';
            if ($l > 0.4) return 'Gray';
            if ($l > 0.2) return 'Dark Gray';
            return 'Black';
        }

        // Color names based on hue
        if ($h < 15 || $h >= 345) return 'Red';
        if ($h < 45) return 'Orange';
        if ($h < 60) return 'Yellow';
        if ($h < 150) return 'Green';
        if ($h < 200) return 'Cyan';
        if ($h < 260) return 'Blue';
        if ($h < 290) return 'Purple';
        if ($h < 330) return 'Pink';
        return 'Red';
    }

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

        // Convert to palette format with intelligent names
        $palette = [];

        foreach ($dominantColors as $color) {
            $palette[] = [
                'name' => self::getColorName($color),
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
            ['name' => 'Red',       'rgb' => [239, 28, 58]],
            ['name' => 'Orange',    'rgb' => [255, 119, 24]],
            ['name' => 'Yellow',    'rgb' => [255, 222, 0]],
            ['name' => 'Green',     'rgb' => [40, 168, 70]],
            ['name' => 'Blue',      'rgb' => [0, 102, 204]],
            ['name' => 'Purple',    'rgb' => [128, 0, 128]],
            ['name' => 'Brown',     'rgb' => [139, 69, 19]],
            ['name' => 'Pink',      'rgb' => [255, 192, 203]],
            ['name' => 'Gray',      'rgb' => [128, 128, 128]],
            ['name' => 'Black',     'rgb' => [0, 0, 0]],
            ['name' => 'White',     'rgb' => [255, 255, 255]],
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

    /**
     * Extract colors from image and map to nearest kids colors
     * Returns only the kids colors that are actually used
     */
    public static function getKidsColorPaletteFromImage(array $extractedColors): array
    {
        $allKidsColors = self::getKidsPalette(11); // All available kids colors
        $usedKidsColors = [];
        $usedMap = [];

        // For each extracted color, find nearest kids color
        foreach ($extractedColors as $rgb) {
            $bestKidsIndex = -1;
            $bestDistance = PHP_FLOAT_MAX;
            $labPixel = self::rgbToLab($rgb);

            foreach ($allKidsColors as $idx => $kidsColor) {
                $labKidsColor = self::rgbToLab($kidsColor['rgb']);
                $dL = $labPixel[0] - $labKidsColor[0];
                $da = $labPixel[1] - $labKidsColor[1];
                $db = $labPixel[2] - $labKidsColor[2];
                $distance = sqrt($dL * $dL + $da * $da + $db * $db);

                if ($distance < $bestDistance) {
                    $bestDistance = $distance;
                    $bestKidsIndex = $idx;
                }
            }

            // Track which kids colors are used
            if ($bestKidsIndex >= 0 && !isset($usedMap[$bestKidsIndex])) {
                $usedMap[$bestKidsIndex] = true;
                $usedKidsColors[] = $allKidsColors[$bestKidsIndex];
            }
        }

        return !empty($usedKidsColors) ? $usedKidsColors : array_slice($allKidsColors, 0, 1);
    }
}
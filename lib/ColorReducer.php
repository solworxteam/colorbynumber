<?php

class ColorReducer{

    /**
     * K-means clustering with CIELAB color space for better color perception
     */
    public static function reduce($pixels, $k = 8, $iterations = 10) {
        if (empty($pixels)) {
            return [];
        }

        // Convert RGB to LAB for perceptually uniform color space
        $labPixels = array_map([self::class, 'rgbToLab'], $pixels);

        // Initialize centroids randomly from the data
        $indices = array_rand($labPixels, min($k, count($labPixels)));
        if (!is_array($indices)) {
            $indices = [$indices];
        }
        $centroids = array_map(function($i) use ($labPixels) {
            return $labPixels[$i];
        }, $indices);

        // K-means iterations
        for ($it = 0; $it < $iterations; $it++) {
            $groups = array_fill(0, $k, []);

            // Assign pixels to nearest centroid (using CIELAB distance)
            foreach ($labPixels as $idx => $pixel) {
                $best = 0;
                $bestDist = PHP_FLOAT_MAX;

                foreach ($centroids as $i => $centroid) {
                    $d = self::cieLab_distance($pixel, $centroid);
                    if ($d < $bestDist) {
                        $bestDist = $d;
                        $best = $i;
                    }
                }

                $groups[$best][] = $idx;
            }

            // Update centroids
            foreach ($groups as $i => $group) {
                if (empty($group)) {
                    continue;
                }

                $l = $a = $b = 0;
                foreach ($group as $idx) {
                    $l += $labPixels[$idx][0];
                    $a += $labPixels[$idx][1];
                    $b += $labPixels[$idx][2];
                }

                $n = count($group);
                $centroids[$i] = [
                    $l / $n,
                    $a / $n,
                    $b / $n
                ];
            }
        }

        // Convert centroids back to RGB
        return array_map([self::class, 'labToRgb'], $centroids);
    }

    /**
     * Convert RGB to CIE LAB color space (perceptually uniform)
     */
    private static function rgbToLab($rgb) {
        $r = $rgb[0] / 255;
        $g = $rgb[1] / 255;
        $b = $rgb[2] / 255;

        // RGB to XYZ
        if ($r > 0.04045) $r = pow(($r + 0.055) / 1.055, 2.4);
        else $r = $r / 12.92;
        
        if ($g > 0.04045) $g = pow(($g + 0.055) / 1.055, 2.4);
        else $g = $g / 12.92;
        
        if ($b > 0.04045) $b = pow(($b + 0.055) / 1.055, 2.4);
        else $b = $b / 12.92;

        $x = $r * 0.4124 + $g * 0.3576 + $b * 0.1805;
        $y = $r * 0.2126 + $g * 0.7152 + $b * 0.0722;
        $z = $r * 0.0193 + $g * 0.1192 + $b * 0.9505;

        // XYZ to LAB (D65 illuminant)
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

    /**
     * Convert LAB to RGB
     */
    private static function labToRgb($lab) {
        $L = $lab[0];
        $a = $lab[1];
        $b = $lab[2];

        // LAB to XYZ
        $y = ($L + 16) / 116;
        $x = $a / 500 + $y;
        $z = $y - $b / 200;

        $xCube = $x * $x * $x;
        $yCube = $y * $y * $y;
        $zCube = $z * $z * $z;

        $delta = 6 / 29;
        $deltaSquare = $delta * $delta;

        if ($xCube > $deltaSquare) $x = $xCube;
        else $x = 3 * $deltaSquare * ($x - 4/29);

        if ($yCube > $deltaSquare) $y = $yCube;
        else $y = 3 * $deltaSquare * ($y - 4/29);

        if ($zCube > $deltaSquare) $z = $zCube;
        else $z = 3 * $deltaSquare * ($z - 4/29);

        $x = $x * 0.95047;
        $y = $y * 1.00000;
        $z = $z * 1.08883;

        // XYZ to RGB
        $r = $x *  3.2406 + $y * -1.5372 + $z * -0.4986;
        $g = $x * -0.9689 + $y *  1.8758 + $z *  0.0415;
        $b = $x *  0.0557 + $y * -0.2040 + $z *  1.0570;

        if ($r > 0.0031308) $r = 1.055 * pow($r, 1/2.4) - 0.055;
        else $r = 12.92 * $r;

        if ($g > 0.0031308) $g = 1.055 * pow($g, 1/2.4) - 0.055;
        else $g = 12.92 * $g;

        if ($b > 0.0031308) $b = 1.055 * pow($b, 1/2.4) - 0.055;
        else $b = 12.92 * $b;

        return [
            max(0, min(255, (int)round($r * 255))),
            max(0, min(255, (int)round($g * 255))),
            max(0, min(255, (int)round($b * 255)))
        ];
    }

    /**
     * CIELAB distance (perceptually accurate)
     */
    private static function cieLab_distance($lab1, $lab2) {
        $dL = $lab1[0] - $lab2[0];
        $da = $lab1[1] - $lab2[1];
        $db = $lab1[2] - $lab2[2];
        return sqrt($dL * $dL + $da * $da + $db * $db);
    }

}
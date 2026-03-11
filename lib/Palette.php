<?php
declare(strict_types=1);

class Palette
{
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

    public static function nearestPaletteIndex(array $pixel, array $palette): int
    {
        $bestIndex = 0;
        $bestDistance = PHP_INT_MAX;

        foreach ($palette as $i => $entry) {
            $color = $entry['rgb'];

            $dr = $pixel[0] - $color[0];
            $dg = $pixel[1] - $color[1];
            $db = $pixel[2] - $color[2];

            $distance = ($dr * $dr) + ($dg * $dg) + ($db * $db);

            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $bestIndex = $i;
            }
        }

        return $bestIndex + 1;
    }

    public static function getPaletteEntryByNumber(array $palette, int $number): ?array
    {
        $index = $number - 1;
        return $palette[$index] ?? null;
    }
}
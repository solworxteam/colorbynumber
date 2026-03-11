<?php
declare(strict_types=1);

/**
 * Kids Color Palette Management
 * 
 * Provides fixed, friendly colors for children's coloring.
 * All colors approximate standard pencil/crayon colors.
 */
class Palette
{
    /**
     * Get the fixed kids color palette
     * Colors are selected up to $maxColors, in order
     */
    public static function getKidsPalette(int $maxColors): array
    {
        $colors = [
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

        // Clamp to available colors
        $count = max(2, min(count($colors), $maxColors));
        return array_slice($colors, 0, $count);
    }

    /**
     * Get a single palette entry by color number (1-indexed)
     */
    public static function getColor(int $colorNumber, array $palette): ?array
    {
        $index = $colorNumber - 1;
        return $palette[$index] ?? null;
    }
}

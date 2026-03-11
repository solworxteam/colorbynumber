<?php

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
}
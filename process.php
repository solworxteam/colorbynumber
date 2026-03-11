<?php
declare(strict_types=1);

require __DIR__ . '/lib/Worksheet.php';
require __DIR__ . '/lib/Palette.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

if (
    !isset($_POST['grid'], $_POST['colors']) ||
    !isset($_FILES['image']) ||
    !is_uploaded_file($_FILES['image']['tmp_name'])
) {
    http_response_code(400);
    exit('Missing form fields or image.');
}

$grid = max(10, min(80, (int)$_POST['grid']));
$colors = max(2, min(11, (int)$_POST['colors']));

$uploadDir = __DIR__ . '/uploads';
$outputDir = __DIR__ . '/output';

if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
    http_response_code(500);
    exit('Failed to create uploads directory.');
}

if (!is_dir($outputDir) && !mkdir($outputDir, 0755, true)) {
    http_response_code(500);
    exit('Failed to create output directory.');
}

$originalName = $_FILES['image']['name'] ?? 'upload';
$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'webp'];

if (!in_array($extension, $allowed, true)) {
    http_response_code(400);
    exit('Only JPG, JPEG, PNG and WEBP are allowed.');
}

$filename = uniqid('img_', true) . '.' . $extension;
$storedPath = $uploadDir . '/' . $filename;

if (!move_uploaded_file($_FILES['image']['tmp_name'], $storedPath)) {
    http_response_code(500);
    exit('Failed to save uploaded file.');
}

$imageData = file_get_contents($storedPath);
if ($imageData === false) {
    http_response_code(500);
    exit('Failed to read image.');
}

$img = imagecreatefromstring($imageData);
if ($img === false) {
    http_response_code(500);
    exit('Failed to create image resource. Check PHP GD.');
}

[$rgbGrid, $pixels] = Worksheet::buildGrid($img, $grid);
$palette = Palette::getKidsPalette($colors);

$numberGrid = [];

foreach ($rgbGrid as $y => $row) {
    foreach ($row as $x => $pixel) {
        $numberGrid[$y][$x] = Palette::nearestPaletteIndex($pixel, $palette);
    }
}

$jsonData = [
    'size' => $grid,
    'palette' => $palette,
    'numberGrid' => $numberGrid,
    'image' => $filename
];

$jsonPath = $outputDir . '/' . $filename . '.json';

if (file_put_contents($jsonPath, json_encode($jsonData, JSON_PRETTY_PRINT)) === false) {
    http_response_code(500);
    exit('Failed to save worksheet data.');
}

header('Location: preview.php?id=' . rawurlencode($filename));
exit;
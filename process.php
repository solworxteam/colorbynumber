<?php
declare(strict_types=1);

require __DIR__ . '/lib/Config.php';
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
    exit(Config::getErrorMessage('missing_fields'));
}

// Validate file size
if ($_FILES['image']['size'] > Config::MAX_FILE_SIZE) {
    http_response_code(413);
    exit(Config::getErrorMessage('file_too_large'));
}

$grid = max(Config::MIN_GRID_SIZE, min(Config::MAX_GRID_SIZE, (int)$_POST['grid']));
$colors = max(Config::MIN_COLORS, min(Config::MAX_COLORS, (int)$_POST['colors']));

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

if (!in_array($extension, Config::ALLOWED_EXTENSIONS, true)) {
    http_response_code(400);
    exit(Config::getErrorMessage('invalid_extension'));
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
}Config::getErrorMessage('read_failed'));
}

$img = imagecreatefromstring($imageData);
if ($img === false) {
    http_response_code(500);
    exit(Config::getErrorMessage('invalid_image'));
}

// Validate and resize image if necessary
$originalWidth = imagesx($img);
$originalHeight = imagesy($img);

if ($originalWidth > Config::MAX_IMAGE_WIDTH || $originalHeight > Config::MAX_IMAGE_HEIGHT) {
    $img = Worksheet::resizeImage($img, Config::MAX_IMAGE_WIDTH, Config::MAX_IMAGE_HEIGHT, Config::RESIZE_QUALITY);
    if ($img === false) {
        http_response_code(500);
        exit(Config::getErrorMessage('image_too_large'));
    }

[$rgbGrid, $pixels] = Worksheet::buildGrid($img, $grid);
$palette = Palette::getKidsPalette($colors);

$numberGrid = [];

foreach ($rgbGrid as $y => $row) {
    foreach ($row as $x => $pixel) {
        $numberGrid[$y][$x] = Palette::nearestPaletteIndex($pixel, $palette);
    })) === false) {
    http_response_code(500);
    exit(Config::getErrorMessage('save_failed')
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
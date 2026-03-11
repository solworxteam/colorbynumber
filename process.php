<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

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
    exit(Config::getErrorMessage('mkdir_failed'));
}

if (!is_dir($outputDir) && !mkdir($outputDir, 0755, true)) {
    http_response_code(500);
    exit(Config::getErrorMessage('mkdir_failed'));
}

$originalName = $_FILES['image']['name'] ?? 'upload';
$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

if (!in_array($extension, Config::ALLOWED_EXTENSIONS, true)) {
    http_response_code(400);
    exit(Config::getErrorMessage('invalid_extension'));
}

$filename = uniqid('img_', true) . '.' . $extension;
$storedPath = $uploadDir . '/' . $filename;

error_log("Uploading file: name=" . $_FILES['image']['name'] . ", type=" . $_FILES['image']['type'] . ", size=" . $_FILES['image']['size'] . ", ext=" . $extension);

if (!move_uploaded_file($_FILES['image']['tmp_name'], $storedPath)) {
    error_log("ERROR: move_uploaded_file failed");
    http_response_code(500);
    exit(Config::getErrorMessage('upload_failed'));
}

$fileSize = filesize($storedPath);
$exists = file_exists($storedPath) ? 'yes' : 'no';
error_log("File stored at: $storedPath, file_exists=$exists, size=$fileSize");

$imageData = file_get_contents($storedPath);
if ($imageData === false) {
    error_log("ERROR: file_get_contents failed");
    http_response_code(500);
    exit(Config::getErrorMessage('read_failed'));
}

$imgDataSize = strlen($imageData);
$imgHex = bin2hex(substr($imageData, 0, 8));
error_log("Image data read: size=$imgDataSize, first 8 bytes=$imgHex");

$img = imagecreatefromstring($imageData);
if ($img === false) {
    $imgDataSize = strlen($imageData);
    $imgHex32 = bin2hex(substr($imageData, 0, 32));
    error_log("ERROR: imagecreatefromstring failed. File: $storedPath, Size: $imgDataSize, Hex: $imgHex32");
    http_response_code(500);
    exit(Config::getErrorMessage('invalid_image'));
}

error_log("Image created successfully");

$originalWidth = imagesx($img);
$originalHeight = imagesy($img);

if ($originalWidth > Config::MAX_IMAGE_WIDTH || $originalHeight > Config::MAX_IMAGE_HEIGHT) {
    $img = Worksheet::resizeImage($img, Config::MAX_IMAGE_WIDTH, Config::MAX_IMAGE_HEIGHT, Config::RESIZE_QUALITY);
    if ($img === false) {
        http_response_code(500);
        exit(Config::getErrorMessage('image_too_large'));
    }
}

// Get fixed kids color palette
error_log("Loading kids color palette with $colors colors");
$palette = Palette::getKidsPalette($colors);
error_log("Palette colors: " . count($palette));

// Generate worksheet: pixelated grid → quantized to kids colors → noise reduction
error_log("Generating worksheet: grid=$grid colors=$colors");
$result = Worksheet::generateWorksheet($img, $grid, $palette);
$numberGrid = $result['numberGrid'];

error_log("Worksheet generated successfully");

$jsonData = [
    'size' => $grid,
    'palette' => $palette,
    'numberGrid' => $numberGrid,
    'image' => $filename
];

$jsonPath = $outputDir . '/' . $filename . '.json';

if (file_put_contents($jsonPath, json_encode($jsonData)) === false) {
    http_response_code(500);
    exit(Config::getErrorMessage('save_failed'));
}

header('Location: preview.php?id=' . rawurlencode($filename));
exit;
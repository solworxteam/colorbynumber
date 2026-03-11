<?php

declare(strict_types=1);

require __DIR__ . '/lib/ColorReducer.php';
require __DIR__ . '/lib/Worksheet.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed. Please submit the form from index.php');
}

if (
    !isset($_POST['grid'], $_POST['colors']) ||
    !isset($_FILES['image']) ||
    !is_uploaded_file($_FILES['image']['tmp_name'])
) {
    http_response_code(400);
    exit('Missing image upload or form fields.');
}

$grid = max(10, min(100, (int)$_POST['grid']));
$colors = max(2, min(24, (int)$_POST['colors']));

$uploadDir = __DIR__ . '/uploads';
$outputDir = __DIR__ . '/output';

if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
    http_response_code(500);
    exit('Could not create uploads directory.');
}

if (!is_dir($outputDir) && !mkdir($outputDir, 0755, true)) {
    http_response_code(500);
    exit('Could not create output directory.');
}

$tmp = $_FILES['image']['tmp_name'];
$originalName = $_FILES['image']['name'] ?? 'upload';
$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

$allowed = ['jpg', 'jpeg', 'png', 'webp'];
if (!in_array($extension, $allowed, true)) {
    http_response_code(400);
    exit('Only JPG, JPEG, PNG, and WEBP files are allowed.');
}

$baseName = uniqid('img_', true);
$storedFilename = $baseName . '.' . $extension;
$storedPath = $uploadDir . '/' . $storedFilename;

if (!move_uploaded_file($tmp, $storedPath)) {
    http_response_code(500);
    exit('Failed to save uploaded image.');
}

$imageData = file_get_contents($storedPath);
if ($imageData === false) {
    http_response_code(500);
    exit('Failed to read uploaded image.');
}

$img = imagecreatefromstring($imageData);
if ($img === false) {
    http_response_code(500);
    exit('Failed to process image. Make sure GD is enabled in PHP.');
}

[$gridData, $pixels] = Worksheet::buildGrid($img, $grid);
$palette = ColorReducer::reduce($pixels, $colors);

$jsonPath = $outputDir . '/' . $storedFilename . '.json';
$result = file_put_contents($jsonPath, json_encode([
    'grid' => $gridData,
    'palette' => $palette,
    'size' => $grid,
], JSON_PRETTY_PRINT));

if ($result === false) {
    http_response_code(500);
    exit('Failed to write output data.');
}

header('Location: preview.php?id=' . rawurlencode($storedFilename));
exit;
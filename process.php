<?php
declare(strict_types=1);

// Enable error logging for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

$debug = true;

function debug_log($msg) {
    global $debug;
    if ($debug) {
        error_log("[DEBUG] " . $msg);
    }
}

debug_log("process.php started");

require __DIR__ . '/lib/Config.php';
debug_log("Config.php loaded");

require __DIR__ . '/lib/Worksheet.php';
debug_log("Worksheet.php loaded");

require __DIR__ . '/lib/Palette.php';
debug_log("Palette.php loaded");

debug_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);

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
debug_log("POST grid: " . $_POST['grid'] . ", colors: " . $_POST['colors']);

$grid = max(Config::MIN_GRID_SIZE, min(Config::MAX_GRID_SIZE, (int)$_POST['grid']));
$colors = max(Config::MIN_COLORS, min(Config::MAX_COLORS, (int)$_POST['colors']));

debug_log("Sanitized grid: $grid, colors: $colors"
$grid = max(Config::MIN_GRID_SIZE

debug_log("uploadDir: $uploadDir, outputDir: $outputDir");, min(Config::MAX_GRID_SIZE, (int)$_POST['grid']));
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
debug_log("Moving uploaded file from " . $_FILES['image']['tmp_name'] . " to $storedPath");
if (!move_uploaded_file($_FILES['image']['tmp_name'], $storedPath)) {
    debug_log("move_uploaded_file failed");
    http_response_code(500);
    exit(Config::getErrorMessage('upload_failed'));
}
debug_log("File moved successfully");filename = uniqid('img_', true) . '.' . $extension;
$storedPath = $uploadDir . '/' . $filename;

if (!move_uploaded_file($_FILES['image']['tmp_name'], $storedPath)) {
    http_response_code(500);
    exit(Config::getErrorMessage('upload_failed'));
}

$imageData = file_get_contents($storedPath);
debug_log("Creating image from string, data size: " . strlen($imageData));
$img = imagecreatefromstring($imageData);
if ($img === false) {
    debug_log("imagecreatefromstring failed - check GD extension");
    http_response_code(500);
    exit(Config::getErrorMessage('invalid_image'));
}
debug_log("Image created successfully");
$img = imagecreatefromstring($imageData);
if ($img === false) {
    http_response_code(500);
    exit(Config::getErrorMessage('invalid_image'));
}

// Validate and resize image if necessary
$originalWidth = imagesx($img);
$originalHeight = imagesy($img);

debug_log("Building grid with size: $grid");
[$rgbGrid, $pixels] = Worksheet::buildGrid($img, $grid);
debug_log("Grid built successfully, pixels count: " . count($pixels));

debug_log("Getting palette with colors: $colors");
$palette = Palette::getKidsPalette($colors);
debug_log("Palette created, colors: " . count($palette)nfig::MAX_IMAGE_WIDTH, Config::MAX_IMAGE_HEIGHT, Config::RESIZE_QUALITY);
    if ($img === false) {
        http_response_code(500);
        exit(Config::getErrorMessage('image_too_large'));
    }
}

[$rgbGrid, $pixels] = Worksheet::buildGrid($img, $grid);
$palette = Palette::getKidsPalette($colors);

$numberGrid = [];

foreach ($rgbGrid as $y => $row) {
    foreach ($row as $x => $pixel) {
        $numberGrid[$y][$x] = Palette::nearestPaletteIndex($pixel, $palette);
    }
debug_log("Saving JSON to: $jsonPath");
if (file_put_contents($jsonPath, json_encode($jsonData)) === false) {
    debug_log("Failed to save JSON file");
    http_response_code(500);
    exit(Config::getErrorMessage('save_failed'));
debug_log("Processing complete, redirecting to preview.php?id=$filename");
}
debug_log("JSON saved successfully");   'size' => $grid,
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
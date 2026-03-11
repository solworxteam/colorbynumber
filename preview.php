<?php
declare(strict_types=1);

if (!isset($_GET['id']) || $_GET['id'] === '') {
    http_response_code(400);
    exit('Missing worksheet id.');
}

$id = basename($_GET['id']);
$jsonPath = __DIR__ . '/output/' . $id . '.json';

if (!file_exists($jsonPath)) {
    http_response_code(404);
    exit('Worksheet data not found.');
}

$data = json_decode(file_get_contents($jsonPath), true);

if (!is_array($data) || !isset($data['numberGrid'], $data['palette'])) {
    http_response_code(500);
    exit('Worksheet data is invalid.');
}

$numberGrid = $data['numberGrid'];
$palette = $data['palette'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Worksheet Preview</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 30px;
            background: #f5f5f5;
        }

        table {
            border-collapse: collapse;
            margin: 20px auto;
            background: #fff;
        }

        td {
            width: 22px;
            height: 22px;
            border: 1px solid #ccc;
            text-align: center;
            vertical-align: middle;
            font-size: 11px;
        }

        .preview-section {
            display: inline-block;
            margin: 0 30px;
            vertical-align: top;
        }

        .preview-section h3 {
            margin-top: 0;
        }

        .legend {
            width: fit-content;
            margin: 25px auto;
            text-align: left;
            background: #fff;
            padding: 16px 20px;
            border: 1px solid #ddd;
        }

        .legend-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 8px 0;
        }

        .swatch {
            width: 22px;
            height: 22px;
            border: 1px solid #888;
            display: inline-block;
        }

        .btn {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 20px;
            background: #2d7ef7;
            color: white;
            text-decoration: none;
            border-radius: 6px;
        }
    </style>
</head>
<body>

<h2>Worksheet Preview</h2>

<div style="text-align: center;">
    <div class="preview-section">
        <h3>Worksheet to Color</h3>
        <table>
            <?php foreach ($numberGrid as $row): ?>
                <tr>
                    <?php foreach ($row as $num): ?>
                        <td><?php echo (int)$num; ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="preview-section">
        <h3>Colored Preview (For Parents)</h3>
        <table>
            <?php foreach ($numberGrid as $row): ?>
                <tr>
                    <?php foreach ($row as $num): ?>
                        <?php
                            $paletteIndex = (int)$num - 1;
                            $color = $palette[$paletteIndex] ?? null;
                            $rgb = $color ? $color['rgb'] : [255, 255, 255];
                            $r = (int)$rgb[0];
                            $g = (int)$rgb[1];
 div style="margin-top: 30px;">
    <a class="btn" href="download.php?id=<?php echo urlencode($id); ?>&type=numbered">Download Worksheet PDF</a>
    <a class="btn" href="download.php?id=<?php echo urlencode($id); ?>&type=colored">Download Colored Preview PDF</a>
</div
                            $bgColor = "rgb($r, $g, $b)";
                        ?>
                        <td style="background-color: <?php echo $bgColor; ?>;">&nbsp;</td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

<div class="legend">
    <h3>Color Key</h3>
    <?php foreach ($palette as $i => $entry): ?>
        <?php
            $r = (int)$entry['rgb'][0];
            $g = (int)$entry['rgb'][1];
            $b = (int)$entry['rgb'][2];
            $name = $entry['name'];
        ?>
        <div class="legend-row">
            <span class="swatch" style="background: rgb(<?php echo $r; ?>, <?php echo $g; ?>, <?php echo $b; ?>);"></span>
            <span><?php echo $i + 1; ?> = <?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
    <?php endforeach; ?>
</div>

<a class="btn" href="download.php?id=<?php echo urlencode($id); ?>">Download PDF</a>

</body>
</html>
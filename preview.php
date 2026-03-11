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
<html>
<head>
    <meta charset="UTF-8">
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

<table>
    <?php foreach ($numberGrid as $row): ?>
        <tr>
            <?php foreach ($row as $num): ?>
                <td><?php echo (int)$num; ?></td>
            <?php endforeach; ?>
        </tr>
    <?php endforeach; ?>
</table>

<div class="legend">
    <h3>Color Key</h3>
    <?php foreach ($palette as $i => $color): ?>
        <?php
            $r = (int)round($color[0]);
            $g = (int)round($color[1]);
            $b = (int)round($color[2]);
        ?>
        <div class="legend-row">
            <span class="swatch" style="background: rgb(<?php echo $r; ?>, <?php echo $g; ?>, <?php echo $b; ?>);"></span>
            <span><?php echo $i + 1; ?> = rgb(<?php echo $r; ?>, <?php echo $g; ?>, <?php echo $b; ?>)</span>
        </div>
    <?php endforeach; ?>
</div>

<a class="btn" href="download.php?id=<?php echo urlencode($id); ?>">Download PDF</a>

</body>
</html>
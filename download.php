<?php
declare(strict_types=1);

require __DIR__ . '/lib/fpdf.php';

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

$pdf = new FPDF('P', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetFont('Arial', '', 8);

$rows = count($numberGrid);
$cols = count($numberGrid[0] ?? []);

if ($rows === 0 || $cols === 0) {
    http_response_code(500);
    exit('Empty worksheet grid.');
}

$maxGridWidth = 170;
$cell = max(4, min(8, $maxGridWidth / $cols));

$pdf->Cell(0, 8, 'Color-by-Number Worksheet', 0, 1, 'C');
$pdf->Ln(3);

foreach ($numberGrid as $row) {
    foreach ($row as $num) {
        $pdf->Cell($cell, $cell, (string)$num, 1, 0, 'C');
    }
    $pdf->Ln();
}

$pdf->Ln(8);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, 'Color Key', 0, 1);

$pdf->SetFont('Arial', '', 10);

foreach ($palette as $i => $color) {
    $r = (int)round($color[0]);
    $g = (int)round($color[1]);
    $b = (int)round($color[2]);

    $pdf->SetFillColor($r, $g, $b);
    $pdf->Cell(10, 10, '', 1, 0, 'C', true);
    $pdf->Cell(0, 10, '  ' . ($i + 1) . ' = rgb(' . $r . ', ' . $g . ', ' . $b . ')', 0, 1);
}

$pdf->Output('D', 'worksheet.pdf');
exit;
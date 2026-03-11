<?php
declare(strict_types=1);

// Temporary debug settings - remove or set to 0 later if desired
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/lib/fpdf.php';

if (!isset($_GET['id']) || $_GET['id'] === '') {
    http_response_code(400);
    exit('Missing worksheet id.');
}

$id = basename((string)$_GET['id']);
$jsonPath = __DIR__ . '/output/' . $id . '.json';

if (!file_exists($jsonPath)) {
    http_response_code(404);
    exit('Worksheet data file not found: ' . htmlspecialchars($jsonPath));
}

$json = file_get_contents($jsonPath);
if ($json === false) {
    http_response_code(500);
    exit('Failed to read worksheet data.');
}

$data = json_decode($json, true);

if (
    !is_array($data) ||
    !isset($data['numberGrid']) ||
    !isset($data['palette']) ||
    !is_array($data['numberGrid']) ||
    !is_array($data['palette'])
) {
    http_response_code(500);
    exit('Worksheet JSON is invalid.');
}

$numberGrid = $data['numberGrid'];
$palette = $data['palette'];

$rows = count($numberGrid);
$cols = isset($numberGrid[0]) && is_array($numberGrid[0]) ? count($numberGrid[0]) : 0;

if ($rows === 0 || $cols === 0) {
    http_response_code(500);
    exit('Worksheet grid is empty.');
}

class WorksheetPDF extends FPDF
{
    function Header()
    {
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 8, 'Color-by-Number Worksheet', 0, 1, 'C');
        $this->Ln(2);
    }
}

$pdf = new WorksheetPDF('P', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 15);
$pdf->SetFont('Arial', '', 8);

// Page sizing
$pageWidth = 190; // usable width on A4 with margins
$cell = floor($pageWidth / max(1, $cols));
$cell = max(4, min(8, $cell));

// Draw grid
foreach ($numberGrid as $row) {
    foreach ($row as $num) {
        $pdf->Cell($cell, $cell, (string)(int)$num, 1, 0, 'C');
    }
    $pdf->Ln();
}

// Legend
$pdf->Ln(8);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, 'Color Key', 0, 1);
$pdf->SetFont('Arial', '', 10);

foreach ($palette as $i => $color) {
    $r = isset($color[0]) ? (int)round((float)$color[0]) : 0;
    $g = isset($color[1]) ? (int)round((float)$color[1]) : 0;
    $b = isset($color[2]) ? (int)round((float)$color[2]) : 0;

    $r = max(0, min(255, $r));
    $g = max(0, min(255, $g));
    $b = max(0, min(255, $b));

    $pdf->SetFillColor($r, $g, $b);
    $pdf->Cell(10, 10, '', 1, 0, 'C', true);
    $pdf->Cell(0, 10, '  ' . ($i + 1) . ' = rgb(' . $r . ', ' . $g . ', ' . $b . ')', 0, 1);
}

// Important: clear any accidental output before PDF
if (ob_get_length()) {
    ob_end_clean();
}

$pdf->Output('D', 'worksheet.pdf');
exit;
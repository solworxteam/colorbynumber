<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/fpdf.php';
require_once __DIR__ . '/lib/Worksheet.php';

if (!isset($_GET['id']) || $_GET['id'] === '') {
    http_response_code(400);
    exit('Missing worksheet id.');
}

$id = basename((string)$_GET['id']);
$type = isset($_GET['type']) && $_GET['type'] === 'colored' ? 'colored' : 'numbered';
$jsonPath = __DIR__ . '/output/' . $id . '.json';

if (!file_exists($jsonPath)) {
    http_response_code(404);
    exit('Worksheet data file not found.');
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
    public $type = 'numbered';

    public function Header(): void
    {
        $this->SetFont('Arial', 'B', 14);
        if ($this->type === 'colored') {
            $this->Cell(0, 8, 'Color-by-Number Worksheet (Colored Preview)', 0, 1, 'C');
        } else {
            $this->Cell(0, 8, 'Color-by-Number Worksheet', 0, 1, 'C');
        }
        $this->Ln(2);
    }
}

$pdf = new WorksheetPDF('P', 'mm', 'A4');
$pdf->type = $type;
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 15);
$pdf->SetFont('Arial', '', 8);

$pageWidth = 190;
$cell = (int)floor($pageWidth / max(1, $cols));
$cell = max(4, min(8, $cell));

if ($type === 'colored') {
    // Colored preview version
    foreach ($numberGrid as $row) {
        foreach ($row as $num) {
            $paletteIndex = (int)$num - 1;
            if ($paletteIndex >= 0 && $paletteIndex < count($palette)) {
                $colorData = $palette[$paletteIndex];
                $r = (int)$colorData['rgb'][0];
                $g = (int)$colorData['rgb'][1];
                $b = (int)$colorData['rgb'][2];
                $pdf->SetFillColor($r, $g, $b);
                $pdf->Cell($cell, $cell, '', 1, 0, 'C', true);
            } else {
                $pdf->Cell($cell, $cell, '', 1, 0, 'C', false);
            }
        }
        $pdf->Ln();
    }
} else {
    // Numbered worksheet version
    foreach ($numberGrid as $row) {
        foreach ($row as $num) {
            $pdf->Cell($cell, $cell, (string)(int)$num, 1, 0, 'C');
        }
        $pdf->Ln();
    }
}

$pdf->Ln(8);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, 'Color Key', 0, 1);
$pdf->SetFont('Arial', '', 10);

// Track which colors are actually used
$usedColors = [];
foreach ($numberGrid as $row) {
    foreach ($row as $num) {
        $usedColors[(int)$num] = true;
    }
}

foreach ($palette as $i => $entry) {
    if (!isset($usedColors[$i + 1])) continue;
    
    $r = (int)$entry['rgb'][0];
    $g = (int)$entry['rgb'][1];
    $b = (int)$entry['rgb'][2];
    $name = $entry['name'];

    $pdf->SetFillColor($r, $g, $b);
    $pdf->Cell(10, 10, '', 1, 0, 'C', true);
    $pdf->Cell(0, 10, '  ' . ($i + 1) . ' = ' . $name, 0, 1);
}

if (ob_get_length()) {
    ob_end_clean();
}

$filename = $type === 'colored' ? 'worksheet-colored.pdf' : 'worksheet.pdf';
$pdf->Output('D', $filename);
exit;
<?php

require "lib/fpdf.php";

$id=$_GET['id'];

$data=json_decode(file_get_contents("output/".$id.".json"),true);

$grid=$data["grid"];
$palette=$data["palette"];

$pdf=new FPDF();
$pdf->AddPage();

$pdf->SetFont("Arial","",8);

$cell=5;

foreach($grid as $row){

foreach($row as $pixel){

$pdf->Cell($cell,$cell,"1",1,0,"C");

}

$pdf->Ln();

}

$pdf->Ln(10);

$pdf->SetFont("Arial","B",12);
$pdf->Cell(0,10,"Color Key",0,1);

foreach($palette as $i=>$c){

$r=$c[0];
$g=$c[1];
$b=$c[2];

$pdf->SetFillColor($r,$g,$b);
$pdf->Cell(10,10,"",1,0,true);

$pdf->Cell(20,10,$i+1,0,1);

}

$pdf->Output("D","worksheet.pdf");
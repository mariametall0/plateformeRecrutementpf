<?php
// Script de test minimal pour vérifier FPDF
require_once "../libs/fpdf/fpdf.php";

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);
$pdf->Cell(40,10,'FPDF Fonctionne !');
$pdf->Output("I", "test.pdf");

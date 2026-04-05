<?php
ini_set('display_errors', 0);
require_once "../config/database.php";
require_once "../config/session.php";
require_once "../libs/fpdf/fpdf.php";
require_once "../includes/functions.php";

check_role('candidat');
$user_id = $_SESSION['id'];

// 1. Récupération des données
try {
    $stmt = $pdo->prepare("
        SELECT u.*, p.bio, p.secteur_specialite 
        FROM utilisateurs u 
        LEFT JOIN profils_candidats p ON u.id = p.id_utilisateur 
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM cv_formations WHERE id_utilisateur = ? ORDER BY date_debut DESC");
    $stmt->execute([$user_id]);
    $formations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM cv_experiences WHERE id_utilisateur = ? ORDER BY date_debut DESC");
    $stmt->execute([$user_id]);
    $experiences = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM cv_competences WHERE id_utilisateur = ? ORDER BY type, nom");
    $stmt->execute([$user_id]);
    $competences = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) { 
    die("Erreur base de données. Veuillez réessayer."); 
}

if (!$user) die("Profil introuvable.");

// Helper pour l'encodage (PHP 8.2 compatible)
function conv($txt) {
    return mb_convert_encoding((string)$txt, 'ISO-8859-1', 'UTF-8');
}

// 2. Classe PDF Evolution (Deux Colonnes)
class CV_Premium extends FPDF {
    function RoundedRect($x, $y, $w, $h, $r, $style = '', $angle = '1234')
    {
        $k = $this->k;
        $hp = $this->h;
        if($style=='F') $op='f';
        elseif($style=='FD' || $style=='DF') $op='B';
        else $op='S';
        $MyArc = 4/3 * (sqrt(2) - 1);
        $this->_out(sprintf('%.2F %.2F m',($x+$r)*$k,($hp-$y)*$k));

        $xc = $x+$w-$r;
        $yc = $y+$r;
        $this->_out(sprintf('%.2F %.2F l',$xc*$k,($hp-$y)*$k));
        if (strpos($angle, '2')===false)
            $this->_out(sprintf('%.2F %.2F l',($x+$w)*$k,($hp-$y)*$k));
        else
            $this->_Arc($xc + $r*$MyArc, $hp - $y, $xc + $r, $hp - ($yc - $r*$MyArc), $xc + $r, $hp - $yc);

        $xc = $x+$w-$r;
        $yc = $y+$h-$r;
        $this->_out(sprintf('%.2F %.2F l',($x+$w)*$k,($hp-$yc)*$k));
        if (strpos($angle, '3')===false)
            $this->_out(sprintf('%.2F %.2F l',($x+$w)*$k,($hp-($y+$h))*$k));
        else
            $this->_Arc($xc + $r, $hp - ($yc + $r*$MyArc), $xc + $r*$MyArc, $hp - ($y+$h), $xc, $hp - ($y+$h));

        $xc = $x+$r;
        $yc = $y+$h-$r;
        $this->_out(sprintf('%.2F %.2F l',$xc*$k,($hp-($y+$h))*$k));
        if (strpos($angle, '4')===false)
            $this->_out(sprintf('%.2F %.2F l',$x*$k,($hp-($y+$h))*$k));
        else
            $this->_Arc($xc - $r*$MyArc, $hp - ($y+$h), $xc - $r, $hp - ($yc + $r*$MyArc), $xc - $r, $hp - $yc);

        $xc = $x+$r;
        $yc = $y+$r;
        $this->_out(sprintf('%.2F %.2F l',$x*$k,($hp-$yc)*$k));
        if (strpos($angle, '1')===false)
        {
            $this->_out(sprintf('%.2F %.2F l',$x*$k,($hp-$y)*$k));
            $this->_out(sprintf('%.2F %.2F l',($x+$r)*$k,($hp-$y)*$k));
        }
        else
            $this->_Arc($xc - $r, $hp - ($yc - $r*$MyArc), $xc - $r*$MyArc, $hp - $y, $xc, $hp - $y);
        $this->_out($op);
    }

    function _Arc($x1, $y1, $x2, $y2, $x3, $y3)
    {
        $h = $this->h;
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c ', $x1*$this->k, $y1*$this->k, 
            $x2*$this->k, $y2*$this->k, $x3*$this->k, $y3*$this->k));
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(0, 10, conv('Document généré par Admissio Careers - ' . date('d/m/Y')), 0, 0, 'C');
    }
}

// 3. Initialisation du PDF
if (ob_get_level()) ob_clean();
$pdf = new CV_Premium();
$pdf->SetMargins(0, 0, 0);
$pdf->SetAutoPageBreak(true, 15);
$pdf->AddPage();

// --- SIDEBAR (GAUCHE - Fond Slate) ---
$pdf->SetFillColor(241, 245, 249); 
$pdf->Rect(0, 0, 65, 297, 'F');

// Photo Placeholder (style rond/moderne)
$pdf->SetFillColor(203, 213, 225);
$pdf->RoundedRect(12, 15, 40, 40, 20, 'F'); // Grand rayon pour effet cercle
$pdf->SetXY(12, 33);
$pdf->SetFont('Arial', 'B', 8);
$pdf->SetTextColor(100, 116, 139);
$pdf->Cell(40, 5, 'PHOTO', 0, 0, 'C');

// Contact Info
$pdf->SetTextColor(15, 23, 42); 
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetXY(10, 65);
$pdf->Cell(45, 8, 'CONTACT', 0, 1, 'L');
$pdf->SetDrawColor(203, 213, 225);
$pdf->Line(10, 72, 55, 72);

$pdf->SetFont('Arial', '', 9);
$pdf->SetY(75);
$pdf->SetX(10);
$content = "Email :\n" . $user['email'] . "\n\nTel :\n" . ($user['telephone'] ?? 'N/A') . "\n\nVille :\n" . ($user['adresse'] ?? 'N/A') . "\n" . ($user['pays'] ?? '');
$pdf->MultiCell(45, 5, conv($content), 0, 'L');

// Compétences (Sidebar)
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetY(130);
$pdf->SetX(10);
$pdf->Cell(45, 8, conv('COMPÉTENCES'), 0, 1, 'L');
$pdf->Line(10, 137, 55, 137);

$pdf->SetFont('Arial', '', 9);
$pdf->SetY(142);
foreach($competences as $c) {
    if($pdf->GetY() > 270) break;
    $pdf->SetX(10);
    $pdf->Cell(45, 5, conv("• " . $c['nom']), 0, 1, 'L');
}

// --- MAIN COLUMN (DROITE) ---
$pdf->SetTextColor(15, 23, 42);
$pdf->SetXY(75, 20);
$pdf->SetFont('Arial', 'B', 24);
$pdf->Cell(0, 12, conv(strtoupper($user['nom'])), 0, 1, 'L');

$pdf->SetX(75);
$pdf->SetFont('Arial', 'B', 14);
$pdf->SetTextColor(79, 70, 229); // Indigo
$pdf->Cell(0, 10, conv($user['secteur_specialite'] ?? 'Expert Professionnel'), 0, 1, 'L');

// Bio
if (!empty($user['bio'])) {
    $pdf->SetY(45);
    $pdf->SetX(75);
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(71, 85, 105);
    $pdf->MultiCell(120, 5, conv($user['bio']), 0, 'J');
}

// Expériences (Main)
$pdf->SetY(85);
$pdf->SetX(75);
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(15, 23, 42);
$pdf->Cell(0, 10, conv('EXPÉRIENCES PROFESSIONNELLES'), 0, 1, 'L');
$pdf->SetDrawColor(79, 70, 229);
$pdf->Line(75, 93, 200, 93);
$pdf->Ln(5);

foreach ($experiences as $e) {
    if ($pdf->GetY() > 250) { $pdf->AddPage(); $pdf->SetX(75); } else { $pdf->SetX(75); }
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 6, conv($e['poste']), 0, 1);
    
    $pdf->SetX(75);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetTextColor(79, 70, 229);
    $date_str = format_date($e['date_debut']) . ' - ' . ($e['en_poste'] ? conv('Présent') : format_date($e['date_fin']));
    $pdf->Cell(0, 5, conv($e['entreprise'] . " | " . $date_str), 0, 1);
    
    $pdf->SetX(75);
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(71, 85, 105);
    $pdf->MultiCell(120, 5, conv($e['description'] ?? ''));
    $pdf->Ln(5);
}

// Formations (Main)
if ($pdf->GetY() > 220) { $pdf->AddPage(); $pdf->SetX(75); } else { $pdf->SetX(75); }
$pdf->Ln(5);
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(15, 23, 42);
$pdf->Cell(0, 10, conv('FORMATIONS & DIPLÔMES'), 0, 1, 'L');
$pdf->Line(75, $pdf->GetY()-2, 200, $pdf->GetY()-2);
$pdf->Ln(5);

foreach ($formations as $f) {
    if ($pdf->GetY() > 260) { $pdf->AddPage(); $pdf->SetX(75); } else { $pdf->SetX(75); }
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 6, conv($f['diplome']), 0, 1);
    
    $pdf->SetX(75);
    $pdf->SetFont('Arial', 'I', 9);
    $pdf->SetTextColor(100, 116, 139);
    $date_f = format_date($f['date_debut']) . ' - ' . ($f['date_fin'] ? format_date($f['date_fin']) : conv('Présent'));
    $pdf->Cell(0, 5, conv($f['etablissement'] . " (" . $date_f . ")"), 0, 1);
    $pdf->Ln(3);
}

$pdf->Output("I", "CV_" . preg_replace('/[^a-zA-Z0-9_-]/', '_', $user['nom']) . ".pdf");

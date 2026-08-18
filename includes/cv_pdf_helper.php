<?php
/**
 * cv_pdf_helper.php – Helper modulaire pour la génération de PDFs (CV et Lettres).
 */
require_once __DIR__ . "/../libs/fpdf/fpdf.php";
require_once __DIR__ . "/functions.php";

/**
 * Helper d'encodage ISO pour FPDF
 */
if (!function_exists('conv')) {
    function conv($txt) {
        return mb_convert_encoding((string)$txt, 'ISO-8859-1', 'UTF-8');
    }
}

/**
 * Classe de rendu PDF Premium partagée
 */
class AdmissioPDF extends FPDF {
    function RoundedRect($x, $y, $w, $h, $r, $style = '', $angle = '1234') {
        $k = $this->k; $hp = $this->h;
        if($style=='F') $op='f'; elseif($style=='FD' || $style=='DF') $op='B'; else $op='S';
        $MyArc = 4/3 * (sqrt(2) - 1);
        $this->_out(sprintf('%.2F %.2F m',($x+$r)*$k,($hp-$y)*$k));
        $xc = $x+$w-$r; $yc = $y+$r;
        $this->_out(sprintf('%.2F %.2F l',$xc*$k,($hp-$y)*$k));
        if (strpos($angle, '2')===false) $this->_out(sprintf('%.2F %.2F l',($x+$w)*$k,($hp-$y)*$k));
        else $this->_Arc($xc + $r*$MyArc, $hp - $y, $xc + $r, $hp - ($yc - $r*$MyArc), $xc + $r, $hp - $yc);
        $xc = $x+$w-$r; $yc = $y+$h-$r;
        $this->_out(sprintf('%.2F %.2F l',($x+$w)*$k,($hp-$yc)*$k));
        if (strpos($angle, '3')===false) $this->_out(sprintf('%.2F %.2F l',($x+$w)*$k,($hp-($y+$h))*$k));
        else $this->_Arc($xc + $r, $hp - ($yc + $r*$MyArc), $xc + $r*$MyArc, $hp - ($y+$h), $xc, $hp - ($y+$h));
        $xc = $x+$r; $yc = $y+$h-$r;
        $this->_out(sprintf('%.2F %.2F l',$xc*$k,($hp-($y+$h))*$k));
        if (strpos($angle, '4')===false) $this->_out(sprintf('%.2F %.2F l',$x*$k,($hp-($y+$h))*$k));
        else $this->_Arc($xc - $r*$MyArc, $hp - ($y+$h), $xc - $r, $hp - ($yc + $r*$MyArc), $xc - $r, $hp - $yc);
        $xc = $x+$r; $yc = $y+$r;
        $this->_out(sprintf('%.2F %.2F l',$x*$k,($hp-$yc)*$k));
        if (strpos($angle, '1')===false) { $this->_out(sprintf('%.2F %.2F l',$x*$k,($hp-$y)*$k)); $this->_out(sprintf('%.2F %.2F l',($x+$r)*$k,($hp-$y)*$k)); }
        else $this->_Arc($xc - $r, $hp - ($yc - $r*$MyArc), $xc - $r*$MyArc, $hp - $y, $xc, $hp - $y);
        $this->_out($op);
    }
    function _Arc($x1, $y1, $x2, $y2, $x3, $y3) { $h = $this->h; $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c ', $x1*$this->k, $y1*$this->k, $x2*$this->k, $y2*$this->k, $x3*$this->k, $y3*$this->k)); }
    function ClippingCircle($x, $y, $r) { $x *= $this->k; $y = ($this->h - $y) * $this->k; $r *= $this->k; $p = 4/3 * (sqrt(2) - 1); $this->_out(sprintf('q %.2F %.2F m %.2F %.2F %.2F %.2F %.2F %.2F c %.2F %.2F %.2F %.2F %.2F %.2F c %.2F %.2F %.2F %.2F %.2F %.2F c %.2F %.2F %.2F %.2F %.2F %.2F c W n', $x + $r, $y, $x + $r, $y + $r * $p, $x + $r * $p, $y + $r, $x, $y + $r, $x - $r * $p, $y + $r, $x - $r, $y + $r * $p, $x - $r, $y, $x - $r, $y - $r * $p, $x - $r * $p, $y - $r, $x, $y - $r, $x + $r * $p, $y - $r, $x + $r, $y - $r * $p, $x + $r, $y)); }
    function UnsetClipping() { $this->_out('Q'); }
    function Footer() { $this->SetY(-15); $this->SetFont('Arial', 'I', 8); $this->SetTextColor(150, 150, 150); $this->Cell(0, 10, conv('Généré par Admissio Portal – Excellence en recrutement'), 0, 0, 'C'); }
}

/**
 * Génère et retourne un objet PDF de CV numérique complet
 */
function build_cv_pdf_object($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT u.*, p.bio, p.secteur_specialite, p.photo_path, p.masquer_photo, p.linkedin 
            FROM utilisateurs u 
            LEFT JOIN profils_candidats p ON u.id = p.id_utilisateur 
            WHERE u.id = ?
        ");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) return null;

        $formations = $pdo->prepare("SELECT * FROM cv_formations WHERE id_utilisateur = ? ORDER BY date_fin DESC, date_debut DESC");
        $formations->execute([$user_id]); $formations = $formations->fetchAll(PDO::FETCH_ASSOC);

        $experiences = $pdo->prepare("SELECT * FROM cv_experiences WHERE id_utilisateur = ? ORDER BY date_debut DESC");
        $experiences->execute([$user_id]); $experiences = $experiences->fetchAll(PDO::FETCH_ASSOC);

        $competences = $pdo->prepare("SELECT * FROM cv_competences WHERE id_utilisateur = ? ORDER BY type, nom");
        $competences->execute([$user_id]); $competences = $competences->fetchAll(PDO::FETCH_ASSOC);

        $langues = $pdo->prepare("SELECT * FROM cv_langues WHERE id_utilisateur = ? ORDER BY langue");
        $langues->execute([$user_id]); $langues = $langues->fetchAll(PDO::FETCH_ASSOC);

        $certifications = $pdo->prepare("SELECT * FROM cv_certifications WHERE id_utilisateur = ? ORDER BY date_obtention DESC");
        $certifications->execute([$user_id]); $certifications = $certifications->fetchAll(PDO::FETCH_ASSOC);

        $interets = $pdo->prepare("SELECT * FROM cv_interets WHERE id_utilisateur = ? ORDER BY nom");
        $interets->execute([$user_id]); $interets = $interets->fetchAll(PDO::FETCH_ASSOC);

        $pdf = new AdmissioPDF();
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(true, 10);
        $pdf->AddPage();

        // CONFIG COULEURS (PRO MODELE)
        $c_sidebar = [245, 213, 176]; // Beige/Pêche
        $c_accent = [26, 188, 156];  // Teal/Vert d'eau
        $c_text_dark = [44, 62, 80]; // Gris ardoise
        $c_text_muted = [127, 140, 141];

        // --- SIDEBAR ---
        $pdf->SetFillColor($c_sidebar[0], $c_sidebar[1], $c_sidebar[2]);
        $pdf->Rect(0, 0, 70, 297, 'F');

        // Contact Info (Haut de sidebar)
        $pdf->SetTextColor($c_text_muted[0], $c_text_muted[1], $c_text_muted[2]);
        $pdf->SetXY(10, 20);
        $pdf->SetFont('Arial', '', 9);
        $contact_info = [
            'email' => $user['email'],
            'address' => trim(($user['adresse'] ?? '') . " " . ($user['pays'] ?? '')),
            'tel' => $user['telephone'] ?? '',
            'linkedin' => $user['linkedin'] ?? ''
        ];
        foreach($contact_info as $label => $val) {
            if(!empty($val)) {
                $pdf->Cell(50, 5, conv($val), 0, 1, 'L');
                $pdf->SetX(10);
            }
        }

        // Langues (avec barres)
        if (!empty($langues)) {
            $pdf->Ln(10); $pdf->SetX(10);
            $pdf->SetFont('Arial', 'B', 12); $pdf->SetTextColor($c_text_dark[0], $c_text_dark[1], $c_text_dark[2]);
            $pdf->Cell(50, 10, conv('Langues'), 0, 1, 'L');
            $pdf->SetFont('Arial', '', 9);
            $niveaux_map = ['notions'=>20, 'intermediaire'=>50, 'avance'=>75, 'bilingue'=>90, 'maternel'=>100];
            foreach($langues as $l) {
                $pdf->SetX(10); $pdf->Cell(50, 5, conv($l['langue']), 0, 1, 'L');
                $pdf->SetX(10);
                $w = $niveaux_map[$l['niveau']] ?? 50;
                $pdf->SetFillColor(189, 195, 199); $pdf->Rect(10, $pdf->GetY(), 45, 2, 'F');
                $pdf->SetFillColor($c_text_dark[0], $c_text_dark[1], $c_text_dark[2]); $pdf->Rect(10, $pdf->GetY(), ($w/100)*45, 2, 'F');
                $pdf->Ln(5);
            }
        }

        // Compétences (Informatique)
        if (!empty($competences)) {
            $pdf->Ln(5); $pdf->SetX(10);
            $pdf->SetFont('Arial', 'B', 12); $pdf->SetTextColor($c_text_dark[0], $c_text_dark[1], $c_text_dark[2]);
            $pdf->Cell(50, 10, conv('Informatique'), 0, 1, 'L');
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetX(10);
            $skills_str = "";
            foreach($competences as $c) { $skills_str .= $c['nom'] . ", "; }
            $pdf->MultiCell(50, 5, conv(rtrim($skills_str, ", ")), 0, 'L');
        }

        // Atouts
        $pdf->Ln(10); $pdf->SetX(10);
        $pdf->SetFont('Arial', 'B', 12); $pdf->Cell(50, 10, conv('Atouts'), 0, 1, 'L');
        $pdf->SetFont('Arial', '', 9); $pdf->SetX(10);
        $pdf->Cell(50, 5, conv("Capacité d'adaptation"), 0, 1, 'L');
        $pdf->SetX(10); $pdf->Cell(50, 5, conv("Rigueur et Autonomie"), 0, 1, 'L');

        // Centres d'intérêt
        if (!empty($interets)) {
            $pdf->Ln(5); $pdf->SetX(10);
            $pdf->SetFont('Arial', 'B', 12); $pdf->Cell(50, 10, conv('Loisirs'), 0, 1, 'L');
            $pdf->SetFont('Arial', '', 9);
            foreach($interets as $i) {
                $pdf->SetX(10); $pdf->Cell(50, 5, conv('- ' . $i['nom']), 0, 1, 'L');
            }
        }

        // --- MAIN CONTENT ---
        $pdf->SetTextColor($c_text_dark[0], $c_text_dark[1], $c_text_dark[2]);
        $pdf->SetXY(80, 20);
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(120, 10, conv(strtoupper($user['nom'])), 0, 1, 'L');
        $pdf->SetX(80);
        $pdf->SetFont('Arial', 'B', 12); $pdf->SetTextColor($c_accent[0], $c_accent[1], $c_accent[2]);
        $pdf->Cell(120, 8, conv($user['secteur_specialite'] ?? 'Étudiante en informatique'), 0, 1, 'L');
        
        if (!empty($user['bio'])) {
            $pdf->Ln(2); $pdf->SetX(80);
            $pdf->SetFont('Arial', '', 9); $pdf->SetTextColor($c_text_dark[0], $c_text_dark[1], $c_text_dark[2]);
            $pdf->MultiCell(120, 5, conv($user['bio']), 0, 'J');
        }

        // Formations (Diplômes)
        if (!empty($formations)) {
            $pdf->Ln(10); $pdf->SetX(80);
            $pdf->SetFont('Arial', 'B', 12); $pdf->SetTextColor($c_text_dark[0], $c_text_dark[1], $c_text_dark[2]);
            $pdf->Cell(120, 10, conv('Diplômes et Formations'), 0, 1, 'L');
            $pdf->SetDrawColor($c_text_dark[0], $c_text_dark[1], $c_text_dark[2]);
            $startY = $pdf->GetY();
            foreach($formations as $f) {
                $pdf->SetX(85);
                $pdf->SetFont('Arial', 'B', 10); $pdf->SetTextColor($c_accent[0], $c_accent[1], $c_accent[2]);
                $pdf->Cell(115, 6, conv($f['diplome']), 0, 1, 'L');
                $pdf->SetX(85); $pdf->SetFont('Arial', 'B', 9); $pdf->SetTextColor($c_text_dark[0], $c_text_dark[1], $c_text_dark[2]);
                $date_start = !empty($f['date_debut']) ? date('M Y', strtotime((string)$f['date_debut'])) : 'N/A';
                $date_end = (!empty($f['date_fin'])) ? date('M Y', strtotime((string)$f['date_fin'])) : 'Présent';
                $date_f = $date_start . " - " . $date_end;
                $pdf->Cell(115, 5, conv($f['etablissement'] . " | " . $date_f), 0, 1, 'L');
                
                // Dot timeline
                $pdf->SetFillColor($c_accent[0], $c_accent[1], $c_accent[2]);
                $pdf->RoundedRect(80.5, $pdf->GetY() - 7, 3, 3, 1.5, 'F');
                $pdf->Ln(2);
            }
            $pdf->Line(82, $startY, 82, $pdf->GetY() - 5);
        }

        // Expériences
        if (!empty($experiences)) {
            $pdf->Ln(5); $pdf->SetX(80);
            $pdf->SetFont('Arial', 'B', 12); $pdf->Cell(120, 10, conv('Expériences professionnelles'), 0, 1, 'L');
            $startY = $pdf->GetY();
            foreach($experiences as $e) {
                $pdf->SetX(85);
                $pdf->SetFont('Arial', 'B', 10); $pdf->SetTextColor($c_accent[0], $c_accent[1], $c_accent[2]);
                $pdf->Cell(115, 6, conv($e['poste']), 0, 1, 'L');
                $pdf->SetX(85); $pdf->SetFont('Arial', 'B', 9); $pdf->SetTextColor($c_text_dark[0], $c_text_dark[1], $c_text_dark[2]);
                $date_start_e = !empty($e['date_debut']) ? date('M Y', strtotime((string)$e['date_debut'])) : 'N/A';
                $date_end_e = $e['en_poste'] ? 'Présent' : (!empty($e['date_fin']) ? date('M Y', strtotime((string)$e['date_fin'])) : '...');
                $date_e = $date_start_e . " - " . $date_end_e;
                $pdf->Cell(115, 5, conv($e['entreprise'] . " | " . $date_e), 0, 1, 'L');
                $pdf->SetX(85); $pdf->SetFont('Arial', '', 8); $pdf->SetTextColor($c_text_muted[0], $c_text_muted[1], $c_text_muted[2]);
                $pdf->MultiCell(115, 4, conv($e['description']), 0, 'L');
                
                // Dot timeline
                $pdf->SetFillColor($c_accent[0], $c_accent[1], $c_accent[2]);
                $pdf->RoundedRect(80.5, $pdf->GetY() - 7, 3, 3, 1.5, 'F');
                $pdf->Ln(5);
            }
            $pdf->Line(82, $startY, 82, $pdf->GetY() - 5);
        }

        return $pdf;
    } catch (Exception $e) { return null; }
}

/**
 * Génère un PDF de motivation professionnel
 */
function generate_motivation_pdf($pdo, $user_id, $text, $offre_titre) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) return null;

        $pdf = new AdmissioPDF();
        $pdf->AddPage();
        
        // Header
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->SetTextColor(25, 103, 210);
        $pdf->Cell(0, 10, conv('LETTRE DE MOTIVATION'), 0, 1, 'R');
        $pdf->SetDrawColor(25, 103, 210);
        $pdf->Line(10, 25, 200, 25);
        
        $pdf->Ln(10);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetTextColor(30, 41, 59);
        $pdf->Cell(0, 6, conv($user['nom']), 0, 1, 'L');
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(0, 5, conv($user['email']), 0, 1, 'L');
        $pdf->Cell(0, 5, conv($user['telephone'] ?? ''), 0, 1, 'L');
        
        $pdf->Ln(15);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(30, 41, 59);
        $pdf->Cell(0, 6, conv('Objet : Candidature au poste de ' . $offre_titre), 0, 1, 'L');
        
        $pdf->Ln(10);
        $pdf->SetFont('Arial', '', 11);
        $pdf->SetTextColor(71, 85, 105);
        $pdf->MultiCell(0, 7, conv($text), 0, 'J');
        
        $pdf->Ln(20);
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->Cell(0, 6, conv('Fait à Casablanca, le ' . date('d/m/Y')), 0, 1, 'R');
        
        return $pdf;
    } catch (Exception $e) { return null; }
}

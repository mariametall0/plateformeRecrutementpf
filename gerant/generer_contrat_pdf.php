<?php
// Ne pas afficher d'erreur HTML qui casserait le buffer du PDF
ini_set('display_errors', 0);
require_once "../config/database.php";
require_once "../config/session.php";
require_once "../libs/fpdf/fpdf.php";

check_role('gerant');
$id_gerant = $_SESSION['id'];
$id_candidature = (int)($_GET['id'] ?? 0);

if ($id_candidature <= 0) die("ID invalide.");

// Récupérer les infos
$stmt = $pdo->prepare("
    SELECT c.id, c.statut, c.date_candidature,
           u.nom AS candidat_nom, u.email AS candidat_email, u.telephone AS candidat_telephone, u.adresse, u.pays,
           co.titre AS offre_titre, co.id_gerant,
           g.nom AS gerant_nom
    FROM candidatures c
    JOIN utilisateurs u ON c.id_candidat = u.id
    JOIN offres co ON c.id_offre = co.id
    JOIN utilisateurs g ON co.id_gerant = g.id
    WHERE c.id = ? AND co.id_gerant = ?
");
$stmt->execute([$id_candidature, $id_gerant]);
$cand = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cand) die("Candidature introuvable ou acces refuse.");
if ($cand['statut'] !== 'validee') die("Cette candidature n'est pas encore acceptee/validee. PDF indisponible.");

// Definition de la classe PDF
class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial', 'B', 24);
        $this->SetTextColor(79, 70, 229); // Primary color (indigo)
        $this->Cell(0, 15, 'ADMISSIO', 0, 1, 'L');
        $this->SetFont('Arial', 'B', 10);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(0, 5, 'Plateforme de Recrutement Avancee', 0, 1, 'L');
        $this->Ln(5);
        $this->Line(10, 32, 200, 32);
        $this->Ln(5);
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(0, 10, 'Document genere par voie automatique (ATS Admissio) - Page '.$this->PageNo().'/{nb}', 0, 0, 'C');
    }
}

// Instanciation
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 11);
$pdf->SetTextColor(40, 40, 40);

// Alignement droit pour la date et lieu
$pdf->Cell(0, 10, 'Fait a Casablanca, le ' . date('d/m/Y'), 0, 1, 'R');
$pdf->Ln(5);

// Destinataire
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 6, "A l'attention de : " . utf8_decode($cand['candidat_nom']), 0, 1);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 6, "Email : " . utf8_decode($cand['candidat_email']), 0, 1);
$pdf->Cell(0, 6, "Contact : " . utf8_decode($cand['candidat_telephone']), 0, 1);
$pdf->Cell(0, 6, "Pays : " . utf8_decode($cand['pays']), 0, 1);
$pdf->Ln(15);

// Titre Principal
$pdf->SetFont('Arial', 'B', 16);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 10, utf8_decode("OBJET : PROPOSITION D'ADMISSION / CONTRAT"), 0, 1, 'C');
$pdf->Ln(10);

// Corps du texte
$pdf->SetFont('Arial', '', 11);
$texte = "Madame, Monsieur " . utf8_decode($cand['candidat_nom']) . ",\n\n" .
         "Suite a l'etude approfondie de votre dossier (Reference #" . $cand['id'] . ") et aux diverses epreuves de selection, nous avons l'immense joie de vous informer que votre candidature a ete formellement RATIFIEE et VALIDEE pour l'opportunite suivante :\n\n" .
         "*** " . utf8_decode($cand['offre_titre']) . " ***\n\n" .
         "Vos solides competences, ainsi que votre profil de maniere generale, correspondent aux hautes exigences techniques et humaines que nous recherchions activement pour cette voie.\n\n" .
         "Par la presente correspondance valant offre formelle, la direction (" . utf8_decode($cand['gerant_nom']) . ") vous invite a integrer notre etablissement.\n\n" .
         "Les modalites pratiques (horaires, lieu, documents complementaires requis) vous seront directement notifiees. Veuillez nous renvoyer un exemplaire numerique de cette offre avec la mention manuscrite 'Lu et approuve', suivie de votre signature, en gage d'acceptation definitive.\n\n" .
         "Nous vous renouvelons nos tres sinceres felicitations et vous souhaitons la bienvenue.\n\n" .
         "Veuillez agreer nos salutations distinguees.";

$pdf->MultiCell(0, 7, $texte);
$pdf->Ln(20);

// Signatures
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(95, 6, utf8_decode("Representant de l'Entreprise"), 0, 0, 'C');
$pdf->Cell(95, 6, utf8_decode("Le Candidat"), 0, 1, 'C');

$pdf->SetFont('Arial', 'I', 9);
$pdf->Line(20, $pdf->GetY()+20, 80, $pdf->GetY()+20);
$pdf->Line(115, $pdf->GetY()+20, 185, $pdf->GetY()+20);

$pdf->Cell(95, 6, utf8_decode($cand['gerant_nom']), 0, 0, 'C');
$pdf->Cell(95, 6, utf8_decode("(Lu et approuve, precede de la signature)"), 0, 1, 'C');

// Sortie du HTTP
$pdf->Output("I", "Lettre_Offre_" . preg_replace('/[^a-zA-Z0-9_-]/', '_', $cand['candidat_nom']) . ".pdf", true);
?>

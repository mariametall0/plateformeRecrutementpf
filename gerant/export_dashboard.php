<?php
/**
 * export_dashboard.php – Génère le rapport PDF du tableau de bord Gérant
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/cv_pdf_helper.php';

// Vérification de sécurité
check_role('gerant');
$id_gerant = (int)($_SESSION['id'] ?? 0);

try {
    // Infos du gérant
    $stmt = $pdo->prepare("SELECT nom FROM utilisateurs WHERE id = ?");
    $stmt->execute([$id_gerant]);
    $gerant = $stmt->fetch();
    if (!$gerant) {
        die("Accès non autorisé.");
    }

    // Statistiques globales
    $stmt_stats = $pdo->prepare("
        SELECT 
            COUNT(c.id) as offres_total,
            SUM(CASE WHEN c.statut = 'actif' THEN 1 ELSE 0 END) as actifs
        FROM concours c
        WHERE c.id_gerant = ?
    ");
    $stmt_stats->execute([$id_gerant]);
    $stats = $stmt_stats->fetch();
    $stats['offres_total'] = $stats['offres_total'] ?? 0;
    $stats['actifs'] = $stats['actifs'] ?? 0;

    $stmt_cand = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN ca.statut = 'en_attente' THEN 1 ELSE 0 END) as en_attente,
            SUM(CASE WHEN ca.statut = 'validee' THEN 1 ELSE 0 END) as validees
        FROM candidatures ca
        JOIN concours c ON ca.id_concours = c.id
        WHERE c.id_gerant = ?
    ");
    $stmt_cand->execute([$id_gerant]);
    $cand_stats = $stmt_cand->fetch();
    $cand_stats['en_attente'] = $cand_stats['en_attente'] ?? 0;
    $cand_stats['validees'] = $cand_stats['validees'] ?? 0;

    // Mes opportunités actives
    $stmt_opp = $pdo->prepare("SELECT titre, date_cloture, statut FROM concours WHERE id_gerant = ? AND statut = 'actif' ORDER BY date_cloture ASC");
    $stmt_opp->execute([$id_gerant]);
    $opportunites = $stmt_opp->fetchAll();

    // Entretiens à venir
    $stmt_next = $pdo->prepare("
        SELECT e.date_entrevue, e.heure_entrevue, u.nom as candidat_nom, co.titre as concours_titre 
        FROM entretiens e 
        JOIN candidatures c ON e.id_candidature = c.id 
        JOIN utilisateurs u ON c.id_candidat = u.id 
        JOIN concours co ON c.id_concours = co.id 
        WHERE co.id_gerant = ? AND e.date_entrevue >= CURDATE() 
        ORDER BY e.date_entrevue ASC LIMIT 10
    ");
    $stmt_next->execute([$id_gerant]);
    $entretiens = $stmt_next->fetchAll();

    // Candidatures récentes reçues
    $stmt_recent = $pdo->prepare("
        SELECT ca.date_candidature, ca.statut, u.nom as candidat_nom, co.titre as concours_titre
        FROM candidatures ca
        JOIN utilisateurs u ON ca.id_candidat = u.id
        JOIN concours co ON ca.id_concours = co.id
        WHERE co.id_gerant = ?
        ORDER BY ca.date_candidature DESC
        LIMIT 15
    ");
    $stmt_recent->execute([$id_gerant]);
    $recent_candidatures = $stmt_recent->fetchAll();

    // Initialisation PDF
    $pdf = new AdmissioPDF();
    $pdf->AddPage();

    // Couleurs
    $colorPrimary = [13, 110, 253]; // Bleu Bootstrap
    $colorTextDark = [33, 37, 41];
    $colorTextMuted = [108, 117, 125];

    // Header
    $pdf->SetFont('Arial', 'B', 20);
    $pdf->SetTextColor($colorPrimary[0], $colorPrimary[1], $colorPrimary[2]);
    $pdf->Cell(0, 10, conv(mb_strtoupper(__t('RAPPORT D\'ACTIVITE - RECRUTEMENT'))), 0, 1, 'C');
    $pdf->Ln(5);

    $pdf->SetFont('Arial', '', 11);
    $pdf->SetTextColor($colorTextMuted[0], $colorTextMuted[1], $colorTextMuted[2]);
    $pdf->Cell(0, 6, conv(__t('Généré le :') . " " . date('d/m/Y H:i')), 0, 1, 'C');
    $pdf->Cell(0, 6, conv(__t('Recruteur :') . " " . $gerant['nom']), 0, 1, 'C');
    $pdf->Ln(10);

    // Section I : Statistiques
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->SetTextColor($colorTextDark[0], $colorTextDark[1], $colorTextDark[2]);
    $pdf->Cell(0, 10, conv("I. " . __t('Statistiques Globales')), 'B', 1, 'L');
    $pdf->Ln(5);

    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(95, 8, conv(__t('Opportunités') . " : " . $stats['offres_total'] . " (" . __t('Actifs') . ": " . $stats['actifs'] . ")"), 0, 0, 'L');
    $pdf->Cell(95, 8, conv(__t('Candidats') . " : " . ($cand_stats['en_attente'] + $cand_stats['validees'])), 0, 1, 'L');
    $pdf->Cell(95, 8, conv(__t('En Attente') . " : " . $cand_stats['en_attente']), 0, 0, 'L');
    $pdf->Cell(95, 8, conv(__t('Validées') . " : " . $cand_stats['validees']), 0, 1, 'L');
    $pdf->Ln(10);

    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, conv("II. " . __t('Candidatures Reçues')), 'B', 1, 'L');
    $pdf->Ln(5);

    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(50, 8, conv(__t('Candidat')), 1, 0, 'L', true);
    $pdf->Cell(70, 8, conv(__t('Titre')), 1, 0, 'L', true);
    $pdf->Cell(35, 8, conv(__t('Date')), 1, 0, 'C', true);
    $pdf->Cell(35, 8, conv(__t('Statut')), 1, 1, 'C', true);

    $pdf->SetFont('Arial', '', 9);
    if(empty($recent_candidatures)) {
        $pdf->Cell(190, 8, conv("Aucune candidature reçue."), 1, 1, 'C');
    } else {
        foreach($recent_candidatures as $rc) {
            $pdf->Cell(50, 8, conv($rc['candidat_nom']), 1, 0, 'L');
            $pdf->Cell(70, 8, conv($rc['concours_titre']), 1, 0, 'L');
            $pdf->Cell(35, 8, conv(date('d/m/Y', strtotime((string)$rc['date_candidature']))), 1, 0, 'C');
            $pdf->Cell(35, 8, conv(strtoupper(str_replace('_', ' ', (string)$rc['statut']))), 1, 1, 'C');
        }
    }
    $pdf->Ln(10);

    // Section III : Opportunités Actives
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, conv("III. " . __t('Mes Opportunités Actives')), 'B', 1, 'L');
    $pdf->Ln(5);

    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(100, 8, conv(__t('Titre')), 1, 0, 'L', true);
    $pdf->Cell(50, 8, conv(__t('date_cloture')), 1, 0, 'C', true);
    $pdf->Cell(40, 8, conv(__t('Statut')), 1, 1, 'C', true);

    $pdf->SetFont('Arial', '', 10);
    if(empty($opportunites)) {
        $pdf->Cell(190, 8, conv("Aucune opportunité active."), 1, 1, 'C');
    } else {
        foreach($opportunites as $opp) {
            $pdf->Cell(100, 8, conv($opp['titre']), 1, 0, 'L');
            $pdf->Cell(50, 8, conv(date('d/m/Y', strtotime((string)$opp['date_cloture']))), 1, 0, 'C');
            $pdf->Cell(40, 8, conv(strtoupper($opp['statut'])), 1, 1, 'C');
        }
    }
    $pdf->Ln(10);

    // Section IV : Entretiens
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, conv("IV. " . __t('Agenda des Entretiens')), 'B', 1, 'L');
    $pdf->Ln(5);

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(40, 8, conv(__t('Date & Heure')), 1, 0, 'C', true);
    $pdf->Cell(60, 8, conv(__t('Candidat')), 1, 0, 'L', true);
    $pdf->Cell(90, 8, conv(__t('Opportunités')), 1, 1, 'L', true);

    $pdf->SetFont('Arial', '', 10);
    if(empty($entretiens)) {
        $pdf->Cell(190, 8, conv("Aucun entretien prévu."), 1, 1, 'C');
    } else {
        foreach($entretiens as $ne) {
            $dateHeure = date('d/m/Y', strtotime((string)$ne['date_entrevue'])) . ' ' . substr((string)$ne['heure_entrevue'], 0, 5);
            $pdf->Cell(40, 8, conv($dateHeure), 1, 0, 'C');
            $pdf->Cell(60, 8, conv($ne['candidat_nom']), 1, 0, 'L');
            $pdf->Cell(90, 8, conv($ne['concours_titre']), 1, 1, 'L');
        }
    }

    if (ob_get_length()) {
        ob_end_clean();
    }
    $pdf->Output('I', 'Rapport_Admissio_' . date('Ymd') . '.pdf');

} catch (PDOException $e) {
    die("Erreur base de données.");
}


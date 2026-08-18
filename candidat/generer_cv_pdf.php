<?php
/**
 * generer_cv_pdf.php – Génération du CV PDF Premium via le helper.
 */
ob_start();
ini_set('display_errors', 0); // Hide errors to avoid PDF corruption
error_reporting(E_ALL);

require_once "../config/database.php";
require_once "../config/session.php";
require_once "../includes/cv_pdf_helper.php";

// Protection candidat
check_role('candidat');
$user_id = $_SESSION['id'];

// Génération via le helper
if (ob_get_level()) ob_clean();
$pdf = build_cv_pdf_object($pdo, $user_id);

if ($pdf) {
    $pdf->Output("I", "CV_Admissio_" . $user_id . ".pdf");
} else {
    echo "Erreur lors de la génération du CV.";
}
exit();

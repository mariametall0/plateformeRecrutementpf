<?php
// Script de diagnostic pour trouver l'erreur exacte
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "../config/database.php";
require_once "../config/session.php";
require_once "../includes/functions.php";

echo "<h1>Diagnostic PDF</h1>";

echo "Check Role: ";
try {
    check_role('candidat');
    echo "OK (Candidat Logged In)";
} catch (Exception $e) {
    echo "FAILED : " . $e->getMessage();
}

echo "<br>Check FPDF File: ";
$fpdf_path = "../libs/fpdf/fpdf.php";
if (file_exists($fpdf_path)) {
    echo "EXISTS ($fpdf_path)";
} else {
    echo "MISSING ! Check your path.";
}

echo "<br>Check Database User Data: ";
$user_id = $_SESSION['id'] ?? 0;
$stmt = $pdo->prepare("SELECT nom FROM utilisateurs WHERE id = ?");
$stmt->execute([$user_id]);
$nom = $stmt->fetchColumn();
echo $nom ? "Found User: $nom" : "User Not Found in DB with ID $user_id";

echo "<br><br>Si tout est OK ci-dessus, le problème vient de l'encodage ou du buffer dans generer_cv_pdf.php.";
?>
<br><a href="generer_cv_pdf.php">Tester la génération PDF maintenant</a>

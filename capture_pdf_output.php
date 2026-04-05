<?php
require_once "config/database.php";
require_once "config/session.php";

echo "<h1>Capture Output Diagnostique</h1>";
ob_start();
include "candidat/generer_cv_pdf.php";
$output = ob_get_clean();

echo "<h2>Sortie Brute (Tronquée si binaire) :</h2>";
echo "<pre>";
echo htmlspecialchars(substr($output, 0, 1000));
echo "</pre>";

if (strpos($output, "%PDF") === 0) {
    echo "<p style='color:green'>Succès : Un flux PDF a commencé.</p>";
} else {
    echo "<p style='color:red'>DÉFAUT : Ce n'est pas un flux PDF valide.</p>";
}

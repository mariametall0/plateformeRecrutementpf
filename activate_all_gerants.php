<?php
require_once "config/database.php";
$stmt = $pdo->prepare("UPDATE utilisateurs SET statut = 'actif' WHERE role = 'gerant'");
$stmt->execute();
echo "TOUS LES GÉRANTS ONT ÉTÉ ACTIVÉS RÉELLEMENT DANS LA BASE.";
?>

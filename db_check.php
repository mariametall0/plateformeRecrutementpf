<?php
require_once "config/database.php";
$stmt = $pdo->query("SELECT id, nom, email, role, statut FROM utilisateurs WHERE role = 'gerant'");
$gerants = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "### GÉRANTS ###\n";
print_r($gerants);

$stmt = $pdo->query("SELECT id, id_gerant, titre, statut FROM offre");
$offre = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "\n### OFFRE ###\n";
print_r($offre);
?>

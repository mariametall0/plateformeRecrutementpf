<?php
require_once "config/database.php";

$stmt = $pdo->query("SELECT id, nom, email, statut FROM utilisateurs");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($rows);
?>

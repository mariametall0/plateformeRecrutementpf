<?php
require_once "config/database.php";
$stmt = $pdo->query("SELECT c.id, c.id_offre, co.id_gerant, c.statut FROM candidatures c JOIN offre co ON c.id_offre = co.id");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT);
?>

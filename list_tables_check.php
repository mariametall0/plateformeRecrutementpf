<?php
require_once 'c:/wamp64/www/plateforme_recrutement/config/database.php';
$stmt = $pdo->query("SHOW TABLES");
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
?>

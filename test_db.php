<?php
require_once "config/database.php";
$stmt=$pdo->query('SHOW COLUMNS FROM offre'); 
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>

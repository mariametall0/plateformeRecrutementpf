<?php
require_once "config/database.php";
$pass = password_hash("Admin123!", PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ?, statut = 'actif' WHERE email = ?");
$stmt->execute([$pass, 'admin@admissio.ma']);
echo "MOT DE PASSE ADMIN RÉINITIALISÉ : Admin123!";
?>

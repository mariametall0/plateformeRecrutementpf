<?php
require_once "config/database.php";

$hash = password_hash("1234", PASSWORD_DEFAULT);

$stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, email, mot_de_passe, role, statut) VALUES (?, ?, ?, ?, 'actif')");
$stmt->execute(["Test Gerant", "gerant@test.com", $hash, "gerant"]);

echo "Utilisateur créé";
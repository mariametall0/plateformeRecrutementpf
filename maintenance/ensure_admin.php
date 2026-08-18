<?php
require_once "config/database.php";
$stmt = $pdo->prepare("SELECT email FROM utilisateurs WHERE role = ?");
$stmt->execute(['admin']);
$admin = $stmt->fetchColumn();

if (!$admin) {
    // Créer un admin par défaut si aucun n'existe
    $email = "admin@admissio.ma";
    $pass = password_hash("Admin123!", PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, email, mot_de_passe, role, statut) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(["Administrateur", $email, $pass, "admin", "actif"]);
    echo "NOUVEAU ADMIN CRÉÉ : " . $email . " / Admin123!";
} else {
    echo "ADMIN EXISTANT : " . $admin;
}
?>

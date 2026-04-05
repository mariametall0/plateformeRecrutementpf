<?php
require_once "config/database.php";

try {
    $pass = password_hash("password", PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE utilisateurs SET email = 'admin@recrutpro.ma', mot_de_passe = ?, statut = 'actif' WHERE role = 'admin'");
    $stmt->execute([$pass]);
    echo "SUCCESS: Admin account reset successfully.";
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage();
}
?>

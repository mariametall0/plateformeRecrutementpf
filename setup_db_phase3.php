<?php
require_once "config/database.php";

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tickets_support (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_utilisateur INT NOT NULL,
            sujet VARCHAR(255) NOT NULL,
            statut ENUM('ouvert', 'ferme') DEFAULT 'ouvert',
            date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS messages_support (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_ticket INT NOT NULL,
            id_emetteur INT NOT NULL,
            message TEXT NOT NULL,
            date_envoi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_ticket) REFERENCES tickets_support(id) ON DELETE CASCADE,
            FOREIGN KEY (id_emetteur) REFERENCES utilisateurs(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    echo "Tables tickets_support et messages_support créées avec succès.";
} catch (PDOException $e) {
    echo "Erreur lors de la création des tables : " . $e->getMessage();
}
?>

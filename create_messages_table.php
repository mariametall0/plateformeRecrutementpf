<?php
require_once "config/database.php";

try {
    $sql = "
    CREATE TABLE IF NOT EXISTS messages_internes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        candidature_id INT NOT NULL,
        emetteur_role ENUM('candidat', 'gerant') NOT NULL,
        emetteur_id INT NOT NULL,
        contenu TEXT NOT NULL,
        date_envoi DATETIME DEFAULT CURRENT_TIMESTAMP,
        is_read BOOLEAN DEFAULT FALSE,
        FOREIGN KEY (candidature_id) REFERENCES candidatures(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($sql);
    echo "TABLE CREATED";
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage();
}
?>

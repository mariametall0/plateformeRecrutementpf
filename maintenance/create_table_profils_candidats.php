<?php
require_once "config/database.php";

try {
    $sql = "
    CREATE TABLE IF NOT EXISTS `profils_candidats` (
      `id` int NOT NULL AUTO_INCREMENT,
      `id_utilisateur` int NOT NULL,
      `secteur_specialite` varchar(255) DEFAULT NULL,
      `niveau_etude` varchar(255) DEFAULT NULL,
      `date_naissance` date DEFAULT NULL,
      `cv_path` varchar(255) DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `fk_profil_candidat_utilisateur` (`id_utilisateur`),
      CONSTRAINT `fk_profil_candidat_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
    ";
    $pdo->exec($sql);
    echo "Table profils_candidats created successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}


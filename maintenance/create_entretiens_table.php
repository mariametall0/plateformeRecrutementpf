<?php
require_once "config/database.php";

$sql = "
CREATE TABLE IF NOT EXISTS entretiens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_candidature INT NOT NULL,
    date_entrevue DATE NOT NULL,
    heure_entrevue TIME NOT NULL,
    lieu_ou_lien VARCHAR(255) NOT NULL,
    notes TEXT NULL,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_candidature) REFERENCES candidatures(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

try {
    $pdo->exec($sql);
    echo "Table entretiens créée avec succès.";
} catch (PDOException $e) {
    echo "Erreur lors de la création de la table : " . $e->getMessage();
}
?>


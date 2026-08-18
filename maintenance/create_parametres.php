<?php
require_once __DIR__ . '/../config/database.php';

try {
    $sql = "
    CREATE TABLE IF NOT EXISTS parametres_globaux (
        cle VARCHAR(50) PRIMARY KEY,
        valeur TEXT NOT NULL,
        description VARCHAR(255),
        type_champ ENUM('text', 'textarea', 'boolean', 'number') DEFAULT 'text'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($sql);
    echo "Table 'parametres_globaux' created successfully.\n";

    // Insérer les paramètres par défaut s'ils n'existent pas
    $defaults = [
        ['site_nom', 'Admissio', 'Nom de la plateforme', 'text'],
        ['site_email', 'contact@admissio.com', 'Adresse e-mail de contact public', 'text'],
        ['site_telephone', '42519122', 'Numéro de téléphone du support', 'text'],
        ['maintenance_mode', '0', 'Activer le mode maintenance (0 = non, 1 = oui)', 'boolean'],
        ['max_upload_size_mb', '5', 'Taille maximale des fichiers uploadés en Mo', 'number'],
        ['facebook_url', '', 'Lien vers la page Facebook', 'text'],
        ['linkedin_url', '', 'Lien vers la page LinkedIn', 'text'],
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO parametres_globaux (cle, valeur, description, type_champ) VALUES (?, ?, ?, ?)");
    foreach ($defaults as $param) {
        $stmt->execute($param);
    }
    
    echo "Default parameters inserted successfully.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

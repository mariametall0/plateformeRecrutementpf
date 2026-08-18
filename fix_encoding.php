<?php
require_once "config/database.php";

try {
    $pdo->exec("UPDATE parametres_globaux SET description = 'Taille maximale des fichiers uploadés en Mo' WHERE cle = 'max_upload_size_mb'");
    $pdo->exec("UPDATE parametres_globaux SET description = 'Numéro de téléphone du support' WHERE cle = 'site_telephone'");
    echo "Descriptions updated successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

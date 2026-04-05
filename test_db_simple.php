<?php
require_once 'c:/wamp64/www/plateforme_recrutement/config/database.php';
echo "Connexion réussie!\n";

$tables = ['users', 'offres', 'candidatures', 'messages', 'entretiens', 'notifications'];
foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        echo "Table '$table' existe.\n";
    } catch (PDOException $e) {
        echo "Table '$table' MANQUANTE.\n";
    }
}
?>

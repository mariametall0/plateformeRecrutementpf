<?php
require_once 'c:/wamp64/www/plateforme_recrutement/config/database.php';

echo "Vérification de l'existence des fichiers...\n";

$errors = 0;
$success = 0;

// 1. Profils
$stmt = $pdo->query("SELECT id_utilisateur, cv_path FROM profils_candidats WHERE cv_path IS NOT NULL AND cv_path != ''");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $path = __DIR__ . "/uploads/" . $row['cv_path'];
    if (file_exists($path)) {
        $success++;
    } else {
        echo "ERREUR: CV Profil {$row['id_utilisateur']} introuvable: $path\n";
        $errors++;
    }
}

// 2. Dossiers
$stmt = $pdo->query("SELECT id, nom_fichier FROM dossiers");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $path = __DIR__ . "/uploads/" . $row['nom_fichier'];
    if (file_exists($path)) {
        $success++;
    } else {
        echo "ERREUR: Document Dossier {$row['id']} introuvable: $path\n";
        $errors++;
    }
}

echo "\nRésultat : $success fichiers ok, $errors erreurs.\n";
?>

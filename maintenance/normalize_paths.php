<?php
require_once 'c:/wamp64/www/plateforme_recrutement/config/database.php';

echo "Normalisation des chemins de fichiers...\n";

// 1. Table profils_candidats
$stmt = $pdo->query("SELECT id_utilisateur, cv_path FROM profils_candidats WHERE cv_path IS NOT NULL AND cv_path != ''");
$profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($profiles as $p) {
    $old = $p['cv_path'];
    $new = $old;
    
    // Supprimer 'uploads/' au début si présent
    if (strpos($old, 'uploads/') === 0) {
        $new = substr($old, 8);
    }
    
    if ($new !== $old) {
        $update = $pdo->prepare("UPDATE profils_candidats SET cv_path = ? WHERE id_utilisateur = ?");
        $update->execute([$new, $p['id_utilisateur']]);
        echo "Profil {$p['id_utilisateur']} : $old -> $new\n";
    }
}

// 2. Table dossiers
$stmt = $pdo->query("SELECT id, nom_fichier FROM dossiers");
$docs = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($docs as $d) {
    $old = $d['nom_fichier'];
    $new = $old;
    
    if (strpos($old, 'uploads/') === 0) {
        $new = substr($old, 8);
    }
    
    if ($new !== $old) {
        $update = $pdo->prepare("UPDATE dossiers SET nom_fichier = ? WHERE id = ?");
        $update->execute([$new, $d['id']]);
        echo "Dossier {$d['id']} : $old -> $new\n";
    }
}

echo "Terminé.\n";
?>

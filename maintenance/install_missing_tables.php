<?php
/**
 * Script d'installation des tables manquantes
 * Accès : http://localhost/plateforme_recrutement/install_missing_tables.php
 */

require_once "config/database.php";

$created_tables = [];
$errors = [];

// SQL pour créer les tables manquantes
$sql_statements = [
    'profils_candidats' => "
        CREATE TABLE IF NOT EXISTS `profils_candidats` (
          `id` int NOT NULL AUTO_INCREMENT,
          `id_utilisateur` int NOT NULL UNIQUE,
          `secteur_specialite` varchar(255) DEFAULT NULL,
          `niveau_etude` varchar(100) DEFAULT NULL,
          `bio` text,
          `cv_path` varchar(255) DEFAULT NULL,
          `photo_path` varchar(255) DEFAULT NULL,
          `score_profil` int DEFAULT 0,
          `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          `date_modification` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `fk_profil_utilisateur` (`id_utilisateur`),
          CONSTRAINT `fk_profil_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
    ",
    
    'cv_formations' => "
        CREATE TABLE IF NOT EXISTS `cv_formations` (
          `id` int NOT NULL AUTO_INCREMENT,
          `id_utilisateur` int NOT NULL,
          `etablissement` varchar(255) NOT NULL,
          `diplome` varchar(255) NOT NULL,
          `domaine` varchar(255) DEFAULT NULL,
          `date_debut` date DEFAULT NULL,
          `date_fin` date DEFAULT NULL,
          `description` text,
          `date_ajout` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `fk_formation_utilisateur` (`id_utilisateur`),
          CONSTRAINT `fk_formation_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
    ",
    
    'cv_experiences' => "
        CREATE TABLE IF NOT EXISTS `cv_experiences` (
          `id` int NOT NULL AUTO_INCREMENT,
          `id_utilisateur` int NOT NULL,
          `entreprise` varchar(255) NOT NULL,
          `poste` varchar(255) NOT NULL,
          `secteur` varchar(255) DEFAULT NULL,
          `date_debut` date DEFAULT NULL,
          `date_fin` date DEFAULT NULL,
          `en_cours` tinyint(1) DEFAULT 0,
          `description` text,
          `date_ajout` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `fk_experience_utilisateur` (`id_utilisateur`),
          CONSTRAINT `fk_experience_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
    ",
    
    'cv_competences' => "
        CREATE TABLE IF NOT EXISTS `cv_competences` (
          `id` int NOT NULL AUTO_INCREMENT,
          `id_utilisateur` int NOT NULL,
          `competence` varchar(255) NOT NULL,
          `niveau` enum('débutant','intermédiaire','avancé','expert') DEFAULT 'intermédiaire',
          `categorie` varchar(100) DEFAULT NULL,
          `date_ajout` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `fk_competence_utilisateur` (`id_utilisateur`),
          CONSTRAINT `fk_competence_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
    ",
    
    'demandes_session' => "
        CREATE TABLE IF NOT EXISTS `demandes_session` (
          `id` int NOT NULL AUTO_INCREMENT,
          `id_concours` int NOT NULL,
          `id_gerant` int NOT NULL,
          `statut` enum('en_attente','approuvee','rejetee') DEFAULT 'en_attente',
          `message` text,
          `date_demande` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          `date_reponse` timestamp NULL,
          PRIMARY KEY (`id`),
          KEY `fk_demande_concours` (`id_concours`),
          KEY `fk_demande_gerant` (`id_gerant`),
          CONSTRAINT `fk_demande_concours` FOREIGN KEY (`id_concours`) REFERENCES `concours` (`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_demande_gerant` FOREIGN KEY (`id_gerant`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
    ",
    
    'champs_formulaire' => "
        CREATE TABLE IF NOT EXISTS `champs_formulaire` (
          `id` int NOT NULL AUTO_INCREMENT,
          `id_concours` int NOT NULL,
          `libelle` varchar(255) NOT NULL,
          `type_champ` enum('texte','textarea','email','telephone','date','dropdown','checkbox','file') DEFAULT 'texte',
          `description` text,
          `obligatoire` tinyint(1) DEFAULT 1,
          `ordre` int DEFAULT 0,
          `options` json DEFAULT NULL,
          `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `fk_champ_concours` (`id_concours`),
          CONSTRAINT `fk_champ_concours` FOREIGN KEY (`id_concours`) REFERENCES `concours` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
    ",
    
    'reponses_candidature' => "
        CREATE TABLE IF NOT EXISTS `reponses_candidature` (
          `id` int NOT NULL AUTO_INCREMENT,
          `id_candidature` int NOT NULL,
          `id_champ` int NOT NULL,
          `valeur` longtext,
          `date_reponse` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `fk_reponse_candidature` (`id_candidature`),
          KEY `fk_reponse_champ` (`id_champ`),
          CONSTRAINT `fk_reponse_candidature` FOREIGN KEY (`id_candidature`) REFERENCES `candidatures` (`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_reponse_champ` FOREIGN KEY (`id_champ`) REFERENCES `champs_formulaire` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
    ",
    
    'messages_internes' => "
        CREATE TABLE IF NOT EXISTS `messages_internes` (
          `id` int NOT NULL AUTO_INCREMENT,
          `candidature_id` int NOT NULL,
          `emetteur_id` int NOT NULL,
          `recepteur_id` int NOT NULL,
          `message` text NOT NULL,
          `piece_jointe` varchar(255) DEFAULT NULL,
          `lu` tinyint(1) DEFAULT 0,
          `date_envoi` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `fk_message_candidature` (`candidature_id`),
          KEY `fk_message_emetteur` (`emetteur_id`),
          KEY `fk_message_recepteur` (`recepteur_id`),
          CONSTRAINT `fk_message_candidature` FOREIGN KEY (`candidature_id`) REFERENCES `candidatures` (`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_message_emetteur` FOREIGN KEY (`emetteur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_message_recepteur` FOREIGN KEY (`recepteur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
    ",
    
    'offres' => "
        CREATE TABLE IF NOT EXISTS `offres` (
          `id` int NOT NULL AUTO_INCREMENT,
          `titre` varchar(255) NOT NULL,
          `description` text NOT NULL,
          `id_gerant` int NOT NULL,
          `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `fk_offre_gerant` (`id_gerant`),
          CONSTRAINT `fk_offre_gerant` FOREIGN KEY (`id_gerant`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
    "
];

// Exécuter chaque création de table
foreach ($sql_statements as $table_name => $sql) {
    try {
        $pdo->exec($sql);
        $created_tables[] = $table_name;
    } catch (Exception $e) {
        $errors[] = "$table_name: " . $e->getMessage();
    }
}

// Ajouter les colonnes manquantes à la table utilisateurs si nécessaire
try {
    $pdo->exec("ALTER TABLE utilisateurs ADD COLUMN secteur VARCHAR(255) DEFAULT NULL");
} catch (Exception $e) {
    // La colonne existe peut-être déjà
}

// Ajouter les colonnes manquantes aux dossiers
try {
    $pdo->exec("ALTER TABLE dossiers ADD COLUMN taille_fichier INT DEFAULT NULL");
    $pdo->exec("ALTER TABLE dossiers ADD COLUMN type_fichier VARCHAR(50) DEFAULT NULL");
} catch (Exception $e) {
    // Les colonnes existent peut-être déjà
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation des Tables Manquantes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .container-install {
            background: white;
            border-radius: 12px;
            padding: 3rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
        }
        .success-item {
            padding: 0.75rem 1.5rem;
            margin-bottom: 0.5rem;
            background: #ecfdf5;
            border-left: 4px solid #10b981;
            border-radius: 4px;
            color: #059669;
        }
        .error-item {
            padding: 0.75rem 1.5rem;
            margin-bottom: 0.5rem;
            background: #fee2e2;
            border-left: 4px solid #dc2626;
            border-radius: 4px;
            color: #b91c1c;
        }
        .icon-success {
            color: #10b981;
            margin-right: 0.5rem;
        }
        .icon-error {
            color: #dc2626;
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container-install">
        <h1 class="mb-4 fw-bold">⚙️ Installation des Tables Manquantes</h1>
        
        <?php if (count($created_tables) > 0): ?>
            <div class="mb-4">
                <h5 class="mb-3">✅ Tables creées avec succès:</h5>
                <?php foreach ($created_tables as $table): ?>
                    <div class="success-item">
                        <i class="bi bi-check-circle icon-success"></i>
                        <strong><?php echo htmlspecialchars($table); ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (count($errors) > 0): ?>
            <div class="mb-4">
                <h5 class="mb-3">⚠️ Erreurs :</h5>
                <?php foreach ($errors as $error): ?>
                    <div class="error-item">
                        <i class="bi bi-exclamation-circle icon-error"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($created_tables) && empty($errors)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                Toutes les tables existent déjà dans la base de données.
            </div>
        <?php else: ?>
            <div class="alert alert-success mt-4">
                <i class="bi bi-check-lg me-2"></i>
                <strong><?php echo count($created_tables); ?></strong> table(s) installée(s) avec succès !
            </div>
        <?php endif; ?>
        
        <div class="d-grid gap-2 mt-4">
            <a href="index.php" class="btn btn-success btn-lg fw-bold">
                Retour à l'accueil
            </a>
        </div>
    </div>
</body>
</html>


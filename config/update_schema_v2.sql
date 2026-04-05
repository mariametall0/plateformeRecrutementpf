-- Mise à jour du schéma de la base de données Admissio
-- Date: 15 Mars 2026

USE `plateforme_recrutement`;

-- 1. Table Utilisateurs : Ajout de la colonne secteur et des index de performance
ALTER TABLE `utilisateurs` 
ADD COLUMN `secteur` VARCHAR(150) DEFAULT NULL AFTER `pays`,
ADD INDEX `idx_utilisateurs_statut` (`statut`),
ADD INDEX `idx_utilisateurs_role` (`role`);

-- 2. Table Candidatures : Ajout de la colonne preselction
ALTER TABLE `candidatures` 
ADD COLUMN `preselction` ENUM('oui', 'non') DEFAULT 'non' AFTER `statut`;

-- 3. Table Dossiers : Ajout des métadonnées de fichiers (taille et type MIME)
ALTER TABLE `dossiers` 
ADD COLUMN `taille_fichier` INT DEFAULT NULL AFTER `nom_fichier`,
ADD COLUMN `type_fichier` VARCHAR(50) DEFAULT NULL AFTER `taille_fichier`;

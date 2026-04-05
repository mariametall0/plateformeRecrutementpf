-- Table des champs dynamiques du formulaire de candidature par concours
CREATE TABLE IF NOT EXISTS `champs_formulaire` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_concours` int NOT NULL,
  `libelle` varchar(255) NOT NULL,
  `type_champ` enum('texte','textarea','date','nombre','liste','case_a_cocher','fichier') NOT NULL DEFAULT 'texte',
  `options_liste` text DEFAULT NULL COMMENT 'Options séparées par | pour le type liste',
  `obligatoire` tinyint(1) DEFAULT '0',
  `ordre` int DEFAULT '0',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_champ_concours` (`id_concours`),
  CONSTRAINT `fk_champ_concours` FOREIGN KEY (`id_concours`) REFERENCES `concours` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Table des réponses aux champs dynamiques par candidature
CREATE TABLE IF NOT EXISTS `reponses_candidature` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_candidature` int NOT NULL,
  `id_champ` int NOT NULL,
  `valeur` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_reponse` (`id_candidature`,`id_champ`),
  KEY `fk_reponse_candidature` (`id_candidature`),
  KEY `fk_reponse_champ` (`id_champ`),
  CONSTRAINT `fk_reponse_candidature` FOREIGN KEY (`id_candidature`) REFERENCES `candidatures` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reponse_champ` FOREIGN KEY (`id_champ`) REFERENCES `champs_formulaire` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Table des demandes de session par les gérants
CREATE TABLE IF NOT EXISTS `demandes_session` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_concours` int NOT NULL,
  `id_gerant` int NOT NULL,
  `date_demande` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `message` text DEFAULT NULL,
  `statut` enum('en_attente','approuvee','refusee') DEFAULT 'en_attente',
  PRIMARY KEY (`id`),
  KEY `fk_demande_concours` (`id_concours`),
  KEY `fk_demande_gerant` (`id_gerant`),
  CONSTRAINT `fk_demande_concours` FOREIGN KEY (`id_concours`) REFERENCES `concours` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_demande_gerant` FOREIGN KEY (`id_gerant`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

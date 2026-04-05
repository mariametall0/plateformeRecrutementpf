-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : ven. 13 mars 2026 à 19:55
-- Version du serveur : 9.1.0
-- Version de PHP : 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `plateforme_recrutement`
--

-- --------------------------------------------------------

--
-- Structure de la table `candidatures`
--

DROP TABLE IF EXISTS `candidatures`;
CREATE TABLE IF NOT EXISTS `candidatures` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_candidat` int NOT NULL,
  `id_concours` int NOT NULL,
  `statut` enum('en_attente','validee','rejetee') DEFAULT 'en_attente',
  `date_candidature` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_candidat_2` (`id_candidat`,`id_concours`),
  KEY `id_candidat` (`id_candidat`),
  KEY `id_concours` (`id_concours`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `concours`
--

DROP TABLE IF EXISTS `concours`;
CREATE TABLE IF NOT EXISTS `concours` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `date_ouverture` date NOT NULL,
  `heure_ouverture` time DEFAULT NULL,
  `date_cloture` date NOT NULL,
  `heure_cloture` time DEFAULT NULL,
  `id_gerant` int NOT NULL,
  `statut` enum('actif','inactif') DEFAULT 'inactif',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_gerant` (`id_gerant`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `concours`
--

INSERT INTO `concours` (`id`, `titre`, `description`, `date_ouverture`, `heure_ouverture`, `date_cloture`, `heure_cloture`, `id_gerant`, `statut`, `date_creation`) VALUES
(1, 'Recrutement de GRH', 'joisdfv', '2026-03-10', '12:00:00', '2026-03-31', '12:00:00', 4, 'inactif', '2026-03-09 22:34:22');

-- --------------------------------------------------------

--
-- Structure de la table `dossiers`
--

DROP TABLE IF EXISTS `dossiers`;
CREATE TABLE IF NOT EXISTS `dossiers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_candidature` int NOT NULL,
  `type_document` enum('cv','lettre_motivation','diplome','autre') NOT NULL,
  `nom_fichier` varchar(255) NOT NULL,
  `etat` enum('en_attente','valide','rejete') DEFAULT 'en_attente',
  `date_upload` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_candidature` (`id_candidature`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `entreprises`
--

DROP TABLE IF EXISTS `entreprises`;
CREATE TABLE IF NOT EXISTS `entreprises` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int NOT NULL,
  `nom_entreprise` varchar(255) NOT NULL,
  `registre_commerce` varchar(100) DEFAULT NULL,
  `secteur_activite` varchar(255) DEFAULT NULL,
  `responsable` varchar(255) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_entreprise_utilisateur` (`id_utilisateur`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `sessions_concours`
--

DROP TABLE IF EXISTS `sessions_concours`;
CREATE TABLE IF NOT EXISTS `sessions_concours` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_concours` int NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `statut` enum('active','inactive') DEFAULT 'inactive',
  PRIMARY KEY (`id`),
  KEY `id_concours` (`id_concours`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

DROP TABLE IF EXISTS `utilisateurs`;
CREATE TABLE IF NOT EXISTS `utilisateurs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `role` enum('admin','gerant','candidat') NOT NULL,
  `statut` enum('actif','inactif') DEFAULT 'inactif',
  `telephone` varchar(20) DEFAULT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `pays` varchar(100) DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `reset_token` varchar(255) DEFAULT NULL,
  `token_expire` datetime DEFAULT NULL,
  `reset_last_request` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `nom`, `email`, `mot_de_passe`, `role`, `statut`, `telephone`, `adresse`, `pays`, `date_creation`, `reset_token`, `token_expire`, `reset_last_request`) VALUES
(1, 'Habsatou Tall', 'mariametall06@gmail.com', '$2y$10$tOr85FHZ.SV6wlSRkIVipOwiyOU7bQ/u18x3URZgiRjYBGWdhelBi', 'candidat', 'inactif', '+222 43195103', 'Nouakchott', 'Mauritanie', '2026-03-05 21:52:48', '3098367646bfd5590dc3b22524e53262ed74d510d161c394182ab8d9b5b4e413', '2026-03-06 00:09:40', '2026-03-06 00:06:40'),
(2, 'mariame Tall', 'habsatoutall20@gmail.com', '$2y$10$Rv4.RddW1wObK.MHILhCEuC.UYGa82Ugc2efFaL7.oB9nYQ6lecI.', 'candidat', 'inactif', '+222 42519122', 'Selibaby', 'Mauritanie', '2026-03-06 00:34:27', '357d729f2664f3d456b127c65add1abf09e7a5a0505f9e94528a508707a13e36', '2026-03-06 00:46:55', '2026-03-06 00:43:55'),
(3, 'fatima Tall', 'tfama598@gmail.com', '$2y$10$PgzKJOY/oxp53d3IR0S3suyfzBS1b9NoicSHCQeqaaW2ThPdrEFHa', 'candidat', 'inactif', '46889052', 'cite plage', 'Mauritanie', '2026-03-07 22:52:27', NULL, NULL, NULL),
(4, 'bebe Tall', 'bt276863@gmail.com', '$2y$10$ZR61ZuossmV4BCXhH0HrT.a8jjAJqGzN0LTWKZx4qxPPZi9xnXp8K', 'gerant', 'inactif', '+22243195103', 'tevragh zeina', 'Mauritania (‫موريتانيا‬‎)', '2026-03-09 22:32:45', NULL, NULL, NULL);

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `candidatures`
--
ALTER TABLE `candidatures`
  ADD CONSTRAINT `fk_candidature_candidat` FOREIGN KEY (`id_candidat`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_candidature_concours` FOREIGN KEY (`id_concours`) REFERENCES `concours` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `concours`
--
ALTER TABLE `concours`
  ADD CONSTRAINT `fk_concours_gerant` FOREIGN KEY (`id_gerant`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `dossiers`
--
ALTER TABLE `dossiers`
  ADD CONSTRAINT `fk_dossier_candidature` FOREIGN KEY (`id_candidature`) REFERENCES `candidatures` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `entreprises`
--
ALTER TABLE `entreprises`
  ADD CONSTRAINT `fk_entreprise_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `sessions_concours`
--
ALTER TABLE `sessions_concours`
  ADD CONSTRAINT `fk_session_concours` FOREIGN KEY (`id_concours`) REFERENCES `concours` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

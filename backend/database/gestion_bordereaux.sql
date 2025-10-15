-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : mar. 14 oct. 2025 à 22:21
-- Version du serveur : 8.3.0
-- Version de PHP : 8.2.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `gestion_bordereaux`
--

-- --------------------------------------------------------

--
-- Structure de la table `banque`
--

DROP TABLE IF EXISTS `banque`;
CREATE TABLE IF NOT EXISTS `banque` (
  `id_banque` int NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `section` enum('PVO005','PSC005') COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_banque`),
  UNIQUE KEY `unique_id_banque` (`id_banque`)
) ;

--
-- Déchargement des données de la table `banque`
--

INSERT INTO `banque` (`id_banque`, `nom`, `date_creation`, `section`) VALUES
(101, 'BNI Madagascar', '2025-09-21 08:10:24', 'PVO005'),
(102, 'BOA Madagascar', '2025-09-21 08:10:24', 'PSC005'),
(103, 'Rasolo', '2025-09-21 08:44:38', 'PVO005'),
(104, 'SOAVOLA', '2025-09-23 10:52:03', 'PVO005');

-- --------------------------------------------------------

--
-- Structure de la table `bordereau`
--

DROP TABLE IF EXISTS `bordereau`;
CREATE TABLE IF NOT EXISTS `bordereau` (
  `id_bordereau` int NOT NULL,
  `matricule` varchar(6) COLLATE utf8mb4_general_ci NOT NULL,
  `reference` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '',
  `objet` varchar(500) COLLATE utf8mb4_general_ci NOT NULL,
  `statut` enum('Mandatement','Secours','VISA') COLLATE utf8mb4_general_ci NOT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `est_envoye` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_bordereau`,`matricule`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `bordereau`
--

INSERT INTO `bordereau` (`id_bordereau`, `matricule`, `reference`, `objet`, `statut`, `date_creation`, `est_envoye`) VALUES
(2012, '789652', '752/HGF', 'gzg\"', 'VISA', '2025-09-14 17:32:57', 1),
(2012, '852123', '862/HJK', 'ehzhy', 'VISA', '2025-09-14 17:33:23', 1),
(2014, '201458', '568/DS', 'ddede', 'Mandatement', '2025-09-14 17:34:40', 1),
(2014, '458746', '568/DS', 'ddede', 'Mandatement', '2025-09-14 17:34:40', 1),
(2014, '478965', '258/TT', 'cdcdcdfrt', 'Mandatement', '2025-09-14 17:40:04', 1),
(2014, '965312', '568/DS', 'ddede', 'Mandatement', '2025-09-14 17:34:40', 1),
(2015, '324778', '521/KJH', 'Demande de dépot', 'VISA', '2025-09-14 18:03:59', 1),
(2015, '511134', '521/KJH', 'Demande de dépot', 'VISA', '2025-09-14 18:03:59', 1),
(2016, '147845', '', 'qqdfefef', 'VISA', '2025-09-15 09:16:02', 1),
(2016, '789654', '', 'qqdfefef', 'VISA', '2025-09-15 09:16:02', 1),
(2017, '456789', '', 'Degregerey', 'VISA', '2025-09-16 08:34:41', 1),
(2017, '478912', '', 'Degregerey', 'VISA', '2025-09-16 08:34:41', 1),
(2018, '457894', '206/CISCO', 'Demande mandatement', 'Mandatement', '2025-09-16 09:16:30', 1),
(2019, 'A25468', '74/MSE', 'Demande de secours au décès', 'Secours', '2025-10-12 20:49:47', 1),
(2020, '123546', '1141/DRETP', 'Erffse', 'Mandatement', '2025-10-13 09:42:35', 1),
(2020, '213521', '1141/DRETP', 'Erffse', 'Mandatement', '2025-10-13 09:42:35', 1);

-- --------------------------------------------------------

--
-- Structure de la table `departement`
--

DROP TABLE IF EXISTS `departement`;
CREATE TABLE IF NOT EXISTS `departement` (
  `id_departement` int NOT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expediteur` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'SRSP Atsimo Andrefana',
  `destination` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `nature` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_departement`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `departement`
--

INSERT INTO `departement` (`id_departement`, `date_creation`, `expediteur`, `destination`, `nature`) VALUES
(145, '2025-10-06 09:39:27', 'SRSP Atsimo Andrefana', 'Manakara', 'zfffree');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateur`
--

DROP TABLE IF EXISTS `utilisateur`;
CREATE TABLE IF NOT EXISTS `utilisateur` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `type_utilisateur` enum('admin','user') DEFAULT 'user',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `utilisateur`
--

INSERT INTO `utilisateur` (`id`, `email`, `mot_de_passe`, `type_utilisateur`) VALUES
(1, 'norlandehubery@gmail.com', '$2y$10$ieV93UK2uUYOJ5.mGrBUMObVfAL0/vaIt2ei2vCfRObSXds.Z47KG', 'admin'),
(3, 'ornellaclaudia0@gmail.com', '$2y$10$MNIFKGU3ZE/kOFY5X2TKGuqQnX6DxFi29qH7gZlZhxfMCrGBGIzT6', 'user');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

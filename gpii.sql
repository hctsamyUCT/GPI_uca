-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : lun. 07 juil. 2025 à 10:53
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
-- Base de données : `gpii`
--

-- --------------------------------------------------------

--
-- Structure de la table `appareils`
--

DROP TABLE IF EXISTS `appareils`;
CREATE TABLE IF NOT EXISTS `appareils` (
  `id_appareil` int NOT NULL AUTO_INCREMENT,
  `type` enum('Imprimante','Projecteur') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `modele` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `numero_inventaire` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `etat` enum('Operationnel','En panne') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Operationnel',
  `id_marque` int NOT NULL,
  `id_salle` int UNSIGNED DEFAULT NULL,
  `date_ajout` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `supprime_le` timestamp NULL DEFAULT NULL,
  `est_affecte` tinyint(1) GENERATED ALWAYS AS ((`id_salle` is not null)) VIRTUAL,
  PRIMARY KEY (`id_appareil`),
  UNIQUE KEY `numero_serie` (`numero_inventaire`),
  KEY `id_marque` (`id_marque`),
  KEY `id_salle` (`id_salle`),
  KEY `type` (`type`),
  KEY `etat` (`etat`),
  KEY `supprime_le` (`supprime_le`)
) ENGINE=InnoDB AUTO_INCREMENT=123 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déclencheurs `appareils`
--
DROP TRIGGER IF EXISTS `before_appareils_delete`;
DELIMITER $$
CREATE TRIGGER `before_appareils_delete` BEFORE DELETE ON `appareils` FOR EACH ROW BEGIN
       DECLARE v_nom_marque VARCHAR(50);
       DECLARE v_nom_salle VARCHAR(50);

       SELECT nom_marque INTO v_nom_marque 
       FROM marque 
       WHERE id_marque = OLD.id_marque;

       -- Gérer le cas où la salle est NULL
       IF OLD.id_salle IS NOT NULL THEN
           SELECT nom_salle INTO v_nom_salle 
           FROM salles 
           WHERE id_salle = OLD.id_salle;
       ELSE
           SET v_nom_salle = NULL;
       END IF;

       INSERT INTO archives_appareils (
           id_appareil, 
           type, 
           modele, 
           numero_inventaire, 
           etat, 
           id_marque,
           nom_marque,
           id_salle,
           nom_salle,
           date_ajout
       ) VALUES (
           OLD.id_appareil, 
           OLD.type, 
           OLD.modele, 
           OLD.numero_inventaire, 
           OLD.etat, 
           OLD.id_marque,
           v_nom_marque,
           OLD.id_salle,
           v_nom_salle,
           OLD.date_ajout
       );
   END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `archives_appareils`
--

DROP TABLE IF EXISTS `archives_appareils`;
CREATE TABLE IF NOT EXISTS `archives_appareils` (
  `id_archive` int NOT NULL AUTO_INCREMENT,
  `id_appareil` int NOT NULL,
  `type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `modele` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `numero_inventaire` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `etat` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_marque` int NOT NULL,
  `nom_marque` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_salle` int UNSIGNED DEFAULT NULL,
  `nom_salle` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_ajout` timestamp NULL DEFAULT NULL,
  `date_archive` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_archive`),
  KEY `numero_inventaire` (`numero_inventaire`)
) ENGINE=InnoDB AUTO_INCREMENT=131 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `archives_appareils`
--

INSERT INTO `archives_appareils` (`id_archive`, `id_appareil`, `type`, `modele`, `numero_inventaire`, `etat`, `id_marque`, `nom_marque`, `id_salle`, `nom_salle`, `date_ajout`, `date_archive`) VALUES
(129, 122, 'Projecteur', 'yyy', 'PRJ/001/2025', 'Operationnel', 5, 'Acer', 10, 'Bureau BBA', '2025-07-07 10:45:37', '2025-07-07 10:49:40'),
(130, 121, 'Imprimante', 'ttt', 'IMP/001/2025', 'Operationnel', 5, 'Acer', NULL, NULL, '2025-07-07 10:39:38', '2025-07-07 10:49:42');

-- --------------------------------------------------------

--
-- Structure de la table `archives_peripherique`
--

DROP TABLE IF EXISTS `archives_peripherique`;
CREATE TABLE IF NOT EXISTS `archives_peripherique` (
  `id_archive` int NOT NULL AUTO_INCREMENT,
  `id_peripherique` int NOT NULL,
  `type` enum('Clavier','Souris','Ecran') NOT NULL,
  `id_marque` int NOT NULL,
  `nom_marque` varchar(255) DEFAULT NULL,
  `numero_inventaire` varchar(255) NOT NULL,
  `etat` enum('Operationnel','En panne') DEFAULT 'Operationnel',
  `date_ajout` datetime DEFAULT NULL,
  `supprime_le` datetime DEFAULT NULL,
  `est_affecte` tinyint(1) DEFAULT NULL,
  `date_archive` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_archive`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `archives_peripherique`
--

INSERT INTO `archives_peripherique` (`id_archive`, `id_peripherique`, `type`, `id_marque`, `nom_marque`, `numero_inventaire`, `etat`, `date_ajout`, `supprime_le`, `est_affecte`, `date_archive`) VALUES
(8, 8, 'Clavier', 4, 'Brother', 'bduaibdiazd', 'Operationnel', '2025-02-19 15:59:26', NULL, 0, '2025-02-19 14:59:29'),
(9, 22, 'Ecran', 1, 'HP', 't323nd', 'Operationnel', '2025-02-20 03:35:04', NULL, 0, '2025-02-25 20:55:11'),
(10, 14, 'Souris', 6, 'Dell', 'a1023', 'Operationnel', '2025-02-20 03:31:18', NULL, 0, '2025-02-25 21:07:35'),
(13, 9, 'Clavier', 5, 'Acer', 'xcfd', 'Operationnel', '2025-02-19 16:14:42', NULL, 0, '2025-04-16 06:19:21'),
(14, 10, 'Clavier', 5, 'Acer', 'ssabij', 'En panne', '2025-02-19 16:20:02', NULL, 0, '2025-06-29 08:42:08'),
(15, 11, 'Clavier', 6, 'Dell', 'c3dnz', 'Operationnel', '2025-02-20 03:29:26', NULL, 1, '2025-06-29 08:42:08'),
(16, 12, 'Clavier', 6, 'Dell', 'd3DNO', 'Operationnel', '2025-02-20 03:29:55', NULL, 1, '2025-06-29 08:42:08'),
(17, 13, 'Clavier', 6, 'Dell', 'dzzde', 'Operationnel', '2025-02-20 03:30:07', NULL, 0, '2025-06-29 08:42:08'),
(18, 15, 'Souris', 6, 'Dell', 'b293', 'Operationnel', '2025-02-20 03:31:30', NULL, 1, '2025-06-29 08:42:08'),
(19, 16, 'Souris', 6, 'Dell', 'dnizd', 'Operationnel', '2025-02-20 03:31:51', NULL, 1, '2025-06-29 08:42:08'),
(20, 17, 'Souris', 3, 'Canon', 'nzkzl', 'Operationnel', '2025-02-20 03:32:07', NULL, 1, '2025-06-29 08:42:08'),
(21, 18, 'Souris', 6, 'Dell', 'ueyhd', 'Operationnel', '2025-02-20 03:33:55', NULL, 1, '2025-06-29 08:42:08'),
(22, 19, 'Ecran', 1, 'HP', 'a4end', 'Operationnel', '2025-02-20 03:34:09', NULL, 0, '2025-06-29 08:42:08'),
(23, 20, 'Ecran', 1, 'HP', 'b2js', 'Operationnel', '2025-02-20 03:34:27', NULL, 1, '2025-06-29 08:42:08'),
(24, 21, 'Ecran', 1, 'HP', 'c23nsl', 'Operationnel', '2025-02-20 03:34:43', NULL, 1, '2025-06-29 08:42:08'),
(25, 23, 'Ecran', 6, 'Dell', 'dnls', 'Operationnel', '2025-02-20 03:35:23', NULL, 1, '2025-06-29 08:42:08'),
(26, 24, 'Clavier', 4, 'Brother', 'buzbdbzd', 'Operationnel', '2025-02-20 10:06:02', NULL, 1, '2025-06-29 08:42:08'),
(27, 25, 'Clavier', 5, 'Acer', 'eerdxx', 'Operationnel', '2025-02-20 14:04:19', NULL, 1, '2025-06-29 08:42:08'),
(28, 26, 'Clavier', 5, 'Acer', 'JJFZE', 'Operationnel', '2025-02-26 08:42:12', NULL, 0, '2025-06-29 08:42:08'),
(29, 27, 'Clavier', 5, 'Acer', 'SAS233', 'Operationnel', '2025-02-26 08:42:40', NULL, 1, '2025-06-29 08:42:08'),
(30, 28, 'Ecran', 5, 'Acer', 'ndzo239', 'Operationnel', '2025-02-26 08:43:04', NULL, 1, '2025-06-29 08:42:08'),
(31, 29, 'Ecran', 3, 'Canon', 'hrhs293', 'Operationnel', '2025-02-26 08:45:04', NULL, 1, '2025-06-29 08:42:08'),
(32, 30, 'Ecran', 4, 'Brother', 'sbz192', 'Operationnel', '2025-02-26 08:45:14', NULL, 1, '2025-06-29 08:42:08'),
(33, 31, 'Ecran', 4, 'Brother', 'bsa192', 'Operationnel', '2025-02-26 08:45:25', NULL, 0, '2025-06-29 08:42:08'),
(34, 32, 'Souris', 4, 'Brother', 'sbua293', 'Operationnel', '2025-02-26 08:46:08', NULL, 1, '2025-06-29 08:42:08'),
(35, 33, 'Souris', 4, 'Brother', 'sia293', 'Operationnel', '2025-02-26 08:46:17', NULL, 1, '2025-06-29 08:42:08'),
(36, 34, 'Clavier', 5, 'Acer', 'ssabij	', 'Operationnel', '2025-04-16 07:32:42', NULL, 0, '2025-06-29 08:42:08'),
(37, 37, 'Souris', 6, 'Dell', 'SRI/001/2025', 'Operationnel', '2025-06-29 09:57:01', NULL, 0, '2025-06-29 08:57:21');

-- --------------------------------------------------------

--
-- Structure de la table `archives_unite`
--

DROP TABLE IF EXISTS `archives_unite`;
CREATE TABLE IF NOT EXISTS `archives_unite` (
  `id_archive` int NOT NULL AUTO_INCREMENT,
  `id_unite` int NOT NULL,
  `numero_inventaire` varchar(255) NOT NULL,
  `ram` varchar(255) DEFAULT NULL,
  `processeur` varchar(255) DEFAULT NULL,
  `disque_dur_1_type` varchar(255) DEFAULT NULL,
  `disque_dur_1_capacite` varchar(255) DEFAULT NULL,
  `disque_dur_2_type` varchar(255) DEFAULT NULL,
  `disque_dur_2_capacite` varchar(255) DEFAULT NULL,
  `etat` enum('Operationnel','En panne') DEFAULT 'Operationnel',
  `date_achat` date DEFAULT NULL,
  `date_ajout` datetime DEFAULT NULL,
  `supprime_le` datetime DEFAULT NULL,
  `est_affecte` tinyint(1) DEFAULT NULL,
  `date_archive` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_archive`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `archives_unite`
--

INSERT INTO `archives_unite` (`id_archive`, `id_unite`, `numero_inventaire`, `ram`, `processeur`, `disque_dur_1_type`, `disque_dur_1_capacite`, `disque_dur_2_type`, `disque_dur_2_capacite`, `etat`, `date_achat`, `date_ajout`, `supprime_le`, `est_affecte`, `date_archive`) VALUES
(16, 13, 'b3bb', '2 GB', 'INTEL_CORE_I3', 'SSD', '70 GB', NULL, NULL, 'Operationnel', NULL, '2025-02-19 16:13:43', NULL, 1, '2025-03-02 09:04:27'),
(17, 10, 'GZZVHZH', '16 GB', 'INTEL_CORE_I3', 'SSD', '120 GB', NULL, NULL, 'Operationnel', '2025-02-19', '2025-02-19 16:05:40', NULL, 0, '2025-06-29 08:41:43'),
(18, 11, 'a1aa', '4 GB', 'INTEL_CORE_I3', 'HDD', '120 GB', NULL, NULL, 'En panne', NULL, '2025-02-19 16:08:44', NULL, 0, '2025-06-29 08:41:43'),
(19, 12, 'b2bb', '2 GB', 'INTEL_CORE_I5', 'SSD', '70 GB', NULL, NULL, 'Operationnel', NULL, '2025-02-19 16:11:58', NULL, 0, '2025-06-29 08:41:43'),
(20, 14, 'd2dd', '2 GB', 'INTEL_CORE_I3', 'SSD', '500 GB', NULL, NULL, 'Operationnel', NULL, '2025-02-19 16:14:01', NULL, 1, '2025-06-29 08:41:43'),
(21, 15, 't5dd', '4 GB', 'INTEL_CORE_I3', 'SSD', '120 GB', NULL, NULL, 'En panne', NULL, '2025-02-19 16:14:23', NULL, 1, '2025-06-29 08:41:43'),
(22, 16, 'jsnals', '4 GB', 'INTEL_CORE_I5', 'SSD', '70 GB', NULL, NULL, 'Operationnel', NULL, '2025-02-20 06:49:46', NULL, 1, '2025-06-29 08:41:43'),
(23, 17, 'eefne', '4 GB', 'INTEL_CORE_I5', 'SSD', '120 GB', NULL, NULL, 'En panne', NULL, '2025-02-20 08:24:03', NULL, 1, '2025-06-29 08:41:43'),
(24, 18, 'tensks', '2 GB', 'INTEL_CORE_I3', 'SSD', '70 GB', 'SSD', '120 GB', 'En panne', '2001-01-01', '2025-02-26 08:40:52', NULL, 1, '2025-06-29 08:41:43'),
(25, 20, 'UNT/002/2025', '8 GB', 'INTEL_CORE_I5', 'SSD', '120 GB', NULL, NULL, 'Operationnel', '2026-06-29', '2025-06-29 09:50:04', NULL, 0, '2025-06-29 08:50:35');

-- --------------------------------------------------------

--
-- Structure de la table `interventions`
--

DROP TABLE IF EXISTS `interventions`;
CREATE TABLE IF NOT EXISTS `interventions` (
  `id_intervention` int NOT NULL AUTO_INCREMENT,
  `id_ticket` int NOT NULL,
  `id_user` int NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_intervention` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `action_effectuee` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id_intervention`),
  KEY `id_ticket` (`id_ticket`),
  KEY `id_user` (`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `marque`
--

DROP TABLE IF EXISTS `marque`;
CREATE TABLE IF NOT EXISTS `marque` (
  `id_marque` int NOT NULL AUTO_INCREMENT,
  `nom_marque` varchar(255) NOT NULL,
  PRIMARY KEY (`id_marque`),
  UNIQUE KEY `nom_marque` (`nom_marque`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `marque`
--

INSERT INTO `marque` (`id_marque`, `nom_marque`) VALUES
(5, 'Acer'),
(10, 'alhihlil'),
(4, 'Brother'),
(3, 'Canon'),
(6, 'Dell'),
(2, 'Epson'),
(1, 'HP'),
(9, 'Lenovo'),
(8, 'Logitech'),
(7, 'NEC');

-- --------------------------------------------------------

--
-- Structure de la table `peripherique`
--

DROP TABLE IF EXISTS `peripherique`;
CREATE TABLE IF NOT EXISTS `peripherique` (
  `id_peripherique` int NOT NULL AUTO_INCREMENT,
  `type` enum('Clavier','Souris','Ecran') NOT NULL,
  `id_marque` int NOT NULL,
  `numero_inventaire` varchar(255) NOT NULL,
  `etat` enum('Operationnel','En panne') DEFAULT 'Operationnel',
  `date_ajout` datetime DEFAULT CURRENT_TIMESTAMP,
  `supprime_le` datetime DEFAULT NULL,
  `est_affecte` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id_peripherique`),
  UNIQUE KEY `numero_inventaire` (`numero_inventaire`),
  KEY `id_marque` (`id_marque`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `peripherique`
--

INSERT INTO `peripherique` (`id_peripherique`, `type`, `id_marque`, `numero_inventaire`, `etat`, `date_ajout`, `supprime_le`, `est_affecte`) VALUES
(35, 'Clavier', 5, 'CLA/001/2025', 'Operationnel', '2025-06-29 09:56:35', NULL, 1),
(36, 'Clavier', 5, 'CLA/002/2025', 'Operationnel', '2025-06-29 09:56:47', NULL, 1),
(38, 'Ecran', 6, 'ECR/001/2025', 'Operationnel', '2025-06-29 09:57:15', NULL, 1),
(39, 'Ecran', 6, 'ECR/002/2025', 'Operationnel', '2025-06-29 09:57:33', NULL, 1),
(40, 'Ecran', 6, 'ECR/003/2025', 'Operationnel', '2025-06-29 12:04:20', NULL, 0);

--
-- Déclencheurs `peripherique`
--
DROP TRIGGER IF EXISTS `before_peripherique_delete`;
DELIMITER $$
CREATE TRIGGER `before_peripherique_delete` BEFORE DELETE ON `peripherique` FOR EACH ROW BEGIN
    DECLARE v_nom_marque VARCHAR(255);
    SELECT nom_marque INTO v_nom_marque FROM marque WHERE id_marque = OLD.id_marque;
    
    INSERT INTO archives_peripherique (
        id_peripherique,
        type,
        id_marque,
        nom_marque,
        numero_inventaire,
        etat,
        date_ajout,
        supprime_le,
        est_affecte
    )
    VALUES (
        OLD.id_peripherique,
        OLD.type,
        OLD.id_marque,
        v_nom_marque,
        OLD.numero_inventaire,
        OLD.etat,
        OLD.date_ajout,
        OLD.supprime_le,
        OLD.est_affecte
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `poste`
--

DROP TABLE IF EXISTS `poste`;
CREATE TABLE IF NOT EXISTS `poste` (
  `id_poste` int NOT NULL AUTO_INCREMENT,
  `nom_poste` varchar(255) NOT NULL,
  `id_salle` int UNSIGNED NOT NULL,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `etat` enum('opérationnel','en_panne') NOT NULL DEFAULT 'opérationnel',
  PRIMARY KEY (`id_poste`),
  UNIQUE KEY `unique_nom_poste_par_salle` (`nom_poste`,`id_salle`),
  KEY `id_salle` (`id_salle`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `poste`
--

INSERT INTO `poste` (`id_poste`, `nom_poste`, `id_salle`, `date_creation`, `etat`) VALUES
(19, 'poste 01', 1, '2025-06-29 09:58:03', 'opérationnel'),
(20, 'poste 002', 1, '2025-06-29 11:55:36', 'opérationnel'),
(21, 'poste 03', 1, '2025-06-29 11:55:56', 'opérationnel');

-- --------------------------------------------------------

--
-- Structure de la table `poste_peripherique`
--

DROP TABLE IF EXISTS `poste_peripherique`;
CREATE TABLE IF NOT EXISTS `poste_peripherique` (
  `id_poste` int NOT NULL,
  `id_peripherique` int NOT NULL,
  PRIMARY KEY (`id_poste`,`id_peripherique`),
  UNIQUE KEY `unique_peripherique_poste` (`id_peripherique`),
  KEY `id_peripherique` (`id_peripherique`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `poste_peripherique`
--

INSERT INTO `poste_peripherique` (`id_poste`, `id_peripherique`) VALUES
(19, 35),
(21, 36),
(19, 38),
(21, 39);

--
-- Déclencheurs `poste_peripherique`
--
DROP TRIGGER IF EXISTS `check_peripherique_type`;
DELIMITER $$
CREATE TRIGGER `check_peripherique_type` BEFORE INSERT ON `poste_peripherique` FOR EACH ROW BEGIN
    DECLARE type_count INT;
    DECLARE periph_type VARCHAR(50);
    
    SELECT type INTO periph_type 
    FROM peripherique 
    WHERE id_peripherique = NEW.id_peripherique;
    
    SELECT COUNT(*) INTO type_count
    FROM poste_peripherique pp
    JOIN peripherique p ON pp.id_peripherique = p.id_peripherique
    WHERE pp.id_poste = NEW.id_poste AND p.type = periph_type;
    
    IF type_count > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Un périphérique de ce type est déjà affecté à ce poste';
    END IF;
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `reset_est_affecte_peripherique`;
DELIMITER $$
CREATE TRIGGER `reset_est_affecte_peripherique` AFTER DELETE ON `poste_peripherique` FOR EACH ROW BEGIN
    UPDATE peripherique
    SET est_affecte = FALSE
    WHERE id_peripherique = OLD.id_peripherique
      AND NOT EXISTS (SELECT 1 FROM poste_peripherique WHERE id_peripherique = OLD.id_peripherique);
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `update_est_affecte_peripherique`;
DELIMITER $$
CREATE TRIGGER `update_est_affecte_peripherique` AFTER INSERT ON `poste_peripherique` FOR EACH ROW BEGIN
    UPDATE peripherique
    SET est_affecte = TRUE
    WHERE id_peripherique = NEW.id_peripherique;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `poste_unite`
--

DROP TABLE IF EXISTS `poste_unite`;
CREATE TABLE IF NOT EXISTS `poste_unite` (
  `id_poste` int NOT NULL,
  `id_unite` int NOT NULL,
  PRIMARY KEY (`id_poste`,`id_unite`),
  UNIQUE KEY `unique_poste_unite` (`id_poste`),
  UNIQUE KEY `unique_unite_poste` (`id_unite`),
  KEY `id_unite` (`id_unite`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `poste_unite`
--

INSERT INTO `poste_unite` (`id_poste`, `id_unite`) VALUES
(19, 19),
(20, 21),
(21, 22);

--
-- Déclencheurs `poste_unite`
--
DROP TRIGGER IF EXISTS `check_unite_unique`;
DELIMITER $$
CREATE TRIGGER `check_unite_unique` BEFORE INSERT ON `poste_unite` FOR EACH ROW BEGIN
    DECLARE unite_count INT;
    
    -- Vérifier si le poste a déjà une unité
    SELECT COUNT(*) INTO unite_count
    FROM poste_unite
    WHERE id_poste = NEW.id_poste;
    
    IF unite_count > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Ce poste a déjà une unité centrale affectée';
    END IF;
    
    -- Vérifier si l'unité est déjà affectée à un autre poste
    SELECT COUNT(*) INTO unite_count
    FROM poste_unite
    WHERE id_unite = NEW.id_unite;
    
    IF unite_count > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cette unité centrale est déjà affectée à un autre poste';
    END IF;
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `reset_est_affecte_unite`;
DELIMITER $$
CREATE TRIGGER `reset_est_affecte_unite` AFTER DELETE ON `poste_unite` FOR EACH ROW BEGIN
    UPDATE unite
    SET est_affecte = FALSE
    WHERE id_unite = OLD.id_unite
      AND NOT EXISTS (SELECT 1 FROM poste_unite WHERE id_unite = OLD.id_unite);
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `update_est_affecte_unite`;
DELIMITER $$
CREATE TRIGGER `update_est_affecte_unite` AFTER INSERT ON `poste_unite` FOR EACH ROW BEGIN
    UPDATE unite
    SET est_affecte = TRUE
    WHERE id_unite = NEW.id_unite;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE IF NOT EXISTS `roles` (
  `id_role` int NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id_role`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `roles`
--

INSERT INTO `roles` (`id_role`, `role_name`) VALUES
(1, 'Admin'),
(5, 'Agent administratif'),
(3, 'Chargé de formation'),
(4, 'Professeur'),
(2, 'Technicien');

-- --------------------------------------------------------

--
-- Structure de la table `salles`
--

DROP TABLE IF EXISTS `salles`;
CREATE TABLE IF NOT EXISTS `salles` (
  `id_salle` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom_salle` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `etage` enum('2','3') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `groupe` enum('Administratif','Etude','Cisco') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id_salle`),
  UNIQUE KEY `unique_salle_etage` (`nom_salle`,`etage`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `salles`
--

INSERT INTO `salles` (`id_salle`, `nom_salle`, `etage`, `groupe`) VALUES
(1, 'Salle Réseau Cisco 1', '3', 'Cisco'),
(2, 'Salle Réseau Cisco 2', '3', 'Cisco'),
(3, 'Salle Informatique', '3', 'Etude'),
(4, 'Salle d\'Etude 1', '3', 'Etude'),
(5, 'Salle d\'Etude 2', '3', 'Etude'),
(6, 'Salle d\'Etude 3', '3', 'Etude'),
(7, 'Salle d\'Etude 4', '3', 'Etude'),
(8, 'Bureau Directeur', '3', 'Administratif'),
(9, 'Bureau Responsable IT', '3', 'Administratif'),
(10, 'Bureau BBA', '3', 'Administratif'),
(11, 'Salle d\'Etude 5', '2', 'Etude'),
(12, 'Salle d\'Etude 6', '2', 'Etude'),
(13, 'Salle d\'Etude 7', '2', 'Etude'),
(14, 'Service Accueil', '2', 'Administratif'),
(15, 'Comptabilité', '2', 'Administratif'),
(16, 'test', '2', 'Administratif');

-- --------------------------------------------------------

--
-- Structure de la table `tickets`
--

DROP TABLE IF EXISTS `tickets`;
CREATE TABLE IF NOT EXISTS `tickets` (
  `id_ticket` int NOT NULL AUTO_INCREMENT,
  `id_user` int NOT NULL,
  `id_salle` int UNSIGNED DEFAULT NULL,
  `id_poste` int DEFAULT NULL,
  `id_appareil` int DEFAULT NULL,
  `id_peripherique` int DEFAULT NULL,
  `id_unite` int DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `priorite` enum('Haute','Moyenne','Faible') COLLATE utf8mb4_unicode_ci DEFAULT 'Moyenne',
  `etat` enum('Ouvert','En cours','Probleme Résoulu(fermé)','probleme non resoulu(fermé)') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Ouvert',
  `categorie` enum('Matériel','Logiciel','Réseau','Demande') COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_fermeture` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_ticket`),
  KEY `id_user` (`id_user`),
  KEY `id_salle` (`id_salle`),
  KEY `id_poste` (`id_poste`),
  KEY `id_appareil` (`id_appareil`),
  KEY `id_peripherique` (`id_peripherique`),
  KEY `id_unite` (`id_unite`)
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déclencheurs `tickets`
--
DROP TRIGGER IF EXISTS `update_date_fermeture`;
DELIMITER $$
CREATE TRIGGER `update_date_fermeture` BEFORE UPDATE ON `tickets` FOR EACH ROW BEGIN
    IF NEW.etat IN ('Probleme Résoulu(fermé)', 'probleme non resoulu(fermé)') 
    AND OLD.etat NOT IN ('Probleme Résoulu(fermé)', 'probleme non resoulu(fermé)') THEN
        SET NEW.date_fermeture = CURRENT_TIMESTAMP;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `unite`
--

DROP TABLE IF EXISTS `unite`;
CREATE TABLE IF NOT EXISTS `unite` (
  `id_unite` int NOT NULL AUTO_INCREMENT,
  `numero_inventaire` varchar(255) NOT NULL,
  `ram` enum('2 GB','4 GB','8 GB','16 GB','32 GB','64 GB') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `processeur` enum('INTEL_CORE_I3','INTEL_CORE_I5','INTEL_CORE_I7','INTEL_CORE_I9','INTEL_XEON','INTEL_PENTIUM','INTEL_CELERON') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `disque_dur_1_type` enum('SSD','HDD') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `disque_dur_1_capacite` enum('70 GB','120 GB','500 GB') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `disque_dur_2_type` enum('SSD','HDD') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `disque_dur_2_capacite` enum('70 GB','120 GB','500 GB') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `etat` enum('Operationnel','En panne') DEFAULT 'Operationnel',
  `date_achat` date DEFAULT NULL,
  `date_ajout` datetime DEFAULT CURRENT_TIMESTAMP,
  `supprime_le` datetime DEFAULT NULL,
  `est_affecte` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id_unite`),
  UNIQUE KEY `numero_inventaire` (`numero_inventaire`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `unite`
--

INSERT INTO `unite` (`id_unite`, `numero_inventaire`, `ram`, `processeur`, `disque_dur_1_type`, `disque_dur_1_capacite`, `disque_dur_2_type`, `disque_dur_2_capacite`, `etat`, `date_achat`, `date_ajout`, `supprime_le`, `est_affecte`) VALUES
(19, 'UNT/001/2025', '4 GB', 'INTEL_CORE_I3', 'HDD', '70 GB', NULL, NULL, 'Operationnel', '2025-06-29', '2025-06-29 09:49:18', NULL, 1),
(21, 'UNT/001/2026', '4 GB', 'INTEL_CORE_I3', 'SSD', '120 GB', NULL, NULL, 'Operationnel', '2026-06-29', '2025-06-29 09:51:00', NULL, 1),
(22, 'UNT/002/2025', '4 GB', 'INTEL_CORE_I3', 'SSD', '70 GB', NULL, NULL, 'Operationnel', '2025-06-29', '2025-06-29 09:51:22', NULL, 1),
(23, 'UNT/001/2011', '2 GB', 'INTEL_CORE_I5', 'HDD', '120 GB', NULL, NULL, 'Operationnel', '2011-01-01', '2025-07-07 07:41:04', NULL, 0);

--
-- Déclencheurs `unite`
--
DROP TRIGGER IF EXISTS `before_unite_delete`;
DELIMITER $$
CREATE TRIGGER `before_unite_delete` BEFORE DELETE ON `unite` FOR EACH ROW BEGIN
    INSERT INTO archives_unite (
        id_unite,
        numero_inventaire,
        ram,
        processeur,
        disque_dur_1_type,
        disque_dur_1_capacite,
        disque_dur_2_type,
        disque_dur_2_capacite,
        etat,
        date_achat,
        date_ajout,
        supprime_le,
        est_affecte
    )
    VALUES (
        OLD.id_unite,
        OLD.numero_inventaire,
        OLD.ram,
        OLD.processeur,
        OLD.disque_dur_1_type,
        OLD.disque_dur_1_capacite,
        OLD.disque_dur_2_type,
        OLD.disque_dur_2_capacite,
        OLD.etat,
        OLD.date_achat,
        OLD.date_ajout,
        OLD.supprime_le,
        OLD.est_affecte
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id_user` int NOT NULL AUTO_INCREMENT,
  `prenom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_role` int NOT NULL,
  `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `code_utilisateur` varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `code_utilisateur` (`code_utilisateur`),
  KEY `fk_users_role` (`id_role`),
  KEY `idx_code_utilisateur` (`code_utilisateur`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id_user`, `prenom`, `nom`, `email`, `password`, `id_role`, `date_created`, `code_utilisateur`) VALUES
(17, 'samy', 'houchat', 'samy@gmail.com', '$2y$10$8sxVFsvHSYirOFyNkM38Fe27xzCOFdsJAsy2yXY9DxBGVkFDEiQiW', 1, '2025-06-22 18:16:55', 'SAHO05'),
(18, 'nabil', 'Bouiche', 'bouiche@gmail.com', '$2y$10$bjrmOcNYCGULCXviRkIrpejOvZq74vVxcNLndQcVZnVbXjhmsGZr.', 1, '2025-06-25 10:25:07', 'NABO79'),
(20, 'moulaa', 'moulawi', 'moulawi@gmail.com', '$2y$10$cB7G/jdM3b5ybfXflJdVYe.nCQoYNv18IRevmhp/9pe43Jd/baRuG', 3, '2025-06-25 10:51:57', 'MOMO39'),
(21, 'djamila', 'amrouche', 'djamila@gmail.com', '$2y$10$h4HPOD6nZjPubLvznkz7qe.MP06Hesas9.cn8L3zM8DzeTuXQgqoa', 4, '2025-06-25 11:02:03', 'DJAM28');

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `appareils`
--
ALTER TABLE `appareils`
  ADD CONSTRAINT `appareils_ibfk_1` FOREIGN KEY (`id_marque`) REFERENCES `marque` (`id_marque`),
  ADD CONSTRAINT `appareils_ibfk_2` FOREIGN KEY (`id_salle`) REFERENCES `salles` (`id_salle`);

--
-- Contraintes pour la table `interventions`
--
ALTER TABLE `interventions`
  ADD CONSTRAINT `interventions_ibfk_1` FOREIGN KEY (`id_ticket`) REFERENCES `tickets` (`id_ticket`) ON DELETE CASCADE,
  ADD CONSTRAINT `interventions_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`);

--
-- Contraintes pour la table `peripherique`
--
ALTER TABLE `peripherique`
  ADD CONSTRAINT `peripherique_ibfk_1` FOREIGN KEY (`id_marque`) REFERENCES `marque` (`id_marque`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `poste`
--
ALTER TABLE `poste`
  ADD CONSTRAINT `poste_ibfk_1` FOREIGN KEY (`id_salle`) REFERENCES `salles` (`id_salle`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `poste_peripherique`
--
ALTER TABLE `poste_peripherique`
  ADD CONSTRAINT `poste_peripherique_ibfk_1` FOREIGN KEY (`id_poste`) REFERENCES `poste` (`id_poste`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `poste_peripherique_ibfk_2` FOREIGN KEY (`id_peripherique`) REFERENCES `peripherique` (`id_peripherique`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `poste_unite`
--
ALTER TABLE `poste_unite`
  ADD CONSTRAINT `poste_unite_ibfk_1` FOREIGN KEY (`id_poste`) REFERENCES `poste` (`id_poste`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `poste_unite_ibfk_2` FOREIGN KEY (`id_unite`) REFERENCES `unite` (`id_unite`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`),
  ADD CONSTRAINT `tickets_ibfk_2` FOREIGN KEY (`id_salle`) REFERENCES `salles` (`id_salle`),
  ADD CONSTRAINT `tickets_ibfk_3` FOREIGN KEY (`id_poste`) REFERENCES `poste` (`id_poste`),
  ADD CONSTRAINT `tickets_ibfk_4` FOREIGN KEY (`id_appareil`) REFERENCES `appareils` (`id_appareil`),
  ADD CONSTRAINT `tickets_ibfk_5` FOREIGN KEY (`id_peripherique`) REFERENCES `peripherique` (`id_peripherique`),
  ADD CONSTRAINT `tickets_ibfk_6` FOREIGN KEY (`id_unite`) REFERENCES `unite` (`id_unite`);

--
-- Contraintes pour la table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`id_role`) REFERENCES `roles` (`id_role`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

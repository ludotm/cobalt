-- phpMyAdmin SQL Dump
-- version 4.0.4
-- http://www.phpmyadmin.net
--
-- Client: localhost
-- Généré le: Mar 29 Août 2017 à 21:01
-- Version du serveur: 5.6.17
-- Version de PHP: 5.4.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Base de données: `cobalt`
--

-- --------------------------------------------------------

--
-- Structure de la table `_platforms_accounts`
--

CREATE TABLE IF NOT EXISTS `_platforms_accounts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_big_user` int(10) unsigned DEFAULT NULL,
  `platform` varchar(20) NOT NULL,
  `platform_user_id` varchar(255) DEFAULT NULL,
  `platform_username` varchar(45) DEFAULT NULL,
  `access_token` varchar(255) DEFAULT NULL,
  `expire` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `platforms_accounts_unique_idx` (`id_big_user`,`platform`),
  KEY `fk_platforms_accounts_platforms_idx` (`platform`),
  KEY `fk_platforms_accounts_clients_idx` (`id_big_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

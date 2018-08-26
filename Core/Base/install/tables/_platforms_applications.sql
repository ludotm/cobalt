-- phpMyAdmin SQL Dump
-- version 4.0.4
-- http://www.phpmyadmin.net
--
-- Client: localhost
-- Généré le: Mar 29 Août 2017 à 21:02
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
-- Structure de la table `_platforms_applications`
--

CREATE TABLE IF NOT EXISTS `_platforms_applications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_big_user` int(10) unsigned DEFAULT NULL,
  `platform` varchar(20) NOT NULL,
  `api_key` varchar(255) DEFAULT NULL,
  `api_secret` varchar(255) DEFAULT NULL,
  `admin_platform_user_id` varchar(100) NOT NULL DEFAULT '',
  `admin_access_token` varchar(255) NOT NULL DEFAULT '',
  `expire` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fk_platforms_applications_clients_idx` (`id_big_user`),
  KEY `fk_platforms_applications_platforms_idx` (`platform`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

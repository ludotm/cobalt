-- phpMyAdmin SQL Dump
-- version 4.0.4
-- http://www.phpmyadmin.net
--
-- Client: localhost
-- Généré le: Mar 29 Août 2017 à 21:00
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
-- Structure de la table `_images`
--

CREATE TABLE IF NOT EXISTS `_images` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_big_user` int(11) unsigned NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `slug` varchar(255) NOT NULL DEFAULT '',
  `description` varchar(255) NOT NULL DEFAULT '',
  `extension` varchar(5) NOT NULL DEFAULT '',
  `file_type` varchar(20) NOT NULL DEFAULT '',
  `file_size` int(11) unsigned DEFAULT '0',
  `original_width` int(5) unsigned NOT NULL DEFAULT '0',
  `original_height` int(5) unsigned NOT NULL DEFAULT '0',
  `id_user_create` int(11) unsigned NOT NULL,
  `date_create` date NOT NULL DEFAULT '0000-00-00',
  `date_update` date NOT NULL DEFAULT '0000-00-00',
  `deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

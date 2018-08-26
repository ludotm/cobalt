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
-- Structure de la table `_meraki_portals`
--

CREATE TABLE IF NOT EXISTS `_meraki_portals` (
  `id` tinyint(1) NOT NULL AUTO_INCREMENT,
  `id_big_user` int(11) unsigned NOT NULL,
  `logo` int(11) unsigned NOT NULL DEFAULT '0',
  `color_bg` varchar(10) NOT NULL DEFAULT '',
  `color_text` varchar(10) NOT NULL DEFAULT '',
  `color_link` varchar(10) NOT NULL DEFAULT '',
  `color_main` varchar(10) NOT NULL DEFAULT '',
  `headline` varchar(255) NOT NULL DEFAULT '',
  `footerline` varchar(255) NOT NULL DEFAULT '',
  `json_menu` text NOT NULL,
  `contact_address` varchar(255) NOT NULL DEFAULT '',
  `contact_cp` varchar(15) NOT NULL DEFAULT '',
  `contact_town` varchar(75) NOT NULL DEFAULT '',
  `contact_email` varchar(150) NOT NULL,
  `contact_map` tinyint(1) unsigned NOT NULL,
  `show_social` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `active_sharing` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `show_newsletter` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `contact_form` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

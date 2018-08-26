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
-- Structure de la table `_meraki_data_storage`
--

CREATE TABLE IF NOT EXISTS `_meraki_data_storage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_client` int(11) unsigned NOT NULL,
  `id_big_user` int(11) unsigned NOT NULL,
  `ap_mac` int(11) NOT NULL,
  `ap_tags` int(11) NOT NULL,
  `ap_floors` int(11) NOT NULL,
  `client_mac` varchar(20) NOT NULL,
  `is_associated` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `ipv4` varchar(15) NOT NULL,
  `ipv6` varchar(50) NOT NULL,
  `seen_time` varchar(20) NOT NULL,
  `seen_epoch` int(11) NOT NULL,
  `ssid` varchar(50) NOT NULL,
  `rssi` int(11) NOT NULL,
  `manufacturer` varchar(20) NOT NULL,
  `os` varchar(20) NOT NULL,
  `location` varchar(20) NOT NULL,
  `lat` float NOT NULL,
  `lng` float NOT NULL,
  `unc` float NOT NULL,
  `x` float NOT NULL,
  `y` float NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

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
-- Structure de la table `_factures`
--

CREATE TABLE IF NOT EXISTS `_factures` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_big_user` int(11) unsigned NOT NULL,
  `ref` varchar(20) NOT NULL DEFAULT '',
  `id_transaction` varchar(40) NOT NULL DEFAULT '',
  `start_date` date NOT NULL DEFAULT '0000-00-00',
  `end_date` date NOT NULL DEFAULT '0000-00-00',
  `products` text NOT NULL,
  `total_ht` float unsigned NOT NULL DEFAULT '0',
  `total_ttc` float unsigned NOT NULL DEFAULT '0',
  `total_consumables_ttc` float unsigned NOT NULL DEFAULT '0',
  `spothit_cost` float unsigned NOT NULL DEFAULT '0',
  `charged` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `payment_status` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `reason` varchar(30) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

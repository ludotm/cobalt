-- phpMyAdmin SQL Dump
-- version 4.0.4
-- http://www.phpmyadmin.net
--
-- Client: localhost
-- Généré le: Mar 29 Août 2017 à 20:58
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
-- Structure de la table `_big_users`
--

CREATE TABLE IF NOT EXISTS `_big_users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `society` varchar(100) NOT NULL DEFAULT '',
  `type_society` varchar(50) NOT NULL DEFAULT '',
  `rcs` varchar(50) NOT NULL DEFAULT '',
  `rcs_town` varchar(100) NOT NULL DEFAULT '',
  `contact_prename` varchar(100) NOT NULL DEFAULT '',
  `contact_name` varchar(100) NOT NULL,
  `address` varchar(255) NOT NULL,
  `cp` varchar(10) NOT NULL,
  `town` varchar(75) NOT NULL,
  `country` varchar(100) NOT NULL DEFAULT '',
  `email` varchar(100) NOT NULL DEFAULT '',
  `phone` varchar(20) NOT NULL DEFAULT '',
  `mobile` varchar(20) NOT NULL,
  `access_admin` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `date_create` date NOT NULL DEFAULT '0000-00-00',
  `start_trial` date NOT NULL DEFAULT '0000-00-00',
  `end_trial` date NOT NULL DEFAULT '0000-00-00',
  `date_contract` date NOT NULL DEFAULT '0000-00-00',
  `engagement` date NOT NULL DEFAULT '0000-00-00',
  `auto_params` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `alert` date NOT NULL DEFAULT '0000-00-00',
  `alert_comment` text NOT NULL,
  `comment` text NOT NULL,
  `motif` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `stop_contact` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `stop_facturation` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `cgu` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `parrain` varchar(100) NOT NULL DEFAULT '',
  `deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Contenu de la table `_big_users`
--

INSERT INTO `_big_users` (`id`, `name`, `society`, `type_society`, `rcs`, `rcs_town`, `contact_prename`, `contact_name`, `address`, `cp`, `town`, `country`, `email`, `phone`, `mobile`, `access_admin`, `date_create`, `start_trial`, `end_trial`, `date_contract`, `engagement`, `auto_params`, `alert`, `alert_comment`, `comment`, `motif`, `stop_contact`, `stop_facturation`, `cgu`, `parrain`, `deleted`) VALUES
(1, 'Client 1', '', '', '', '', 'client', 'client', '', '', '', '', '', '', '', 1, '0000-00-00', '0000-00-00', '0000-00-00', '0000-00-00', '0000-00-00', 0, '0000-00-00', '', '', 0, 0, 0, 0, '', 0);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

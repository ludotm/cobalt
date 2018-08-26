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
-- Structure de la table `_emails`
--

CREATE TABLE IF NOT EXISTS `_emails` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `campain_title` varchar(255) NOT NULL,
  `id_big_user` int(11) unsigned NOT NULL,
  `id_template` int(11) unsigned NOT NULL DEFAULT '0',
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `id_image` int(11) unsigned NOT NULL DEFAULT '0',
  `attachments` text NOT NULL,
  `from_mail` varchar(50) NOT NULL DEFAULT '',
  `from_name` varchar(50) NOT NULL DEFAULT '',
  `response_mail` varchar(50) NOT NULL DEFAULT '',
  `target` text NOT NULL,
  `send_to` text NOT NULL,
  `cc` text NOT NULL,
  `cci` text NOT NULL,
  `count_mails` int(5) unsigned NOT NULL DEFAULT '0',
  `timestamp` int(11) unsigned NOT NULL DEFAULT '0',
  `provider` varchar(20) NOT NULL DEFAULT '',
  `id_common_provider` int(11) unsigned NOT NULL DEFAULT '0',
  `is_campain` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `id_user_create` int(11) unsigned NOT NULL DEFAULT '0',
  `deleted` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

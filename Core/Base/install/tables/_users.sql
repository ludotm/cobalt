-- phpMyAdmin SQL Dump
-- version 4.0.4
-- http://www.phpmyadmin.net
--
-- Client: localhost
-- Généré le: Mer 18 Octobre 2017 à 14:14
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
-- Structure de la table `_users`
--

CREATE TABLE IF NOT EXISTS `_users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_big_user` int(11) unsigned NOT NULL DEFAULT '0',
  `prename` varchar(100) NOT NULL DEFAULT '',
  `name` varchar(100) NOT NULL,
  `username` varchar(75) NOT NULL,
  `password` varchar(75) NOT NULL,
  `email` varchar(75) NOT NULL,
  `id_rank` int(11) unsigned NOT NULL,
  `data` text NOT NULL,
  `date_create` date NOT NULL DEFAULT '0000-00-00',
  `date_connexion` int(11) unsigned NOT NULL DEFAULT '0',
  `ip` varchar(20) NOT NULL DEFAULT '',
  `connexion_tries_time` int(11) unsigned NOT NULL DEFAULT '0',
  `connexion_tries_count` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

--
-- Contenu de la table `_users`
--

INSERT INTO `_users` (`id`, `id_big_user`, `prename`, `name`, `username`, `password`, `email`, `id_rank`, `data`, `date_create`, `date_connexion`, `ip`, `connexion_tries_time`, `connexion_tries_count`, `active`, `deleted`) VALUES
(1, 0, '', 'Superadmin', 'superadmin', 'f01c1e389951abf79a8c8102494624141c0f3b62b212304524933fed2c8a3b29', 'l.honore@gmx.com', 1, '', '0000-00-00', 1508325225, '127.0.0.1', 1508325225, 0, 1, 0),
(2, 1, '', 'Administrateur', 'admin', '03f3c53936f2b0e6d42c024cd21a06d926aeb272d66f031ec74f4605e305b6dc', '', 2, '', '0000-00-00', 1508325458, '127.0.0.1', 1508325458, 0, 1, 0);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

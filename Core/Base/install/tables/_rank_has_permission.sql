-- phpMyAdmin SQL Dump
-- version 4.0.4
-- http://www.phpmyadmin.net
--
-- Client: localhost
-- Généré le: Mer 18 Octobre 2017 à 14:13
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
-- Structure de la table `_rank_has_permission`
--

CREATE TABLE IF NOT EXISTS `_rank_has_permission` (
  `id_rank` int(11) unsigned NOT NULL,
  `id_big_user` int(11) unsigned NOT NULL,
  `permission` varchar(30) NOT NULL,
  `value` varchar(5) NOT NULL,
  UNIQUE KEY `id_rank` (`id_rank`,`permission`,`id_big_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Contenu de la table `_rank_has_permission`
--

INSERT INTO `_rank_has_permission` (`id_rank`, `id_big_user`, `permission`, `value`) VALUES
(2, 1, 'access_zone', 'YES'),
(2, 1, 'manage_params', 'YES'),
(2, 1, 'manage_permissions', 'YES'),
(2, 1, 'manage_users', 'YES'),
(2, 1, 'manage_variables', 'YES');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

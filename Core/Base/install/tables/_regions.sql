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
-- Structure de la table `_regions`
--

CREATE TABLE IF NOT EXISTS `_regions` (
  `num_region` varchar(2) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`num_region`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Contenu de la table `_regions`
--

INSERT INTO `_regions` (`num_region`, `name`) VALUES
('1', 'Alsace'),
('10', 'Franche Comte'),
('11', 'Haute Normandie'),
('12', 'Ile de France'),
('13', 'Languedoc Roussillon'),
('14', 'Limousin'),
('15', 'Lorraine'),
('16', 'Midi-Pyrénées'),
('17', 'Nord Pas de Calais'),
('18', 'Provence Alpes Côte d''Azur'),
('19', 'Pays de la Loire'),
('2', 'Aquitaine'),
('20', 'Picardie'),
('21', 'Poitou Charente'),
('22', 'Rhone Alpes'),
('3', 'Auvergne'),
('4', 'Basse Normandie'),
('5', 'Bourgogne'),
('6', 'Bretagne'),
('7', 'Centre'),
('8', 'Champagne Ardenne'),
('9', 'Corse');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

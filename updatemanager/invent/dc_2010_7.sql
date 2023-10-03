-- phpMyAdmin SQL Dump
-- version 3.3.2deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jul 24, 2010 at 10:54 AM
-- Server version: 5.1.41
-- PHP Version: 5.3.2-1ubuntu4.2

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `invent`
--

-- --------------------------------------------------------

--
-- Table structure for table `dc_2010_7`
--

CREATE TABLE IF NOT EXISTS `dc_2010_7` (
  `dc_id` int(11) NOT NULL AUTO_INCREMENT,
  `storeroom_id` int(11) NOT NULL DEFAULT '0',
  `client_id` int(11) NOT NULL DEFAULT '0',
  `dc_number` int(11) NOT NULL DEFAULT '0',
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `total_amount` float NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0',
  `is_modified` char(1) NOT NULL DEFAULT 'N',
  PRIMARY KEY (`dc_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

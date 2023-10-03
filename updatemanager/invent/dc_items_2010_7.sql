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
-- Table structure for table `dc_items_2010_7`
--

CREATE TABLE IF NOT EXISTS `dc_items_2010_7` (
  `dc_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `quantity` float NOT NULL DEFAULT '0',
  `discount` smallint(6) NOT NULL DEFAULT '0',
  `price` float NOT NULL DEFAULT '0',
  `product_id` int(11) NOT NULL DEFAULT '0',
  `dc_id` int(11) NOT NULL DEFAULT '0',
  `batch_id` int(11) NOT NULL DEFAULT '0',
  `product_description` varchar(256) NOT NULL,
  PRIMARY KEY (`dc_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

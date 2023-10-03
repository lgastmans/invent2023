

ALTER TABLE `customer` ADD `fs_account` VARCHAR(16) NULL AFTER `same_address`;

-- phpMyAdmin SQL Dump
-- version 4.5.4.1deb2ubuntu2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Dec 13, 2017 at 08:39 AM
-- Server version: 5.7.20-0ubuntu0.16.04.1
-- PHP Version: 7.0.26-2+ubuntu16.04.1+deb.sury.org+2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `wtinvent`
--

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `id` int(11) NOT NULL,
  `customer_id` varchar(15) DEFAULT ' ',
  `company` varchar(60) DEFAULT ' ',
  `address` varchar(80) DEFAULT ' ',
  `address2` varchar(60) DEFAULT ' ',
  `city` varchar(30) DEFAULT ' ',
  `zip` varchar(10) DEFAULT ' ',
  `phone1` varchar(30) DEFAULT ' ',
  `phone2` varchar(30) DEFAULT ' ',
  `fax` varchar(30) DEFAULT '',
  `email` varchar(30) DEFAULT ' ',
  `cell` varchar(30) DEFAULT ' ',
  `contact_person` varchar(40) DEFAULT '',
  `sales_tax_no` varchar(50) DEFAULT ' ',
  `sales_tax_type` varchar(50) DEFAULT ' ',
  `tax` float NOT NULL DEFAULT '0',
  `tax_id` int(11) NOT NULL DEFAULT '0',
  `sur` varchar(50) DEFAULT ' ',
  `surcharge` float DEFAULT '0',
  `delivery_address` varchar(150) DEFAULT ' ',
  `discount` float DEFAULT '0',
  `payment_terms` varchar(90) DEFAULT ' ',
  `payment_type` varchar(255) NOT NULL DEFAULT 'rupees',
  `username` varchar(64) NOT NULL,
  `password` varchar(128) NOT NULL,
  `is_active` char(1) NOT NULL DEFAULT 'Y',
  `is_modified` char(1) NOT NULL DEFAULT 'N',
  `can_view_price` char(1) NOT NULL DEFAULT 'Y',
  `currency_id` int(11) NOT NULL,
  `price_increase` decimal(2,2) NOT NULL,
  `is_other_state` char(1) NOT NULL DEFAULT 'N',
  `state` varchar(50) NOT NULL,
  `state_code` varchar(25) NOT NULL,
  `gstin` varchar(50) NOT NULL,
  `ship_address` varchar(80) DEFAULT NULL,
  `ship_address2` varchar(60) DEFAULT NULL,
  `ship_city` varchar(30) DEFAULT NULL,
  `ship_zip` varchar(10) DEFAULT NULL,
  `ship_company` varchar(60) DEFAULT NULL,
  `ship_state` varchar(50) DEFAULT NULL,
  `ship_state_code` varchar(25) DEFAULT NULL,
  `ship_gstin` varchar(50) DEFAULT NULL,
  `same_address` char(1) NOT NULL DEFAULT 'Y'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

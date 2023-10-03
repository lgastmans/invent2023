-- phpMyAdmin SQL Dump
-- version 4.5.4.1deb2ubuntu2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jan 26, 2018 at 07:11 PM
-- Server version: 5.7.21-0ubuntu0.16.04.1
-- PHP Version: 7.0.27-1+ubuntu16.04.1+deb.sury.org+1

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
-- Table structure for table `stock_supplier`
--

CREATE TABLE `stock_supplier` (
  `supplier_id` int(11) NOT NULL,
  `supplier_code` varchar(16) NOT NULL DEFAULT '',
  `supplier_name` varchar(128) NOT NULL DEFAULT '',
  `contact_person` varchar(128) NOT NULL DEFAULT '',
  `supplier_address` varchar(128) NOT NULL DEFAULT '',
  `supplier_city` varchar(64) NOT NULL DEFAULT '',
  `supplier_state` varchar(20) NOT NULL DEFAULT '',
  `supplier_phone` varchar(12) NOT NULL DEFAULT '',
  `supplier_cell` varchar(12) NOT NULL DEFAULT '',
  `is_supplier_delivering` char(1) NOT NULL DEFAULT 'Y',
  `commission_percent` float NOT NULL DEFAULT '0',
  `commission_percent_2` float NOT NULL DEFAULT '0',
  `commission_percent_3` float NOT NULL DEFAULT '0',
  `supplier_type` char(1) NOT NULL DEFAULT '',
  `supplier_abbreviation` varchar(8) NOT NULL DEFAULT '',
  `supplier_zip` varchar(6) NOT NULL DEFAULT '',
  `supplier_email` varchar(128) NOT NULL DEFAULT '',
  `supplier_discount` float NOT NULL DEFAULT '0',
  `trust` varchar(50) NOT NULL DEFAULT ' ',
  `supplier_TIN` varchar(30) NOT NULL DEFAULT ' ',
  `supplier_CST` varchar(30) NOT NULL DEFAULT ' ',
  `is_active` char(1) NOT NULL DEFAULT 'Y',
  `is_other_state` char(1) NOT NULL DEFAULT 'N',
  `account_number` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `stock_supplier`
--
ALTER TABLE `stock_supplier`
  ADD PRIMARY KEY (`supplier_id`),
  ADD KEY `supplier_code` (`supplier_code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `stock_supplier`
--
ALTER TABLE `stock_supplier`
  MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

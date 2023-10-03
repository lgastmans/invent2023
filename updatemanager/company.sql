

ALTER TABLE `company` ADD `email_password` VARCHAR(256) NULL DEFAULT NULL AFTER `email`;



-- phpMyAdmin SQL Dump
-- version 4.5.4.1deb2ubuntu2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Dec 13, 2017 at 10:02 PM
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
-- Table structure for table `company`
--

CREATE TABLE `company` (
  `id` int(11) NOT NULL,
  `title` varchar(128) DEFAULT NULL,
  `legal_name` varchar(128) DEFAULT NULL,
  `trade_name` varchar(128) NOT NULL,
  `trust` varchar(128) NOT NULL,
  `gstin` varchar(32) NOT NULL,
  `address` varchar(128) NOT NULL,
  `phone` varchar(32) NOT NULL,
  `email` varchar(64) NOT NULL,
  `footer` varchar(512) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `company`
--
ALTER TABLE `company`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `company`
--
ALTER TABLE `company`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

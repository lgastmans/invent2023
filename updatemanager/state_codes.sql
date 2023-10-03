-- phpMyAdmin SQL Dump
-- version 4.5.4.1deb2ubuntu2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Dec 11, 2017 at 09:19 PM
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
-- Table structure for table `state_codes`
--

CREATE TABLE `state_codes` (
  `id` int(11) NOT NULL,
  `state` varchar(32) NOT NULL,
  `code` int(11) NOT NULL,
  `abbr` varchar(2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `state_codes`
--

INSERT INTO `state_codes` (`id`, `state`, `code`, `abbr`) VALUES
(1, 'Andaman and Nicobar Islands', 35, 'AN'),
(2, 'Andhra Pradesh', 28, 'AP'),
(3, 'Andhra Pradesh (New)', 37, 'AD'),
(4, 'Arunachal Pradesh', 12, 'AR'),
(5, 'Assam', 18, 'AS'),
(6, 'Bihar', 10, 'BH'),
(7, 'Chandigarh', 4, 'CH'),
(8, 'Chattisgarh', 22, 'CT'),
(9, 'Dadra and Nagar Haveli', 26, 'DN'),
(10, 'Daman and Diu', 25, 'DD'),
(11, 'Delhi', 7, 'DL'),
(12, 'Goa', 30, 'GA'),
(13, 'Gujarat', 24, 'GJ'),
(14, 'Haryana', 6, 'HR'),
(15, 'Himachal Pradesh', 2, 'HP'),
(16, 'Jammu and Kashmir', 1, 'JK'),
(17, 'Jharkhand', 20, 'JH'),
(18, 'Karnataka', 29, 'KA'),
(19, 'Kerala', 32, 'KL'),
(20, 'Lakshadweep Islands', 31, 'LD'),
(21, 'Madhya Pradesh', 23, 'MP'),
(22, 'Maharashtra', 27, 'MH'),
(23, 'Manipur', 14, 'MN'),
(24, 'Meghalaya', 17, 'ME'),
(25, 'Mizoram', 15, 'MI'),
(26, 'Nagaland', 13, 'NL'),
(27, 'Odisha', 21, 'OR'),
(28, 'Pondicherry', 34, 'PY'),
(29, 'Punjab', 3, 'PB'),
(30, 'Rajasthan', 8, 'RJ'),
(31, 'Sikkim', 11, 'SK'),
(32, 'Tamil Nadu', 33, 'TN'),
(33, 'Telangana', 36, 'TS'),
(34, 'Tripura', 16, 'TR'),
(35, 'Uttar Pradesh', 9, 'UP'),
(36, 'Uttarakhand', 5, 'UT'),
(37, 'West Bengal', 19, 'WB');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `state_codes`
--
ALTER TABLE `state_codes`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `state_codes`
--
ALTER TABLE `state_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 07, 2020 at 03:46 AM
-- Server version: 10.1.38-MariaDB
-- PHP Version: 5.6.40

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `byropharmacorp`
--

-- --------------------------------------------------------

--
-- Table structure for table `item_serialise`
--

DROP TABLE IF EXISTS `item_serialise`;
CREATE TABLE `item_serialise` (
  `serialise_id` int(11) NOT NULL,
  `serialise_item_code` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `serialise_reference` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
  `serialise_expire_date` date NOT NULL DEFAULT '0000-00-00',
  `serialise_lot_no` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
  `serialise_manufacture_date` date NOT NULL DEFAULT '0000-00-00',
  `serialise_qty` int(11) NOT NULL DEFAULT '0',
  `serialise_grn_items_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `item_serialise`
--
ALTER TABLE `item_serialise`
  ADD PRIMARY KEY (`serialise_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `item_serialise`
--
ALTER TABLE `item_serialise`
  MODIFY `serialise_id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

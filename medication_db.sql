-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Nov 20, 2026 at 08:21 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `medication_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `medication`
--

CREATE TABLE `medication` (
  `medication_id` int(11) NOT NULL,
  `name` varchar(60) NOT NULL,
  `strength` varchar(20) NOT NULL,
  `form` enum('tablet','capsule','liquid','patch','inhaler','injection') NOT NULL,
  `route` enum('oral','topical','inhaled','subcutaneous','sublingual') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `medication`
--

INSERT INTO `medication` (`medication_id`, `name`, `strength`, `form`, `route`) VALUES
(1, 'Paracetamol', '500 mg', 'tablet', 'oral'),
(2, 'Metformin', '500 mg', 'tablet', 'oral'),
(3, 'Atorvastatin', '20 mg', 'tablet', 'oral'),
(4, 'Perindopril', '5 mg', 'tablet', 'oral'),
(5, 'Amlodipine', '5 mg', 'tablet', 'oral'),
(6, 'Aspirin', '100 mg', 'tablet', 'oral'),
(7, 'Frusemide', '40 mg', 'tablet', 'oral'),
(8, 'Metoprolol', '50 mg', 'tablet', 'oral'),
(9, 'Omeprazole', '20 mg', 'capsule', 'oral'),
(10, 'Salbutamol', '100 mcg', 'inhaler', 'inhaled'),
(11, 'Fentanyl', '12 mcg/hr', 'patch', 'topical'),
(12, 'Glyceryl trinitrate', '0.4 mg', 'tablet', 'sublingual'),
(13, 'Lactulose', '3.35 g/5 mL', 'liquid', 'oral'),
(14, 'Enoxaparin', '40 mg', 'injection', 'subcutaneous'),
(15, 'Levothyroxine', '100 mcg', 'tablet', 'oral');

-- --------------------------------------------------------

--
-- Table structure for table `medication_administration`
--

CREATE TABLE `medication_administration` (
  `admin_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `scheduled_date` date NOT NULL,
  `status` enum('pending','given','refused','fasting','no stock','ceased') NOT NULL DEFAULT 'pending',
  `practitioner_id` int(11) DEFAULT NULL,
  `administered_at` datetime DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `medication_administration`
--

INSERT INTO `medication_administration` (`admin_id`, `order_id`, `scheduled_date`, `status`, `practitioner_id`, `administered_at`, `notes`) VALUES
(1, 1, '2026-11-23', 'given', 2, '2026-11-23 08:08:00', NULL),
(2, 1, '2026-11-24', 'given', 3, '2026-11-24 08:11:00', NULL),
(3, 1, '2026-11-25', 'given', 4, '2026-11-25 08:14:00', NULL),
(4, 1, '2026-11-26', 'given', 1, '2026-11-26 08:17:00', NULL),
(5, 1, '2026-11-27', 'given', 2, '2026-11-27 08:20:00', NULL),
(6, 1, '2026-11-28', 'given', 3, '2026-11-28 08:23:00', NULL),
(7, 1, '2026-11-29', 'given', 4, '2026-11-29 08:26:00', NULL),
(8, 2, '2026-11-23', 'given', 3, '2026-11-23 18:15:00', NULL),
(9, 2, '2026-11-24', 'given', 4, '2026-11-24 18:18:00', NULL),
(10, 2, '2026-11-25', 'refused', 1, '2026-11-25 18:21:00', 'Patient declined the dose'),
(11, 2, '2026-11-26', 'given', 2, '2026-11-26 18:24:00', NULL),
(12, 2, '2026-11-27', 'given', 3, '2026-11-27 18:27:00', NULL),
(13, 2, '2026-11-28', 'given', 4, '2026-11-28 18:30:00', NULL),
(14, 2, '2026-11-29', 'given', 1, '2026-11-29 18:33:00', NULL),
(15, 3, '2026-11-23', 'given', 4, '2026-11-23 08:22:00', NULL),
(16, 3, '2026-11-24', 'given', 1, '2026-11-24 08:25:00', NULL),
(17, 3, '2026-11-25', 'given', 2, '2026-11-25 08:28:00', NULL),
(18, 3, '2026-11-26', 'given', 3, '2026-11-26 08:31:00', NULL),
(19, 3, '2026-11-27', 'given', 4, '2026-11-27 08:34:00', NULL),
(20, 3, '2026-11-28', 'given', 1, '2026-11-28 08:37:00', NULL),
(21, 3, '2026-11-29', 'given', 2, '2026-11-29 08:40:00', NULL),
(22, 4, '2026-11-23', 'given', 1, '2026-11-23 08:29:00', NULL),
(23, 4, '2026-11-24', 'given', 2, '2026-11-24 08:32:00', NULL),
(24, 4, '2026-11-25', 'given', 3, '2026-11-25 08:35:00', NULL),
(25, 4, '2026-11-26', 'given', 4, '2026-11-26 08:38:00', NULL),
(26, 4, '2026-11-27', 'given', 1, '2026-11-27 08:41:00', NULL),
(27, 4, '2026-11-28', 'given', 2, '2026-11-28 08:44:00', NULL),
(28, 4, '2026-11-29', 'given', 3, '2026-11-29 08:02:00', NULL),
(29, 5, '2026-11-23', 'given', 2, '2026-11-23 13:36:00', NULL),
(30, 5, '2026-11-24', 'refused', 3, '2026-11-24 13:39:00', 'Patient declined the dose'),
(31, 5, '2026-11-25', 'given', 4, '2026-11-25 13:42:00', NULL),
(32, 5, '2026-11-26', 'given', 1, '2026-11-26 13:45:00', NULL),
(33, 5, '2026-11-27', 'given', 2, '2026-11-27 13:03:00', NULL),
(34, 5, '2026-11-28', 'given', 3, '2026-11-28 13:06:00', NULL),
(35, 5, '2026-11-29', 'given', 4, '2026-11-29 13:09:00', NULL),
(36, 6, '2026-11-23', 'given', 3, '2026-11-23 08:43:00', NULL),
(37, 6, '2026-11-24', 'given', 4, '2026-11-24 08:01:00', NULL),
(38, 6, '2026-11-25', 'given', 1, '2026-11-25 08:04:00', NULL),
(39, 6, '2026-11-26', 'given', 2, '2026-11-26 08:07:00', NULL),
(40, 6, '2026-11-27', 'given', 3, '2026-11-27 08:10:00', NULL),
(41, 6, '2026-11-28', 'given', 4, '2026-11-28 08:13:00', NULL),
(42, 6, '2026-11-29', 'given', 1, '2026-11-29 08:16:00', NULL),
(43, 7, '2026-11-23', 'given', 4, '2026-11-23 18:05:00', NULL),
(44, 7, '2026-11-24', 'given', 1, '2026-11-24 18:08:00', NULL),
(45, 7, '2026-11-25', 'given', 2, '2026-11-25 18:11:00', NULL),
(46, 7, '2026-11-26', 'given', 3, '2026-11-26 18:14:00', NULL),
(47, 7, '2026-11-27', 'given', 4, '2026-11-27 18:17:00', NULL),
(48, 7, '2026-11-28', 'given', 1, '2026-11-28 18:20:00', NULL),
(49, 7, '2026-11-29', 'given', 2, '2026-11-29 18:23:00', NULL),
(50, 8, '2026-11-23', 'given', 1, '2026-11-23 08:12:00', NULL),
(51, 8, '2026-11-24', 'given', 2, '2026-11-24 08:15:00', NULL),
(52, 8, '2026-11-25', 'given', 3, '2026-11-25 08:18:00', NULL),
(53, 8, '2026-11-26', 'given', 4, '2026-11-26 08:21:00', NULL),
(54, 8, '2026-11-27', 'given', 1, '2026-11-27 08:24:00', NULL),
(55, 8, '2026-11-28', 'fasting', 2, '2026-11-28 08:27:00', 'Patient fasting before a procedure'),
(56, 8, '2026-11-29', 'given', 3, '2026-11-29 08:30:00', NULL),
(57, 9, '2026-11-23', 'given', 2, '2026-11-23 13:19:00', NULL),
(58, 9, '2026-11-24', 'given', 3, '2026-11-24 13:22:00', NULL),
(59, 9, '2026-11-25', 'given', 4, '2026-11-25 13:25:00', NULL),
(60, 9, '2026-11-26', 'given', 1, '2026-11-26 13:28:00', NULL),
(61, 9, '2026-11-27', 'given', 2, '2026-11-27 13:31:00', NULL),
(62, 9, '2026-11-28', 'given', 3, '2026-11-28 13:34:00', NULL),
(63, 9, '2026-11-29', 'given', 4, '2026-11-29 13:37:00', NULL),
(64, 10, '2026-11-23', 'given', 3, '2026-11-23 08:26:00', NULL),
(65, 10, '2026-11-24', 'no stock', 4, '2026-11-24 08:29:00', 'Awaiting delivery from pharmacy'),
(66, 10, '2026-11-25', 'given', 1, '2026-11-25 08:32:00', NULL),
(67, 10, '2026-11-26', 'given', 2, '2026-11-26 08:35:00', NULL),
(68, 10, '2026-11-27', 'given', 3, '2026-11-27 08:38:00', NULL),
(69, 10, '2026-11-28', 'given', 4, '2026-11-28 08:41:00', NULL),
(70, 10, '2026-11-29', 'given', 1, '2026-11-29 08:44:00', NULL),
(71, 11, '2026-11-23', 'given', 4, '2026-11-23 18:33:00', NULL),
(72, 11, '2026-11-24', 'given', 1, '2026-11-24 18:36:00', NULL),
(73, 11, '2026-11-25', 'given', 2, '2026-11-25 18:39:00', NULL),
(74, 11, '2026-11-26', 'given', 3, '2026-11-26 18:42:00', NULL),
(75, 11, '2026-11-27', 'given', 4, '2026-11-27 18:45:00', NULL),
(76, 11, '2026-11-28', 'given', 1, '2026-11-28 18:03:00', NULL),
(77, 11, '2026-11-29', 'ceased', 2, '2026-11-29 18:06:00', 'Ceased by prescriber'),
(78, 12, '2026-11-23', 'given', 1, '2026-11-23 18:40:00', NULL),
(79, 12, '2026-11-24', 'given', 2, '2026-11-24 18:43:00', NULL),
(80, 12, '2026-11-25', 'given', 3, '2026-11-25 18:01:00', NULL),
(81, 12, '2026-11-26', 'given', 4, '2026-11-26 18:04:00', NULL),
(82, 12, '2026-11-27', 'refused', 1, '2026-11-27 18:07:00', 'Patient declined the dose'),
(83, 12, '2026-11-28', 'given', 2, '2026-11-28 18:10:00', NULL),
(84, 12, '2026-11-29', 'given', 3, '2026-11-29 18:13:00', NULL),
(85, 13, '2026-11-23', 'given', 2, '2026-11-23 08:02:00', NULL),
(86, 13, '2026-11-24', 'given', 3, '2026-11-24 08:05:00', NULL),
(87, 13, '2026-11-25', 'given', 4, '2026-11-25 08:08:00', NULL),
(88, 13, '2026-11-26', 'fasting', 1, '2026-11-26 08:11:00', 'Patient fasting before a procedure'),
(89, 13, '2026-11-27', 'given', 2, '2026-11-27 08:14:00', NULL),
(90, 13, '2026-11-28', 'given', 3, '2026-11-28 08:17:00', NULL),
(91, 13, '2026-11-29', 'given', 4, '2026-11-29 08:20:00', NULL),
(92, 14, '2026-11-23', 'given', 3, '2026-11-23 08:09:00', NULL),
(93, 14, '2026-11-24', 'given', 4, '2026-11-24 08:12:00', NULL),
(94, 14, '2026-11-25', 'given', 1, '2026-11-25 08:15:00', NULL),
(95, 14, '2026-11-26', 'given', 2, '2026-11-26 08:18:00', NULL),
(96, 14, '2026-11-27', 'given', 3, '2026-11-27 08:21:00', NULL),
(97, 14, '2026-11-28', 'given', 4, '2026-11-28 08:24:00', NULL),
(98, 14, '2026-11-29', 'given', 1, '2026-11-29 08:27:00', NULL),
(99, 15, '2026-11-23', 'given', 4, '2026-11-23 13:16:00', NULL),
(100, 15, '2026-11-24', 'given', 1, '2026-11-24 13:19:00', NULL),
(101, 15, '2026-11-25', 'given', 2, '2026-11-25 13:22:00', NULL),
(102, 15, '2026-11-26', 'given', 3, '2026-11-26 13:25:00', NULL),
(103, 15, '2026-11-27', 'no stock', 4, '2026-11-27 13:28:00', 'Awaiting delivery from pharmacy'),
(104, 15, '2026-11-28', 'given', 1, '2026-11-28 13:31:00', NULL),
(105, 15, '2026-11-29', 'given', 2, '2026-11-29 13:34:00', NULL),
(106, 16, '2026-11-23', 'given', 1, '2026-11-23 13:23:00', NULL),
(107, 16, '2026-11-24', 'given', 2, '2026-11-24 13:26:00', NULL),
(108, 16, '2026-11-25', 'given', 3, '2026-11-25 13:29:00', NULL),
(109, 16, '2026-11-26', 'refused', 4, '2026-11-26 13:32:00', 'Patient declined the dose'),
(110, 16, '2026-11-27', 'given', 1, '2026-11-27 13:35:00', NULL),
(111, 16, '2026-11-28', 'given', 2, '2026-11-28 13:38:00', NULL),
(112, 16, '2026-11-29', 'given', 3, '2026-11-29 13:41:00', NULL),
(113, 17, '2026-11-23', 'given', 2, '2026-11-23 18:30:00', NULL),
(114, 17, '2026-11-24', 'given', 3, '2026-11-24 18:33:00', NULL),
(115, 17, '2026-11-25', 'given', 4, '2026-11-25 18:36:00', NULL),
(116, 17, '2026-11-26', 'given', 1, '2026-11-26 18:39:00', NULL),
(117, 17, '2026-11-27', 'given', 2, '2026-11-27 18:42:00', NULL),
(118, 17, '2026-11-28', 'given', 3, '2026-11-28 18:45:00', NULL),
(119, 17, '2026-11-29', 'given', 4, '2026-11-29 18:03:00', NULL),
(120, 18, '2026-11-23', 'given', 3, '2026-11-23 08:37:00', NULL),
(121, 18, '2026-11-24', 'given', 4, '2026-11-24 08:40:00', NULL),
(122, 18, '2026-11-25', 'given', 1, '2026-11-25 08:43:00', NULL),
(123, 18, '2026-11-26', 'given', 2, '2026-11-26 08:01:00', NULL),
(124, 18, '2026-11-27', 'given', 3, '2026-11-27 08:04:00', NULL),
(125, 18, '2026-11-28', 'given', 4, '2026-11-28 08:07:00', NULL),
(126, 18, '2026-11-29', 'given', 1, '2026-11-29 08:10:00', NULL),
(127, 19, '2026-11-23', 'given', 4, '2026-11-23 18:44:00', NULL),
(128, 19, '2026-11-24', 'given', 1, '2026-11-24 18:02:00', NULL),
(129, 19, '2026-11-25', 'given', 2, '2026-11-25 18:05:00', NULL),
(130, 19, '2026-11-26', 'given', 3, '2026-11-26 18:08:00', NULL),
(131, 19, '2026-11-27', 'given', 4, '2026-11-27 18:11:00', NULL),
(132, 19, '2026-11-28', 'given', 1, '2026-11-28 18:14:00', NULL),
(133, 19, '2026-11-29', 'given', 2, '2026-11-29 18:17:00', NULL),
(134, 1, '2026-11-30', 'pending', NULL, NULL, NULL),
(135, 1, '2026-12-01', 'pending', NULL, NULL, NULL),
(136, 1, '2026-12-02', 'pending', NULL, NULL, NULL),
(137, 1, '2026-12-03', 'pending', NULL, NULL, NULL),
(138, 1, '2026-12-04', 'pending', NULL, NULL, NULL),
(139, 1, '2026-12-05', 'pending', NULL, NULL, NULL),
(140, 1, '2026-12-06', 'pending', NULL, NULL, NULL),
(141, 2, '2026-11-30', 'pending', NULL, NULL, NULL),
(142, 2, '2026-12-01', 'pending', NULL, NULL, NULL),
(143, 2, '2026-12-02', 'pending', NULL, NULL, NULL),
(144, 2, '2026-12-03', 'pending', NULL, NULL, NULL),
(145, 2, '2026-12-04', 'pending', NULL, NULL, NULL),
(146, 2, '2026-12-05', 'pending', NULL, NULL, NULL),
(147, 2, '2026-12-06', 'pending', NULL, NULL, NULL),
(148, 3, '2026-11-30', 'pending', NULL, NULL, NULL),
(149, 3, '2026-12-01', 'pending', NULL, NULL, NULL),
(150, 3, '2026-12-02', 'pending', NULL, NULL, NULL),
(151, 3, '2026-12-03', 'pending', NULL, NULL, NULL),
(152, 3, '2026-12-04', 'pending', NULL, NULL, NULL),
(153, 3, '2026-12-05', 'pending', NULL, NULL, NULL),
(154, 3, '2026-12-06', 'pending', NULL, NULL, NULL),
(155, 4, '2026-11-30', 'pending', NULL, NULL, NULL),
(156, 4, '2026-12-01', 'pending', NULL, NULL, NULL),
(157, 4, '2026-12-02', 'pending', NULL, NULL, NULL),
(158, 4, '2026-12-03', 'pending', NULL, NULL, NULL),
(159, 4, '2026-12-04', 'pending', NULL, NULL, NULL),
(160, 4, '2026-12-05', 'pending', NULL, NULL, NULL),
(161, 4, '2026-12-06', 'pending', NULL, NULL, NULL),
(162, 5, '2026-11-30', 'pending', NULL, NULL, NULL),
(163, 5, '2026-12-01', 'pending', NULL, NULL, NULL),
(164, 5, '2026-12-02', 'pending', NULL, NULL, NULL),
(165, 5, '2026-12-03', 'pending', NULL, NULL, NULL),
(166, 5, '2026-12-04', 'pending', NULL, NULL, NULL),
(167, 5, '2026-12-05', 'pending', NULL, NULL, NULL),
(168, 5, '2026-12-06', 'pending', NULL, NULL, NULL),
(169, 6, '2026-11-30', 'pending', NULL, NULL, NULL),
(170, 6, '2026-12-01', 'pending', NULL, NULL, NULL),
(171, 6, '2026-12-02', 'pending', NULL, NULL, NULL),
(172, 6, '2026-12-03', 'pending', NULL, NULL, NULL),
(173, 6, '2026-12-04', 'pending', NULL, NULL, NULL),
(174, 6, '2026-12-05', 'pending', NULL, NULL, NULL),
(175, 6, '2026-12-06', 'pending', NULL, NULL, NULL),
(176, 7, '2026-11-30', 'pending', NULL, NULL, NULL),
(177, 7, '2026-12-01', 'pending', NULL, NULL, NULL),
(178, 7, '2026-12-02', 'pending', NULL, NULL, NULL),
(179, 7, '2026-12-03', 'pending', NULL, NULL, NULL),
(180, 7, '2026-12-04', 'pending', NULL, NULL, NULL),
(181, 7, '2026-12-05', 'pending', NULL, NULL, NULL),
(182, 7, '2026-12-06', 'pending', NULL, NULL, NULL),
(183, 8, '2026-11-30', 'pending', NULL, NULL, NULL),
(184, 8, '2026-12-01', 'pending', NULL, NULL, NULL),
(185, 8, '2026-12-02', 'pending', NULL, NULL, NULL),
(186, 8, '2026-12-03', 'pending', NULL, NULL, NULL),
(187, 8, '2026-12-04', 'pending', NULL, NULL, NULL),
(188, 8, '2026-12-05', 'pending', NULL, NULL, NULL),
(189, 8, '2026-12-06', 'pending', NULL, NULL, NULL),
(190, 9, '2026-11-30', 'pending', NULL, NULL, NULL),
(191, 9, '2026-12-01', 'pending', NULL, NULL, NULL),
(192, 9, '2026-12-02', 'pending', NULL, NULL, NULL),
(193, 9, '2026-12-03', 'pending', NULL, NULL, NULL),
(194, 9, '2026-12-04', 'pending', NULL, NULL, NULL),
(195, 9, '2026-12-05', 'pending', NULL, NULL, NULL),
(196, 9, '2026-12-06', 'pending', NULL, NULL, NULL),
(197, 10, '2026-11-30', 'pending', NULL, NULL, NULL),
(198, 10, '2026-12-01', 'pending', NULL, NULL, NULL),
(199, 10, '2026-12-02', 'pending', NULL, NULL, NULL),
(200, 10, '2026-12-03', 'pending', NULL, NULL, NULL),
(201, 10, '2026-12-04', 'pending', NULL, NULL, NULL),
(202, 10, '2026-12-05', 'pending', NULL, NULL, NULL),
(203, 10, '2026-12-06', 'pending', NULL, NULL, NULL),
(204, 11, '2026-11-30', 'ceased', NULL, NULL, 'Ceased by prescriber'),
(205, 11, '2026-12-01', 'ceased', NULL, NULL, 'Ceased by prescriber'),
(206, 11, '2026-12-02', 'ceased', NULL, NULL, 'Ceased by prescriber'),
(207, 11, '2026-12-03', 'ceased', NULL, NULL, 'Ceased by prescriber'),
(208, 11, '2026-12-04', 'ceased', NULL, NULL, 'Ceased by prescriber'),
(209, 11, '2026-12-05', 'ceased', NULL, NULL, 'Ceased by prescriber'),
(210, 11, '2026-12-06', 'ceased', NULL, NULL, 'Ceased by prescriber'),
(211, 12, '2026-11-30', 'pending', NULL, NULL, NULL),
(212, 12, '2026-12-01', 'pending', NULL, NULL, NULL),
(213, 12, '2026-12-02', 'pending', NULL, NULL, NULL),
(214, 12, '2026-12-03', 'pending', NULL, NULL, NULL),
(215, 12, '2026-12-04', 'pending', NULL, NULL, NULL),
(216, 12, '2026-12-05', 'pending', NULL, NULL, NULL),
(217, 12, '2026-12-06', 'pending', NULL, NULL, NULL),
(218, 13, '2026-11-30', 'pending', NULL, NULL, NULL),
(219, 13, '2026-12-01', 'pending', NULL, NULL, NULL),
(220, 13, '2026-12-02', 'pending', NULL, NULL, NULL),
(221, 13, '2026-12-03', 'pending', NULL, NULL, NULL),
(222, 13, '2026-12-04', 'pending', NULL, NULL, NULL),
(223, 13, '2026-12-05', 'pending', NULL, NULL, NULL),
(224, 13, '2026-12-06', 'pending', NULL, NULL, NULL),
(225, 14, '2026-11-30', 'pending', NULL, NULL, NULL),
(226, 14, '2026-12-01', 'pending', NULL, NULL, NULL),
(227, 14, '2026-12-02', 'pending', NULL, NULL, NULL),
(228, 14, '2026-12-03', 'pending', NULL, NULL, NULL),
(229, 14, '2026-12-04', 'pending', NULL, NULL, NULL),
(230, 14, '2026-12-05', 'pending', NULL, NULL, NULL),
(231, 14, '2026-12-06', 'pending', NULL, NULL, NULL),
(232, 15, '2026-11-30', 'pending', NULL, NULL, NULL),
(233, 15, '2026-12-01', 'pending', NULL, NULL, NULL),
(234, 15, '2026-12-02', 'pending', NULL, NULL, NULL),
(235, 15, '2026-12-03', 'pending', NULL, NULL, NULL),
(236, 15, '2026-12-04', 'pending', NULL, NULL, NULL),
(237, 15, '2026-12-05', 'pending', NULL, NULL, NULL),
(238, 15, '2026-12-06', 'pending', NULL, NULL, NULL),
(239, 16, '2026-11-30', 'pending', NULL, NULL, NULL),
(240, 16, '2026-12-01', 'pending', NULL, NULL, NULL),
(241, 16, '2026-12-02', 'pending', NULL, NULL, NULL),
(242, 16, '2026-12-03', 'pending', NULL, NULL, NULL),
(243, 16, '2026-12-04', 'pending', NULL, NULL, NULL),
(244, 16, '2026-12-05', 'pending', NULL, NULL, NULL),
(245, 16, '2026-12-06', 'pending', NULL, NULL, NULL),
(246, 17, '2026-11-30', 'pending', NULL, NULL, NULL),
(247, 17, '2026-12-01', 'pending', NULL, NULL, NULL),
(248, 17, '2026-12-02', 'pending', NULL, NULL, NULL),
(249, 17, '2026-12-03', 'pending', NULL, NULL, NULL),
(250, 17, '2026-12-04', 'pending', NULL, NULL, NULL),
(251, 17, '2026-12-05', 'pending', NULL, NULL, NULL),
(252, 17, '2026-12-06', 'pending', NULL, NULL, NULL),
(253, 18, '2026-11-30', 'pending', NULL, NULL, NULL),
(254, 18, '2026-12-01', 'pending', NULL, NULL, NULL),
(255, 18, '2026-12-02', 'pending', NULL, NULL, NULL),
(256, 18, '2026-12-03', 'pending', NULL, NULL, NULL),
(257, 18, '2026-12-04', 'pending', NULL, NULL, NULL),
(258, 18, '2026-12-05', 'pending', NULL, NULL, NULL),
(259, 18, '2026-12-06', 'pending', NULL, NULL, NULL),
(260, 19, '2026-11-30', 'pending', NULL, NULL, NULL),
(261, 19, '2026-12-01', 'pending', NULL, NULL, NULL),
(262, 19, '2026-12-02', 'pending', NULL, NULL, NULL),
(263, 19, '2026-12-03', 'pending', NULL, NULL, NULL),
(264, 19, '2026-12-04', 'pending', NULL, NULL, NULL),
(265, 19, '2026-12-05', 'pending', NULL, NULL, NULL),
(266, 19, '2026-12-06', 'pending', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `medication_order`
--

CREATE TABLE `medication_order` (
  `order_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `medication_id` int(11) NOT NULL,
  `dose` varchar(30) NOT NULL,
  `round` enum('morning','afternoon','evening') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `medication_order`
--

INSERT INTO `medication_order` (`order_id`, `patient_id`, `medication_id`, `dose`, `round`) VALUES
(1, 1, 2, '1 tablet', 'morning'),
(2, 1, 3, '1 tablet', 'evening'),
(3, 2, 4, '1 tablet', 'morning'),
(4, 2, 6, '1 tablet', 'morning'),
(5, 3, 1, '2 tablets', 'afternoon'),
(6, 4, 7, '1 tablet', 'morning'),
(7, 4, 8, '1 tablet', 'evening'),
(8, 5, 9, '1 capsule', 'morning'),
(9, 6, 10, '2 puffs', 'afternoon'),
(10, 7, 11, '1 patch', 'morning'),
(11, 8, 5, '1 tablet', 'evening'),
(12, 9, 13, '15 mL', 'evening'),
(13, 10, 14, '40 mg', 'morning'),
(14, 11, 15, '1 tablet', 'morning'),
(15, 12, 2, '1 tablet', 'afternoon'),
(16, 13, 12, '1 tablet', 'afternoon'),
(17, 14, 3, '1 tablet', 'evening'),
(18, 15, 1, '2 tablets', 'morning'),
(19, 15, 4, '1 tablet', 'evening');

-- --------------------------------------------------------

--
-- Table structure for table `patient`
--

CREATE TABLE `patient` (
  `patient_id` int(11) NOT NULL,
  `first_name` varchar(40) NOT NULL,
  `last_name` varchar(40) NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` enum('F','M','Other') NOT NULL,
  `room` varchar(10) NOT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `admission_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `patient`
--

INSERT INTO `patient` (`patient_id`, `first_name`, `last_name`, `date_of_birth`, `gender`, `room`, `photo_path`, `admission_date`) VALUES
(1, 'Margaret', 'Thompson', '1938-03-14', 'F', 'A01', 'photos/p01.jpg', '2023-02-10'),
(2, 'Harold', 'Bennett', '1934-07-02', 'M', 'A02', 'photos/p02.jpg', '2022-09-05'),
(3, 'Dorothy', 'Nguyen', '1941-11-23', 'F', 'A03', 'photos/p03.jpg', '2024-01-18'),
(4, 'George', 'Alexander', '1936-01-30', 'M', 'A04', 'photos/p04.jpg', '2021-06-22'),
(5, 'Evelyn', 'Marsh', '1939-05-19', 'F', 'A05', 'photos/p05.jpg', '2023-11-03'),
(6, 'Frank', 'Delaney', '1933-09-08', 'M', 'B01', 'photos/p06.jpg', '2020-03-27'),
(7, 'Joan', 'Kowalski', '1942-12-01', 'F', 'B02', 'photos/p07.jpg', '2024-05-09'),
(8, 'Walter', 'Hughes', '1937-04-17', 'M', 'B03', 'photos/p08.jpg', '2022-12-14'),
(9, 'Beryl', 'Santos', '1940-08-25', 'F', 'B04', 'photos/p09.jpg', '2023-07-30'),
(10, 'Leonard', 'Fischer', '1935-02-11', 'M', 'B05', 'photos/p10.jpg', '2021-10-12'),
(11, 'Ivy', 'Campbell', '1944-06-06', 'F', 'C01', 'photos/p11.jpg', '2025-02-01'),
(12, 'Arthur', 'Mehta', '1938-10-29', 'M', 'C02', 'photos/p12.jpg', '2024-08-16'),
(13, 'Nancy', 'O\'Brien', '1943-03-03', 'F', 'C03', 'photos/p13.jpg', '2025-04-20'),
(14, 'Ronald', 'Zhang', '1932-12-12', 'M', 'C04', 'photos/p14.jpg', '2020-11-08'),
(15, 'Phyllis', 'Warren', '1945-07-21', 'F', 'C05', 'photos/p15.jpg', '2025-06-12');

-- --------------------------------------------------------

--
-- Table structure for table `practitioner`
--

CREATE TABLE `practitioner` (
  `practitioner_id` int(11) NOT NULL,
  `username` varchar(30) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(80) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('practitioner','administrator') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `practitioner`
--

INSERT INTO `practitioner` (`practitioner_id`, `username`, `password_hash`, `full_name`, `email`, `role`) VALUES
(1, 'fnightingale', '$2y$10$YAysRxOKuROqmpZEndtQceT6Ov0LFDmz/uWpjDIbU0kFdSkvIQ4oq', 'Florence Nightingale', 'f.nightingale@carehome.example', 'practitioner'),
(2, 'jchen', '$2y$10$VojGrkEzl4KIXvz/77Wpse.NP2DWngokAuqbD0mWZ7KbGnb30BpWS', 'Jun Chen', 'j.chen@carehome.example', 'practitioner'),
(3, 'apatel', '$2y$10$ObzRAiXFLOr/Lqw92IseIO23rvImh5VmxRb7BJ3wMTzfaWY8U0Day', 'Anita Patel', 'a.patel@carehome.example', 'practitioner'),
(4, 'mrossi', '$2y$10$RMnQwDNr7dEwowLxWeUA9uOk.wD2hkziucowlYB8HmgHN4swm7lWi', 'Marco Rossi', 'm.rossi@carehome.example', 'practitioner'),
(5, 'admin', '$2y$10$YNnNBHDiTCaozw9aTtOjC.tujt4GIAX8Y2iqbbfDbCjsQT1rMpW5m', 'Facility Director', 'director@carehome.example', 'administrator');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `medication`
--
ALTER TABLE `medication`
  ADD PRIMARY KEY (`medication_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `medication_administration`
--
ALTER TABLE `medication_administration`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `uq_admin_order_date` (`order_id`,`scheduled_date`),
  ADD KEY `fk_admin_practitioner` (`practitioner_id`);

--
-- Indexes for table `medication_order`
--
ALTER TABLE `medication_order`
  ADD PRIMARY KEY (`order_id`),
  ADD UNIQUE KEY `uq_order_patient_med_round` (`patient_id`,`medication_id`,`round`),
  ADD KEY `fk_order_medication` (`medication_id`);

--
-- Indexes for table `patient`
--
ALTER TABLE `patient`
  ADD PRIMARY KEY (`patient_id`);

--
-- Indexes for table `practitioner`
--
ALTER TABLE `practitioner`
  ADD PRIMARY KEY (`practitioner_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `medication`
--
ALTER TABLE `medication`
  MODIFY `medication_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `medication_administration`
--
ALTER TABLE `medication_administration`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=389;

--
-- AUTO_INCREMENT for table `medication_order`
--
ALTER TABLE `medication_order`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `patient`
--
ALTER TABLE `patient`
  MODIFY `patient_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `practitioner`
--
ALTER TABLE `practitioner`
  MODIFY `practitioner_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `medication_administration`
--
ALTER TABLE `medication_administration`
  ADD CONSTRAINT `fk_admin_order` FOREIGN KEY (`order_id`) REFERENCES `medication_order` (`order_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_admin_practitioner` FOREIGN KEY (`practitioner_id`) REFERENCES `practitioner` (`practitioner_id`) ON UPDATE CASCADE;

--
-- Constraints for table `medication_order`
--
ALTER TABLE `medication_order`
  ADD CONSTRAINT `fk_order_medication` FOREIGN KEY (`medication_id`) REFERENCES `medication` (`medication_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_patient` FOREIGN KEY (`patient_id`) REFERENCES `patient` (`patient_id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

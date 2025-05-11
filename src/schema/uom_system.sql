-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 11, 2025 at 12:21 PM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `uom_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`id`, `user_id`, `action`, `description`, `ip_address`, `created_at`) VALUES
(1, 1, 'User Logout', 'User logged out successfully', '::1', '2025-05-11 01:12:37'),
(2, 1, 'Admin Login', 'Admin logged in successfully', '::1', '2025-05-11 01:12:51'),
(3, 1, 'Profile Photo Update', 'Admin updated profile photo', '::1', '2025-05-11 01:13:03'),
(4, 1, 'Profile Update', 'Admin updated profile information', '::1', '2025-05-11 01:13:14'),
(5, 1, 'Password Change', 'Admin changed their password', '::1', '2025-05-11 01:13:36'),
(6, 1, 'User Logout', 'User logged out successfully', '::1', '2025-05-11 01:13:43'),
(7, 1, 'Admin Login', 'Admin logged in successfully', '::1', '2025-05-11 01:14:39'),
(8, 1, 'Profile Photo Update', 'Admin updated profile photo', '::1', '2025-05-11 01:15:53'),
(9, 1, 'Profile Update', 'Admin updated profile information', '::1', '2025-05-11 01:15:59'),
(10, 1, 'User Creation', 'Admin created new user: Dogge Tech (admin2)', '::1', '2025-05-11 01:24:40'),
(11, 1, 'User Logout', 'User logged out successfully', '::1', '2025-05-11 01:24:42'),
(12, NULL, 'User Login', 'User logged in successfully', '::1', '2025-05-11 01:24:48'),
(13, NULL, 'Add User', 'Admin added new user: Princess Mae Acal (2025001)', '::1', '2025-05-11 01:48:30'),
(14, NULL, 'Role Validation', 'Found 1 accounts with invalid roles: [{\"id\":\"3\",\"name\":\"Princess Mae Acal\",\"student_id\":\"2025001\",\"role\":\"\"}]', '::1', '2025-05-11 01:48:30'),
(15, NULL, 'Role Validation', 'Found 1 accounts with invalid roles: [{\"id\":\"3\",\"name\":\"Princess Mae Acal\",\"student_id\":\"2025001\",\"role\":\"\"}]', '::1', '2025-05-11 01:48:46'),
(16, NULL, 'Role Validation', 'Found 1 accounts with invalid roles: [{\"id\":\"3\",\"name\":\"Princess Mae Acal\",\"student_id\":\"2025001\",\"role\":\"\"}]', '::1', '2025-05-11 01:49:02'),
(17, NULL, 'Add User', 'Admin added new user: Jhanny Regodos (2025002)', '::1', '2025-05-11 01:49:02'),
(18, NULL, 'Role Validation', 'Found 2 accounts with invalid roles: [{\"id\":\"3\",\"name\":\"Princess Mae Acal\",\"student_id\":\"2025001\",\"role\":\"\"},{\"id\":\"4\",\"name\":\"Jhanny Regodos\",\"student_id\":\"2025002\",\"role\":\"\"}]', '::1', '2025-05-11 01:49:02'),
(19, NULL, 'Role Validation', 'Found 2 accounts with invalid roles: [{\"id\":\"3\",\"name\":\"Princess Mae Acal\",\"student_id\":\"2025001\",\"role\":\"\"},{\"id\":\"4\",\"name\":\"Jhanny Regodos\",\"student_id\":\"2025002\",\"role\":\"\"}]', '::1', '2025-05-11 01:53:15'),
(20, NULL, 'User Update', 'Admin updated user: Princess Mae Acal (2025001)', '::1', '2025-05-11 01:53:15'),
(21, NULL, 'Role Validation', 'Found 2 accounts with invalid roles: [{\"id\":\"3\",\"name\":\"Princess Mae Acal\",\"student_id\":\"2025001\",\"role\":\"\"},{\"id\":\"4\",\"name\":\"Jhanny Regodos\",\"student_id\":\"2025002\",\"role\":\"\"}]', '::1', '2025-05-11 01:53:15'),
(22, NULL, 'Role Validation', 'Found 2 accounts with invalid roles: [{\"id\":\"3\",\"name\":\"Princess Mae Acal\",\"student_id\":\"2025001\",\"role\":\"\"},{\"id\":\"4\",\"name\":\"Jhanny Regodos\",\"student_id\":\"2025002\",\"role\":\"\"}]', '::1', '2025-05-11 01:56:21'),
(23, NULL, 'User Update', 'Admin updated user: Jhanny Regodos (2025002)', '::1', '2025-05-11 01:56:21'),
(24, NULL, 'Role Validation', 'Found 2 accounts with invalid roles: [{\"id\":\"3\",\"name\":\"Princess Mae Acal\",\"student_id\":\"2025001\",\"role\":\"\"},{\"id\":\"4\",\"name\":\"Jhanny Regodos\",\"student_id\":\"2025002\",\"role\":\"\"}]', '::1', '2025-05-11 01:56:21'),
(25, NULL, 'Role Validation', 'Found 2 accounts with invalid roles: [{\"id\":\"3\",\"name\":\"Princess Mae Acal\",\"student_id\":\"2025001\",\"role\":\"\"},{\"id\":\"4\",\"name\":\"Jhanny Regodos\",\"student_id\":\"2025002\",\"role\":\"\"}]', '::1', '2025-05-11 02:01:42'),
(26, NULL, 'User Update', 'Admin updated user: Dogge Tech (admin2)', '::1', '2025-05-11 02:01:42'),
(27, NULL, 'Role Validation', 'Found 2 accounts with invalid roles: [{\"id\":\"3\",\"name\":\"Princess Mae Acal\",\"student_id\":\"2025001\",\"role\":\"\"},{\"id\":\"4\",\"name\":\"Jhanny Regodos\",\"student_id\":\"2025002\",\"role\":\"\"}]', '::1', '2025-05-11 02:01:42'),
(28, NULL, 'Role Validation', 'Found 2 accounts with invalid roles: [{\"id\":\"3\",\"name\":\"Princess Mae Acal\",\"student_id\":\"2025001\",\"role\":\"\"},{\"id\":\"4\",\"name\":\"Jhanny Regodos\",\"student_id\":\"2025002\",\"role\":\"\"}]', '::1', '2025-05-11 02:01:55'),
(29, NULL, 'User Update', 'Admin updated user: Dogge Tech (staff)', '::1', '2025-05-11 02:01:55'),
(30, NULL, 'Role Validation', 'Found 2 accounts with invalid roles: [{\"id\":\"3\",\"name\":\"Princess Mae Acal\",\"student_id\":\"2025001\",\"role\":\"\"},{\"id\":\"4\",\"name\":\"Jhanny Regodos\",\"student_id\":\"2025002\",\"role\":\"\"}]', '::1', '2025-05-11 02:01:55'),
(31, NULL, 'Settings Update', 'Admin updated system settings', '::1', '2025-05-11 02:02:01'),
(32, NULL, 'User Logout', 'User logged out successfully', '::1', '2025-05-11 02:02:04'),
(33, 1, 'Admin Login', 'Admin logged in successfully', '::1', '2025-05-11 02:02:15'),
(34, 1, 'Role Validation', 'Found 2 accounts with invalid roles: [{\"id\":\"3\",\"name\":\"Princess Mae Acal\",\"student_id\":\"2025001\",\"role\":\"\"},{\"id\":\"4\",\"name\":\"Jhanny Regodos\",\"student_id\":\"2025002\",\"role\":\"\"}]', '::1', '2025-05-11 02:07:33'),
(35, 1, 'Role Validation', 'Found 2 accounts with invalid roles: [{\"id\":\"3\",\"name\":\"Princess Mae Acal\",\"student_id\":\"2025001\",\"role\":\"\"},{\"id\":\"4\",\"name\":\"Jhanny Regodos\",\"student_id\":\"2025002\",\"role\":\"\"}]', '::1', '2025-05-11 02:07:53'),
(36, 1, 'Add User', 'Admin added new user: Admin System (2025003)', '::1', '2025-05-11 02:07:53'),
(37, 1, 'Role Validation', 'Found 2 accounts with invalid roles: [{\"id\":\"3\",\"name\":\"Princess Mae Acal\",\"student_id\":\"2025001\",\"role\":\"\"},{\"id\":\"4\",\"name\":\"Jhanny Regodos\",\"student_id\":\"2025002\",\"role\":\"\"}]', '::1', '2025-05-11 02:07:53'),
(38, 1, 'Role Validation', 'Found 2 accounts with invalid roles: [{\"id\":\"3\",\"name\":\"Princess Mae Acal\",\"student_id\":\"2025001\",\"role\":\"\"},{\"id\":\"4\",\"name\":\"Jhanny Regodos\",\"student_id\":\"2025002\",\"role\":\"\"}]', '::1', '2025-05-11 02:08:36'),
(39, 1, 'Add User', 'Admin added new user: Jonald Edaño (2025004)', '::1', '2025-05-11 02:08:36'),
(40, 1, 'Role Validation', 'Found 2 accounts with invalid roles: [{\"id\":\"3\",\"name\":\"Princess Mae Acal\",\"student_id\":\"2025001\",\"role\":\"\"},{\"id\":\"4\",\"name\":\"Jhanny Regodos\",\"student_id\":\"2025002\",\"role\":\"\"}]', '::1', '2025-05-11 02:08:36'),
(41, 1, 'Role Validation', 'Found 2 accounts with invalid roles: [{\"id\":\"3\",\"name\":\"Princess Mae Acal\",\"student_id\":\"2025001\",\"role\":\"\"},{\"id\":\"4\",\"name\":\"Jhanny Regodos\",\"student_id\":\"2025002\",\"role\":\"\"}]', '::1', '2025-05-11 02:11:09'),
(42, 1, 'Role Validation', 'Found 2 accounts with invalid roles: [{\"id\":\"3\",\"name\":\"Princess Mae Acal\",\"student_id\":\"2025001\",\"role\":\"\"},{\"id\":\"4\",\"name\":\"Jhanny Regodos\",\"student_id\":\"2025002\",\"role\":\"\"}]', '::1', '2025-05-11 02:11:29'),
(43, 1, 'Add User', 'Admin added new user: Necil Onihog (2025005)', '::1', '2025-05-11 02:11:29'),
(44, 1, 'Role Validation', 'Found 2 accounts with invalid roles: [{\"id\":\"3\",\"name\":\"Princess Mae Acal\",\"student_id\":\"2025001\",\"role\":\"\"},{\"id\":\"4\",\"name\":\"Jhanny Regodos\",\"student_id\":\"2025002\",\"role\":\"\"}]', '::1', '2025-05-11 02:11:29'),
(45, 1, 'Role Validation', 'Found 2 accounts with invalid roles: [{\"id\":\"3\",\"name\":\"Princess Mae Acal\",\"student_id\":\"2025001\",\"role\":\"\"},{\"id\":\"4\",\"name\":\"Jhanny Regodos\",\"student_id\":\"2025002\",\"role\":\"\"}]', '::1', '2025-05-11 02:11:39'),
(46, 1, 'User Update', 'Admin updated user: Princess Mae Acal (2025001)', '::1', '2025-05-11 02:11:39'),
(47, 1, 'Role Validation', 'Found 1 accounts with invalid roles: [{\"id\":\"4\",\"name\":\"Jhanny Regodos\",\"student_id\":\"2025002\",\"role\":\"\"}]', '::1', '2025-05-11 02:11:39'),
(48, 1, 'Role Validation', 'Found 1 accounts with invalid roles: [{\"id\":\"4\",\"name\":\"Jhanny Regodos\",\"student_id\":\"2025002\",\"role\":\"\"}]', '::1', '2025-05-11 02:11:46'),
(49, 1, 'User Update', 'Admin updated user: Jhanny Regodos (2025002)', '::1', '2025-05-11 02:11:46'),
(50, 1, 'Add User', 'Admin added new user: Marina Pimentel (2025006)', '::1', '2025-05-11 02:12:06'),
(51, 1, 'Database Backup', 'Created database backup: db_backup_2025-05-11-04-29-12.sql', '::1', '2025-05-11 02:29:12'),
(52, 1, 'Add User', 'Admin added new user: Clint Jireh Rafer (2025007)', '::1', '2025-05-11 02:38:11'),
(53, 1, 'Add User', 'Admin added new user: Klient Ariola (2025008)', '::1', '2025-05-11 02:39:29'),
(54, 1, 'User Deletion', 'Admin deleted user: Dogge Tech (staff)', '::1', '2025-05-11 02:40:21'),
(55, 1, 'Add User', 'Admin added new user: Dogge Tech (2025009)', '::1', '2025-05-11 02:40:53'),
(56, 1, 'User Deletion', 'Admin deleted user: Dogge Tech (2025009)', '::1', '2025-05-11 02:42:14'),
(57, 1, 'Add User', 'Admin added new user: Lhester Candano (staff1)', '::1', '2025-05-11 02:49:22'),
(58, 1, 'User Deletion', 'Admin deleted user: Lhester Candano (staff1)', '::1', '2025-05-11 02:49:31'),
(59, 1, 'Add User', 'Admin added new user: Lhester Candano (2025009)', '::1', '2025-05-11 02:53:16'),
(60, 1, 'Add User', 'Admin added new user: ICTS Admin (admin1)', '::1', '2025-05-11 02:53:57'),
(61, 1, 'User Deletion', 'Admin deleted user: Admin System (2025003)', '::1', '2025-05-11 02:54:28'),
(62, 1, 'Add User', 'Admin added new user: CJ Ranido (admin2)', '::1', '2025-05-11 02:55:03'),
(63, 1, 'Add User', 'Admin added new user: Erickaye Villegas (staff1)', '::1', '2025-05-11 02:56:01'),
(64, 1, 'Add User', 'Admin added new user: Liel Regodos (2025010)', '::1', '2025-05-11 03:01:12'),
(65, 1, 'User Logout', 'User logged out successfully', '::1', '2025-05-11 04:39:29'),
(66, 1, 'Admin Login', 'Admin logged in successfully', '::1', '2025-05-11 04:40:23'),
(67, 1, 'Settings Update', 'Admin updated system settings', '::1', '2025-05-11 05:02:26'),
(68, 1, 'User Logout', 'User logged out successfully', '::1', '2025-05-11 05:02:33'),
(69, 16, 'User Login', 'User logged in successfully', '::1', '2025-05-11 05:02:47'),
(70, 16, 'User Logout', 'User logged out successfully', '::1', '2025-05-11 05:14:57'),
(71, 1, 'Admin Login', 'Admin logged in successfully', '::1', '2025-05-11 05:15:05'),
(72, 1, 'Add User', 'Admin added new user: Renthesia Mae Recustudio (2025011)', '::1', '2025-05-11 05:16:23'),
(73, 1, 'User Logout', 'User logged out successfully', '::1', '2025-05-11 05:17:43'),
(74, 1, 'Admin Login', 'Admin logged in successfully', '::1', '2025-05-11 05:19:08'),
(75, 1, 'User Logout', 'User logged out successfully', '::1', '2025-05-11 05:23:53'),
(76, 1, 'Admin Login', 'Admin logged in successfully', '::1', '2025-05-11 05:28:12'),
(77, 1, 'Logout', 'User logged out', '::1', '2025-05-11 05:28:25'),
(78, 1, 'Admin Login', 'Admin logged in successfully', '::1', '2025-05-11 05:28:36'),
(79, 1, 'Logout', 'User logged out', '::1', '2025-05-11 05:28:47'),
(80, 1, 'Admin Login', 'Admin logged in successfully', '::1', '2025-05-11 05:28:51'),
(81, 1, 'Logout', 'User logged out', '::1', '2025-05-11 05:28:55'),
(82, 1, 'Admin Login', 'Admin logged in successfully', '::1', '2025-05-11 05:28:59'),
(83, 1, 'Logout', 'User logged out', '::1', '2025-05-11 05:29:07'),
(84, 1, 'Admin Login', 'Admin logged in successfully', '::1', '2025-05-11 05:29:10'),
(85, 1, 'User Deletion', 'Admin deleted user: CJ Ranido (admin2)', '::1', '2025-05-11 05:36:12'),
(86, 1, 'Add User', 'Admin added new user: Athina Yaba (2025012)', '::1', '2025-05-11 05:36:50'),
(87, 1, 'Add User', 'Admin added new user: Angel Grace Cabig (2025013)', '::1', '2025-05-11 05:38:02'),
(88, 1, 'Add User', 'Admin added new user: Tatyana Tacubao (2025014)', '::1', '2025-05-11 05:38:22'),
(89, 1, 'Add User', 'Admin added new user: Alliyah Marie Malna (2025015)', '::1', '2025-05-11 05:38:47'),
(90, 1, 'Logout', 'User logged out', '::1', '2025-05-11 05:38:51'),
(91, 1, 'User Login', 'User logged in successfully', '::1', '2025-05-11 05:40:56'),
(92, 1, 'Logout', 'User logged out', '::1', '2025-05-11 05:46:28'),
(93, 3, 'User Login', 'User logged in successfully', '::1', '2025-05-11 05:48:26'),
(94, 3, 'Logout', 'User logged out', '::1', '2025-05-11 05:48:40'),
(95, 14, 'User Login', 'User logged in successfully', '::1', '2025-05-11 05:48:52'),
(96, 14, 'Logout', 'User logged out', '::1', '2025-05-11 05:49:27'),
(97, 1, 'Admin Login', 'Admin logged in successfully', '::1', '2025-05-11 05:49:30'),
(98, 1, 'Profile Update', 'Admin updated profile information', '::1', '2025-05-11 05:58:14'),
(99, 1, 'Logout', 'User logged out', '::1', '2025-05-11 05:58:19'),
(100, 1, 'Admin Login', 'Admin logged in successfully', '::1', '2025-05-11 05:58:25'),
(101, 1, 'Password Update', 'Admin updated password', '::1', '2025-05-11 05:59:16'),
(102, 1, 'Logout', 'User logged out', '::1', '2025-05-11 05:59:22'),
(103, 1, 'Admin Login', 'Admin logged in successfully', '::1', '2025-05-11 05:59:30'),
(104, 1, 'Settings Update', 'Admin updated system settings', '::1', '2025-05-11 05:59:51'),
(105, 1, 'Logout', 'User logged out', '::1', '2025-05-11 05:59:54'),
(106, 1, 'User Login', 'User logged in successfully', '::1', '2025-05-11 05:59:58'),
(107, 1, 'Logout', 'User logged out', '::1', '2025-05-11 06:01:20'),
(108, 1, 'User Login', 'User logged in successfully', '::1', '2025-05-11 06:01:54'),
(109, 1, 'Logout', 'User logged out', '::1', '2025-05-11 06:02:04'),
(110, 1, 'Admin Login', 'Admin logged in successfully', '::1', '2025-05-11 06:02:10'),
(111, 1, 'Logout', 'User logged out', '::1', '2025-05-11 06:06:36'),
(112, 1, 'Admin Login', 'Admin logged in successfully', '::1', '2025-05-11 06:06:47'),
(113, 1, 'Admin Login', 'Admin logged in successfully', '::1', '2025-05-11 09:47:10'),
(114, 1, 'Logout', 'User logged out', '::1', '2025-05-11 09:51:00'),
(115, 1, 'Admin Login', 'Admin logged in successfully', '::1', '2025-05-11 09:53:46'),
(116, 1, 'Logout', 'User logged out', '::1', '2025-05-11 09:53:56'),
(117, 1, 'Admin Login', 'Admin logged in successfully', '::1', '2025-05-11 09:58:02'),
(118, 1, 'Add User', 'Admin added new user: Bruno Mars (2025016)', '::1', '2025-05-11 09:59:26'),
(119, 1, 'User Update', 'Admin updated user: Bruno David Mars ()', '::1', '2025-05-11 10:02:09'),
(120, 1, 'User Deletion', 'Admin deleted user: Bruno David Mars ()', '::1', '2025-05-11 10:02:40'),
(121, 1, 'Logout', 'User logged out', '::1', '2025-05-11 10:03:24');

-- --------------------------------------------------------

--
-- Table structure for table `billing`
--

CREATE TABLE `billing` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ledger_id` int(11) NOT NULL,
  `receipt_image` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `card_transactions`
--

CREATE TABLE `card_transactions` (
  `id` int(11) NOT NULL,
  `card_id` int(11) NOT NULL,
  `transaction_type` enum('issue','revoke','suspend','add_points','use_points','renew') NOT NULL,
  `points_amount` int(11) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `category` enum('operational','salary','departmental','miscellaneous') NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `receipt_image` varchar(255) DEFAULT NULL,
  `expense_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `financial_transactions`
--

CREATE TABLE `financial_transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `transaction_type` enum('tuition','shop','permit','fine','other') NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `receipt_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ledger`
--

CREATE TABLE `ledger` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `semester` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('paid','pending','overdue') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permits`
--

CREATE TABLE `permits` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `semester` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approval_date` timestamp NULL DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `category` varchar(50) DEFAULT 'general',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `category`, `description`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'Working Scholars Association', 'general', 'Name of the website', '2025-05-11 01:12:17', '2025-05-11 05:59:51'),
(2, 'site_email', 'admin@g.cu.edu.ph', 'general', 'Primary contact email', '2025-05-11 01:12:17', '2025-05-11 05:59:51'),
(3, 'maintenance_mode', '1', 'general', 'Site maintenance mode (0=off, 1=on)', '2025-05-11 01:12:17', '2025-05-11 05:59:51'),
(4, 'version', '1.0.0', 'system', 'Current system version', '2025-05-11 01:12:17', '2025-05-11 01:12:17'),
(5, 'last_update', '2025-05-11 04:29:12', 'system', 'Last system update timestamp', '2025-05-11 01:12:17', '2025-05-11 02:29:12'),
(6, 'db_created', '2025-05-11 09:12:17', 'system', 'Date when database was first created', '2025-05-11 01:12:17', '2025-05-11 01:12:17'),
(7, 'db_integrity_check', '2025-05-11 09:12:18', 'system', 'Last time database integrity was verified', '2025-05-11 01:12:18', '2025-05-11 01:12:18');

-- --------------------------------------------------------

--
-- Table structure for table `student_cards`
--

CREATE TABLE `student_cards` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `card_number` varchar(50) NOT NULL,
  `issue_date` date NOT NULL,
  `expiry_date` date NOT NULL,
  `status` enum('active','suspended','revoked','expired') DEFAULT 'active',
  `points_balance` int(11) DEFAULT 0,
  `last_used` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_permits`
--

CREATE TABLE `student_permits` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `term` varchar(50) NOT NULL,
  `semester` varchar(100) NOT NULL,
  `status` enum('Allowed','Disallowed') NOT NULL DEFAULT 'Allowed',
  `approved_by` varchar(100) NOT NULL,
  `approval_date` date NOT NULL,
  `file_path` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `points` int(11) DEFAULT 0,
  `role` enum('student','admin','staff') NOT NULL DEFAULT 'student',
  `status` enum('pending','approved','rejected','blocked') NOT NULL DEFAULT 'pending',
  `date_of_birth` date DEFAULT NULL,
  `address` text DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `marital_status` varchar(20) DEFAULT NULL,
  `nationality` varchar(50) DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `emergency_contact_relation` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `student_id`, `password`, `first_name`, `last_name`, `email`, `profile_image`, `department`, `points`, `role`, `status`, `date_of_birth`, `address`, `phone_number`, `gender`, `marital_status`, `nationality`, `emergency_contact_name`, `emergency_contact_phone`, `emergency_contact_relation`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$Xd9msRcbcuWuJnJWo6vHpe33pGJ/sCbCJ02wu/73g/U9h42VUx.jS', 'System', 'Admin', 'admin@g.cu.edu.ph', 'uploads/profile_images/1_1746926153.png', NULL, 0, 'admin', 'approved', NULL, NULL, NULL, NULL, NULL, NULL, 'Capitol University', NULL, NULL, '2025-05-11 01:12:17', '2025-05-11 05:59:16'),
(3, '2025001', '$2y$10$iAInv2L2PZ8uddZya4nlh.EuhZy2ISW19nxgLlDS2EAIhB6L8xPwy', 'Princess Mae', 'Acal', 'primae@gmail.com', 'uploads/profiles/2025001_1746928395_acal.jpg', NULL, 0, 'student', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-11 01:48:30', '2025-05-11 02:11:39'),
(4, '2025002', '$2y$10$EcRHIH5tZpXJekFC8nRw1OZb5OwHDc7h2cMsYXZoyXA2NlwLLIFXy', 'Jhanny', 'Regodos', 'jhannyregodos@gmail.com', 'uploads/profiles/2025002_1746928581_staff-avatar.jpg', NULL, 0, 'student', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-11 01:49:02', '2025-05-11 02:11:46'),
(6, '2025004', '$2y$10$EaOg114NOa5P5m4OWe.Bb./n4kA5fPb5f7yKNReJm2A2drHDX5bv.', 'Jonald', 'Edaño', 'jonald@gmail.com', NULL, NULL, 0, 'student', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-11 02:08:36', '2025-05-11 02:08:36'),
(7, '2025005', '$2y$10$Uznlh1h1v6zWMoxKSypIn.V1aELZtl.A0Wog6CbsfpPo/8jXaKLTe', 'Necil', 'Onihog', 'necil@gmail.com', NULL, NULL, 0, 'student', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-11 02:11:29', '2025-05-11 02:11:29'),
(8, '2025006', '$2y$10$g6A1pN8bs8xh287Ae4/KYuqTDzUhGd3hCeqnkU8i4Omw2d14NFzTu', 'Marina', 'Pimentel', 'marina@gmail.com', NULL, NULL, 0, 'student', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-11 02:12:06', '2025-05-11 02:12:06'),
(9, '2025007', '$2y$10$lH.l.eOevCm1EntT2bS78e1qz0tBBDBrC5W7waW2e63yjMRXB9u7G', 'Clint Jireh', 'Rafer', 'rafer@gmail.com', 'assets/images/user-avatar.jpg', NULL, 0, 'student', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-11 02:38:11', '2025-05-11 02:38:11'),
(10, '2025008', '$2y$10$.NVU4ZrWcsQZiG1S1D2upeSSeEG7EX7SfNk6B8AMU1Yze7c.kgdZm', 'Klient', 'Ariola', 'ariola@gmail.com', 'assets/images/user-avatar.jpg', NULL, 0, 'student', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-11 02:39:29', '2025-05-11 02:39:29'),
(13, '2025009', '$2y$10$gdQyMFxcp4TKqlYTAgAWAu3BWr.UQRbi4vV/0fdIQbbCbpoPkoMUe', 'Lhester', 'Candano', 'lhester@gmail.com', 'assets/images/user-avatar.jpg', NULL, 0, 'student', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-11 02:53:16', '2025-05-11 02:53:16'),
(14, 'admin1', '$2y$10$zIQ3AXIFCw/XcJQn.EynO.Nw6/xTc2X/8SdRunzWKdWnPaL6w3pwK', 'ICTS', 'Admin', 'icts@gmail.com', 'assets/images/admin-avatar.jpg', NULL, 0, 'admin', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-11 02:53:57', '2025-05-11 02:53:57'),
(16, 'staff1', '$2y$10$uO0FeugwcszIr3oxRYhYkuCOXUMqF4Ch4xzPDtsYnRSKGFGihFEOW', 'Erickaye', 'Villegas', 'erickaye@example.com', 'assets/images/staff-avatar.jpg', NULL, 0, 'staff', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-11 02:56:01', '2025-05-11 02:56:01'),
(17, '2025010', '$2y$10$n5y133tc7NqCAtfDbj7WBetANZ6napAl8OsK8m7aEvjD4dqiP.wJW', 'Liel', 'Regodos', 'liel@example.com', 'assets/images/user-avatar.jpg', NULL, 0, 'student', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-11 03:01:12', '2025-05-11 03:01:12'),
(18, '2025011', '$2y$10$jRJjYCtutN4D6MfT//uv5OBwPJQNdZ9NxZb/PyVzLTlx/IQLr3/2e', 'Renthesia Mae', 'Recustudio', 'chem@example.com', 'assets/images/user-avatar.jpg', NULL, 0, 'student', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-11 05:16:23', '2025-05-11 05:16:23'),
(19, '2025012', '$2y$10$G7NBLUVU4yfhImBN/Az1yu0UEjtsmCXAMYQmbXncrjglnuitjoqXa', 'Athina', 'Yaba', 'athina@example.com', 'assets/images/user-avatar.jpg', NULL, 0, 'student', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-11 05:36:50', '2025-05-11 05:36:50'),
(20, '2025013', '$2y$10$Tv8RKCzY.AhSZ65KUuQh4u96CfzQyeACjFcA7tLUFDqIyMavvsGIK', 'Angel Grace', 'Cabig', 'agcabs@example.com', 'assets/images/user-avatar.jpg', NULL, 0, 'student', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-11 05:38:02', '2025-05-11 05:38:02'),
(21, '2025014', '$2y$10$aHDCwx9pyuLilnd1mKrOUuPwp5L.NXltN7j1EhsPr5nNGxauqzbAe', 'Tatyana', 'Tacubao', 'tatskie@example.com', 'assets/images/user-avatar.jpg', NULL, 0, 'student', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-11 05:38:22', '2025-05-11 05:38:22'),
(22, '2025015', '$2y$10$JHjBPBCQGh767GPnhs5fbuYK3wD5IOevw1QcEutM175wZcIJTVt46', 'Alliyah Marie', 'Malna', 'nue@example.com', 'assets/images/user-avatar.jpg', NULL, 0, 'student', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-11 05:38:47', '2025-05-11 05:38:47');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `billing`
--
ALTER TABLE `billing`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `ledger_id` (`ledger_id`);

--
-- Indexes for table `card_transactions`
--
ALTER TABLE `card_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `card_id` (`card_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `financial_transactions`
--
ALTER TABLE `financial_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `ledger`
--
ALTER TABLE `ledger`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_semester` (`user_id`,`semester`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `permits`
--
ALTER TABLE `permits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `student_cards`
--
ALTER TABLE `student_cards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `student_permits`
--
ALTER TABLE `student_permits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_term_semester` (`student_id`,`term`,`semester`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_approval_date` (`approval_date`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_role_status` (`role`,`status`),
  ADD KEY `idx_name` (`first_name`,`last_name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=122;

--
-- AUTO_INCREMENT for table `billing`
--
ALTER TABLE `billing`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `card_transactions`
--
ALTER TABLE `card_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `financial_transactions`
--
ALTER TABLE `financial_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ledger`
--
ALTER TABLE `ledger`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permits`
--
ALTER TABLE `permits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `student_cards`
--
ALTER TABLE `student_cards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_permits`
--
ALTER TABLE `student_permits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `billing`
--
ALTER TABLE `billing`
  ADD CONSTRAINT `billing_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `billing_ibfk_2` FOREIGN KEY (`ledger_id`) REFERENCES `ledger` (`id`);

--
-- Constraints for table `card_transactions`
--
ALTER TABLE `card_transactions`
  ADD CONSTRAINT `card_transactions_ibfk_1` FOREIGN KEY (`card_id`) REFERENCES `student_cards` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `card_transactions_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `financial_transactions`
--
ALTER TABLE `financial_transactions`
  ADD CONSTRAINT `financial_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `financial_transactions_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `ledger`
--
ALTER TABLE `ledger`
  ADD CONSTRAINT `ledger_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `permits`
--
ALTER TABLE `permits`
  ADD CONSTRAINT `permits_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `permits_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `student_cards`
--
ALTER TABLE `student_cards`
  ADD CONSTRAINT `student_cards_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_permits`
--
ALTER TABLE `student_permits`
  ADD CONSTRAINT `student_permits_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`student_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

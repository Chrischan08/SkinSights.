-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 13, 2026 at 06:15 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `skinsights`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `audit_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action_perform` varchar(255) NOT NULL,
  `audit_timestamp` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`audit_id`, `user_id`, `action_perform`, `audit_timestamp`) VALUES
(1, 1, 'Updated profile status for patient account #3', '2026-06-05 11:18:59'),
(2, 1, 'Approved doctor credentials for ID #2', '2026-06-05 11:22:14'),
(3, 1, 'Exported system diagnostic backup data', '2026-06-05 11:40:05'),
(4, 1, 'Created new database table relation indexes', '2026-06-05 11:58:33'),
(6, 6, 'User logged in successfully.', '2026-09-13 20:27:26'),
(7, 6, 'User logged in successfully.', '2026-09-13 20:33:25'),
(8, 6, 'User logged in successfully.', '2026-09-13 20:34:09'),
(9, 6, 'User logged in successfully.', '2026-09-13 20:37:28'),
(10, 6, 'User logged in successfully.', '2026-09-13 20:40:34'),
(11, 6, 'User logged in successfully.', '2026-09-14 00:02:15');

-- --------------------------------------------------------

--
-- Table structure for table `detection_log`
--

CREATE TABLE `detection_log` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `predicted_diseases` varchar(100) NOT NULL,
  `confidence_score` decimal(5,4) NOT NULL,
  `log_timestamp` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `detection_log`
--

INSERT INTO `detection_log` (`log_id`, `user_id`, `image_path`, `predicted_diseases`, `confidence_score`, `log_timestamp`) VALUES
(1, 3, 'uploads/scans/sc_01.png', 'Melanoma', 0.9500, '2026-06-05 11:18:49'),
(2, 3, 'uploads/scans/sc_02.png', 'Basal Cell Carcinoma', 0.8821, '2026-06-05 11:45:12'),
(3, 5, 'uploads/scans/sc_03.png', 'Eczema', 0.9145, '2026-06-05 11:50:00'),
(4, 5, 'uploads/scans/sc_04.png', 'Atopic Dermatitis', 0.9634, '2026-06-05 11:55:22');

-- --------------------------------------------------------

--
-- Table structure for table `oauth_provider`
--

CREATE TABLE `oauth_provider` (
  `oauth_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `provider_name` varchar(50) NOT NULL,
  `provider_user_id` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_token`
--

CREATE TABLE `password_reset_token` (
  `token_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `otp_code` varchar(10) NOT NULL,
  `expires_at` datetime NOT NULL,
  `is_used` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_reset_token`
--

INSERT INTO `password_reset_token` (`token_id`, `user_id`, `token_hash`, `otp_code`, `expires_at`, `is_used`) VALUES
(1, 6, '', '830050', '2026-09-13 14:40:50', 0);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `user_id` int(11) NOT NULL,
  `user_type_id` int(11) NOT NULL,
  `user_status_id` int(11) NOT NULL DEFAULT 1,
  `user_username` varchar(25) NOT NULL,
  `user_password` varchar(255) NOT NULL,
  `user_created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `user_type_id`, `user_status_id`, `user_username`, `user_password`, `user_created_at`) VALUES
(1, 1, 1, 'admin_christian', '$2y$10$BAIJWUOGM8xldwXg9Pe05uJjuklRwXYufgnIj6/l/QKXkFfj7d4Fa', '2026-06-05 11:16:47'),
(2, 2, 1, 'dr_palaganas', '$2y$10$BAIJWUOGM8xldwXg9Pe05uJjuklRwXYufgnIj6/l/QKXkFfj7d4Fa', '2026-06-05 11:20:15'),
(3, 3, 1, 'patient_castillo', '$2y$10$BAIJWUOGM8xldwXg9Pe05uJjuklRwXYufgnIj6/l/QKXkFfj7d4Fa', '2026-06-05 11:22:30'),
(4, 2, 1, 'dr_santos', '$2y$10$BAIJWUOGM8xldwXg9Pe05uJjuklRwXYufgnIj6/l/QKXkFfj7d4Fa', '2026-06-05 11:25:40'),
(6, 2, 1, 'ChristianCastillo', '$2y$10$ua3/xzFceeYy1m2s4Jxw3O4a2BbzAS3KIa/D/kbLLgJfzMfjJJKti', '2026-09-13 20:21:18');

-- --------------------------------------------------------

--
-- Table structure for table `user_profile`
--

CREATE TABLE `user_profile` (
  `profile_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_profile`
--

INSERT INTO `user_profile` (`profile_id`, `user_id`, `first_name`, `last_name`, `email`, `phone_number`) VALUES
(1, 6, 'Christian', 'Castillo', 'christianollitsac3@gmail.com', '09511788839');

-- --------------------------------------------------------

--
-- Table structure for table `user_session`
--

CREATE TABLE `user_session` (
  `session_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_session`
--

INSERT INTO `user_session` (`session_id`, `user_id`, `ip_address`, `user_agent`) VALUES
(1, 6, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36'),
(2, 6, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36'),
(3, 6, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36'),
(4, 6, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36'),
(5, 6, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36'),
(6, 6, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36');

-- --------------------------------------------------------

--
-- Table structure for table `user_status`
--

CREATE TABLE `user_status` (
  `user_status_id` int(11) NOT NULL,
  `status_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_two_factor`
--

CREATE TABLE `user_two_factor` (
  `two_factor_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `secret_key` varchar(255) NOT NULL,
  `backup_codes` text DEFAULT NULL,
  `is_enabled` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_type`
--

CREATE TABLE `user_type` (
  `user_type_id` int(11) NOT NULL,
  `user_type_name` varchar(25) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_type`
--

INSERT INTO `user_type` (`user_type_id`, `user_type_name`) VALUES
(1, 'administrator'),
(2, 'doctor'),
(3, 'patient'),
(4, 'medical_staff');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`audit_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `detection_log`
--
ALTER TABLE `detection_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `oauth_provider`
--
ALTER TABLE `oauth_provider`
  ADD PRIMARY KEY (`oauth_id`),
  ADD KEY `fk_oauth_user` (`user_id`);

--
-- Indexes for table `password_reset_token`
--
ALTER TABLE `password_reset_token`
  ADD PRIMARY KEY (`token_id`),
  ADD KEY `fk_reset_user` (`user_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `user_type_id` (`user_type_id`);

--
-- Indexes for table `user_profile`
--
ALTER TABLE `user_profile`
  ADD PRIMARY KEY (`profile_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone_number` (`phone_number`);

--
-- Indexes for table `user_session`
--
ALTER TABLE `user_session`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `fk_session_user` (`user_id`);

--
-- Indexes for table `user_status`
--
ALTER TABLE `user_status`
  ADD PRIMARY KEY (`user_status_id`),
  ADD UNIQUE KEY `status_name` (`status_name`);

--
-- Indexes for table `user_two_factor`
--
ALTER TABLE `user_two_factor`
  ADD PRIMARY KEY (`two_factor_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `user_type`
--
ALTER TABLE `user_type`
  ADD PRIMARY KEY (`user_type_id`),
  ADD KEY `user_type_id` (`user_type_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `audit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `detection_log`
--
ALTER TABLE `detection_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `oauth_provider`
--
ALTER TABLE `oauth_provider`
  MODIFY `oauth_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_reset_token`
--
ALTER TABLE `password_reset_token`
  MODIFY `token_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `user_profile`
--
ALTER TABLE `user_profile`
  MODIFY `profile_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_session`
--
ALTER TABLE `user_session`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `user_status`
--
ALTER TABLE `user_status`
  MODIFY `user_status_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_two_factor`
--
ALTER TABLE `user_two_factor`
  MODIFY `two_factor_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_type`
--
ALTER TABLE `user_type`
  MODIFY `user_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `oauth_provider`
--
ALTER TABLE `oauth_provider`
  ADD CONSTRAINT `fk_oauth_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `password_reset_token`
--
ALTER TABLE `password_reset_token`
  ADD CONSTRAINT `fk_reset_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_profile`
--
ALTER TABLE `user_profile`
  ADD CONSTRAINT `fk_profile_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_session`
--
ALTER TABLE `user_session`
  ADD CONSTRAINT `fk_session_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_two_factor`
--
ALTER TABLE `user_two_factor`
  ADD CONSTRAINT `fk_2fa_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

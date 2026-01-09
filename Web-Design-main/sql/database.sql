-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Dec 30, 2025 at 07:07 AM
-- Server version: 9.1.0
-- PHP Version: 8.3.14


SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `smart_tips`, `question`, `quiz`, `admin`, `officer`, `student`, `user`, `carbon_emission`;


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `database`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
CREATE TABLE IF NOT EXISTS `admin` (
  `admin_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `identical_number` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `gender` varchar(50) NOT NULL,
  `user_id` int NOT NULL,
  PRIMARY KEY (`admin_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `name`, `identical_number`, `email`, `phone_number`, `gender`, `user_id`) VALUES
(1, 'Jason', '010203-10-1234', 'Tp083259@mail.apu.edu.my', '012-345 6789', 'male', 3);

-- --------------------------------------------------------

--
-- Table structure for table `officer`
--

DROP TABLE IF EXISTS `officer`;
CREATE TABLE IF NOT EXISTS `officer` (
  `officer_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `identical_number` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `gender` varchar(50) NOT NULL,
  `user_id` int NOT NULL,
  PRIMARY KEY (`officer_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `officer`
--

INSERT INTO `officer` (`officer_id`, `name`, `identical_number`, `email`, `phone_number`, `gender`, `user_id`) VALUES
(1, 'Chong', '010203-10-1234', 'Tp082747@mail.apu.edu.my', '012-345 6789', 'male', 1);

-- --------------------------------------------------------

--
-- Table structure for table `question`
--

DROP TABLE IF EXISTS `question`;
CREATE TABLE IF NOT EXISTS `question` (
  `question_id` int NOT NULL AUTO_INCREMENT,
  `question_number` int NOT NULL,
  `question` varchar(255) NOT NULL,
  `picture` varchar(255) DEFAULT NULL,
  `answer` varchar(255) NOT NULL,
  `option_a` varchar(255) NOT NULL,
  `option_b` varchar(255) NOT NULL,
  `option_c` varchar(255) DEFAULT NULL,
  `option_d` varchar(255) DEFAULT NULL,
  `quiz_id` int NOT NULL,
  PRIMARY KEY (`question_id`),
  KEY `quiz_id` (`quiz_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `question`
--

INSERT INTO `question` (`question_id`, `question_number`, `question`, `picture`, `answer`, `option_a`, `option_b`, `option_c`, `option_d`, `quiz_id`) VALUES
(1, 1, 'What is the best way to save energy?', NULL, 'Close the switch', 'Use projector without closing', 'Open all the lights', 'Close the switch', 'Open all the switches', 1),
(2, 2, 'Is closing the laptop a way to save energy usage?', NULL, 'Yes', 'Yes', 'No', NULL, NULL, 1),
(3, 1, 'Can saving energy saved the Earth?', NULL, 'Yes', 'Yes', 'No', NULL, NULL, 2),
(4, 2, 'How can we save energy usage in campus?', NULL, 'Close all the switch when not in use', 'Close all the switch when not in use', 'Open all the switch even not in use', 'Open the projector and TV without closing', NULL, 2);

-- --------------------------------------------------------

--
-- Table structure for table `quiz`
--

DROP TABLE IF EXISTS `quiz`;
CREATE TABLE IF NOT EXISTS `quiz` (
  `quiz_id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `description` varchar(500) NOT NULL,
  `time_limit` int NOT NULL,
  `picture` varchar(255) NOT NULL,
  `status` enum('draft','published','','') NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id` int NOT NULL,
  PRIMARY KEY (`quiz_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quiz`
--

INSERT INTO `quiz` (`quiz_id`, `title`, `description`, `time_limit`, `picture`, `status`, `created_at`, `user_id`) VALUES
(1, 'Energy Savings', 'This quiz is all about ways to save energy in daily life.', 60, 'quiz_69536f69e6f47.jpg', 'draft', '2025-12-30 14:21:29', 1),
(2, 'Eco Friendly', 'This quiz is about saving the Earth.', 30, 'quiz_695378e2f33bd.jpg', 'published', '2025-12-30 15:01:54', 1);

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

DROP TABLE IF EXISTS `student`;
CREATE TABLE IF NOT EXISTS `student` (
  `student_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `identical_number` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `gender` varchar(50) NOT NULL,
  `user_id` int NOT NULL,
  PRIMARY KEY (`student_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`student_id`, `name`, `identical_number`, `email`, `phone_number`, `gender`, `user_id`) VALUES
(1, 'Joshua', '010203-10-1234', 'Tp083719@mail.apu.edu.my', '012-345 6789', 'male', 2);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE IF NOT EXISTS `user` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `username`, `password`, `role`) VALUES
(1, 'TP082747', '$2y$12$yqzGY4Dg0UXzHVRg95dPf.c1BGBilj9E2yP.9fLJvz.fhYI14EB1y', 'officer'),
(2, 'TP083719', '$2y$12$yqzGY4Dg0UXzHVRg95dPf.c1BGBilj9E2yP.9fLJvz.fhYI14EB1y', 'student'),
(3, 'TP083259', '$2y$12$yqzGY4Dg0UXzHVRg95dPf.c1BGBilj9E2yP.9fLJvz.fhYI14EB1y', 'admin');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `admin_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `officer`
--
ALTER TABLE `officer`
  ADD CONSTRAINT `officer_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `question`
--
ALTER TABLE `question`
  ADD CONSTRAINT `question_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `quiz` (`quiz_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `quiz`
--
ALTER TABLE `quiz`
  ADD CONSTRAINT `quiz_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `student`
--
ALTER TABLE `student`
  ADD CONSTRAINT `student_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
-- --------------------------------------------------------

--
-- Table structure for table `smart_tips`
--

DROP TABLE IF EXISTS `smart_tips`;
CREATE TABLE IF NOT EXISTS `smart_tips` (
  `tip_id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `created_by` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`tip_id`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `smart_tips`
--

INSERT INTO `smart_tips` (`tip_id`, `title`, `content`, `thumbnail`, `created_by`, `created_at`) VALUES
(1, 'Turn off lights', 'Turn off lights when not in use to save energy.', NULL, 1, '2025-01-01 10:00:00'),
(2, 'Unplug devices', 'Unplug chargers and electronics when they are fully charged or not in use.', NULL, 1, '2025-01-02 14:30:00');

-- --------------------------------------------------------

--
-- Table structure for table `carbon_emission`
--

DROP TABLE IF EXISTS `carbon_emission`;
CREATE TABLE IF NOT EXISTS `carbon_emission` (
  `emission_id` int NOT NULL AUTO_INCREMENT,
  `month` varchar(50) NOT NULL,
  `year` int NOT NULL,
  `electricity_usage_kwh` decimal(10,2) NOT NULL,
  `carbon_avoided_kg` decimal(10,2) NOT NULL,
  `block_a_usage` decimal(10,2) DEFAULT 0,
  `block_b_usage` decimal(10,2) DEFAULT 0,
  `block_c_usage` decimal(10,2) DEFAULT 0,
  `block_d_usage` decimal(10,2) DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`emission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `carbon_emission`
--

INSERT INTO `carbon_emission` (`emission_id`, `month`, `year`, `electricity_usage_kwh`, `carbon_avoided_kg`, `block_a_usage`, `block_b_usage`, `block_c_usage`, `block_d_usage`, `created_at`) VALUES
(1, 'December', 2024, 1500.50, 450.25, 450.25, 300.10, 375.12, 375.03, '2024-12-31 23:59:59'),
(2, 'January', 2025, 1200.00, 360.00, 360.00, 240.00, 300.00, 300.00, '2025-01-31 23:59:59'),
(3, 'February', 2025, 2186.8, 656.04, 656.04, 437.36, 546.7, 546.7, '2025-01-31 23:59:59'),
(4, 'March', 2025, 1603.81, 481.143, 481.143, 320.762, 400.9525, 400.9525, '2025-01-31 23:59:59'),
(5, 'April', 2025, 2484.12, 745.236, 745.236, 496.824, 621.03, 621.03, '2025-01-31 23:59:59'),
(6, 'May', 2025, 2154.45, 646.335, 646.335, 430.89, 538.6125, 538.6125, '2025-01-31 23:59:59'),
(7, 'June', 2025, 1864.95, 559.485, 559.485, 372.99, 466.2375, 466.2375, '2025-01-31 23:59:59'),
(8, 'July', 2025, 1961.36, 588.408, 588.408, 392.272, 490.34, 490.34, '2025-01-31 23:59:59'),
(9, 'August', 2025, 1511.09, 453.327, 453.327, 302.218, 377.7725, 377.7725, '2025-01-31 23:59:59'),
(10, 'September', 2025, 2379.96, 713.988, 713.988, 475.992, 594.99, 594.99, '2025-01-31 23:59:59'),
(11, 'October', 2025, 2405.11, 721.533, 721.533, 481.022, 601.2775, 601.2775, '2025-01-31 23:59:59'),
(12, 'November', 2025, 2178.78, 653.634, 653.634, 435.756, 544.695, 544.695, '2025-01-31 23:59:59'),
(13, 'December', 2025, 1378.25, 413.475, 413.475, 275.65, 344.5625, 344.5625, '2025-01-31 23:59:59'),
(14, 'January', 2026, 2127.52, 638.256, 638.256, 425.504, 531.88, 531.88, '2026-01-31 23:59:59');

--
-- Constraints for table `smart_tips`
--
ALTER TABLE `smart_tips`
  ADD CONSTRAINT `smart_tips_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

COMMIT;
SET FOREIGN_KEY_CHECKS = 1;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

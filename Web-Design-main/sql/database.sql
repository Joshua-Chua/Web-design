-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jan 11, 2026 at 11:52 AM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `database`
--

-- --------------------------------------------------------

--
-- Table structure for table `achievement`
--

DROP TABLE IF EXISTS `achievement`;
CREATE TABLE IF NOT EXISTS `achievement` (
  `achievement_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` varchar(500) NOT NULL,
  `requirement_type` enum('quizzes_completed','event_participated','login_days','') NOT NULL,
  `requirement_value` int NOT NULL,
  `picture` varchar(255) NOT NULL,
  PRIMARY KEY (`achievement_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `achievement`
--

INSERT INTO `achievement` (`achievement_id`, `name`, `description`, `requirement_type`, `requirement_value`, `picture`) VALUES
(1, 'First Quiz Completed', 'Completed 1 quiz(s)', 'quizzes_completed', 1, 'achievement123.jpg'),
(2, 'A Start For A New Energy Saver', 'Login this website for 1 day(s)', 'login_days', 1, 'login_day1.png');

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `name`, `identical_number`, `email`, `phone_number`, `gender`, `user_id`) VALUES
(1, 'Jason', '010203-10-1234', 'Tp083259@mail.apu.edu.my', '012-345 6789', 'male', 3);

-- --------------------------------------------------------

--
-- Table structure for table `approval`
--

DROP TABLE IF EXISTS `approval`;
CREATE TABLE IF NOT EXISTS `approval` (
  `approval_id` int NOT NULL,
  `approval_decision` tinyint(1) DEFAULT NULL,
  `event_id` int NOT NULL,
  `admin_id` int NOT NULL,
  `proposal_id` int NOT NULL,
  `officer_id` int NOT NULL,
  PRIMARY KEY (`approval_id`),
  KEY `proposal_id` (`proposal_id`),
  KEY `event_id` (`event_id`),
  KEY `admin_id` (`admin_id`),
  KEY `approval_ibfk_4` (`officer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `carbon_emission`
--

DROP TABLE IF EXISTS `carbon_emission`;
CREATE TABLE IF NOT EXISTS `carbon_emission` (
  `emission_id` int NOT NULL AUTO_INCREMENT,
  `month` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `year` int NOT NULL,
  `electricity_usage_kwh` decimal(10,2) NOT NULL,
  `carbon_avoided_kg` decimal(10,2) NOT NULL,
  `block_a_usage` decimal(10,2) DEFAULT '0.00',
  `block_b_usage` decimal(10,2) DEFAULT '0.00',
  `block_c_usage` decimal(10,2) DEFAULT '0.00',
  `block_d_usage` decimal(10,2) DEFAULT '0.00',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`emission_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `carbon_emission`
--

INSERT INTO `carbon_emission` (`emission_id`, `month`, `year`, `electricity_usage_kwh`, `carbon_avoided_kg`, `block_a_usage`, `block_b_usage`, `block_c_usage`, `block_d_usage`, `created_at`) VALUES
(1, 'December', 2024, 1500.50, 450.25, 450.25, 300.10, 375.12, 375.03, '2024-12-31 23:59:59'),
(2, 'January', 2025, 1200.00, 360.00, 360.00, 240.00, 300.00, 300.00, '2025-01-31 23:59:59'),
(3, 'February', 2025, 2186.80, 656.04, 656.04, 437.36, 546.70, 546.70, '2025-01-31 23:59:59'),
(4, 'March', 2025, 1603.81, 481.14, 481.14, 320.76, 400.95, 400.95, '2025-01-31 23:59:59'),
(5, 'April', 2025, 2484.12, 745.24, 745.24, 496.82, 621.03, 621.03, '2025-01-31 23:59:59'),
(6, 'May', 2025, 2154.45, 646.34, 646.34, 430.89, 538.61, 538.61, '2025-01-31 23:59:59'),
(7, 'June', 2025, 1864.95, 559.49, 559.49, 372.99, 466.24, 466.24, '2025-01-31 23:59:59'),
(8, 'July', 2025, 1961.36, 588.41, 588.41, 392.27, 490.34, 490.34, '2025-01-31 23:59:59'),
(9, 'August', 2025, 1511.09, 453.33, 453.33, 302.22, 377.77, 377.77, '2025-01-31 23:59:59'),
(10, 'September', 2025, 2379.96, 713.99, 713.99, 475.99, 594.99, 594.99, '2025-01-31 23:59:59'),
(11, 'October', 2025, 2405.11, 721.53, 721.53, 481.02, 601.28, 601.28, '2025-01-31 23:59:59'),
(12, 'November', 2025, 2178.78, 653.63, 653.63, 435.76, 544.70, 544.70, '2025-01-31 23:59:59'),
(13, 'December', 2025, 1378.25, 413.48, 413.48, 275.65, 344.56, 344.56, '2025-01-31 23:59:59'),
(14, 'January', 2026, 2207.66, 662.30, 658.33, 445.59, 551.95, 551.95, '2026-01-31 23:59:59');

-- --------------------------------------------------------

--
-- Table structure for table `comment`
--

DROP TABLE IF EXISTS `comment`;
CREATE TABLE IF NOT EXISTS `comment` (
  `comment_id` int NOT NULL,
  `comment_details` varchar(500) NOT NULL,
  `post_id` int NOT NULL,
  `user_id` int NOT NULL,
  PRIMARY KEY (`comment_id`),
  KEY `post_id` (`post_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

DROP TABLE IF EXISTS `comments`;
CREATE TABLE IF NOT EXISTS `comments` (
  `comment_id` int NOT NULL AUTO_INCREMENT,
  `post_id` int NOT NULL,
  `user_id` int NOT NULL,
  `comment_text` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`comment_id`),
  KEY `comments_ibfk_1` (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event`
--

DROP TABLE IF EXISTS `event`;
CREATE TABLE IF NOT EXISTS `event` (
  `event_id` int NOT NULL,
  `proposal_id` int NOT NULL,
  `approval_id` int NOT NULL,
  PRIMARY KEY (`event_id`),
  KEY `proposal_id` (`proposal_id`),
  KEY `approval_id` (`approval_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_streak`
--

DROP TABLE IF EXISTS `login_streak`;
CREATE TABLE IF NOT EXISTS `login_streak` (
  `login_streak_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `last_login_date` date NOT NULL,
  `current_streak_days` int NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`login_streak_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `login_streak`
--

INSERT INTO `login_streak` (`login_streak_id`, `user_id`, `last_login_date`, `current_streak_days`, `created_at`, `updated_at`) VALUES
(1, 2, '2026-01-11', 1, '2026-01-11 09:24:43', '2026-01-11 09:24:43');

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `officer`
--

INSERT INTO `officer` (`officer_id`, `name`, `identical_number`, `email`, `phone_number`, `gender`, `user_id`) VALUES
(1, 'Chong', '010203-10-1234', 'Tp082747@mail.apu.edu.my', '012-345 6789', 'male', 1);

-- --------------------------------------------------------

--
-- Table structure for table `post`
--

DROP TABLE IF EXISTS `post`;
CREATE TABLE IF NOT EXISTS `post` (
  `post_id` int NOT NULL,
  `post_subject` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `post_details` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `picture` varchar(255) NOT NULL,
  `student_id` int NOT NULL,
  `officer_id` int NOT NULL,
  `admin_id` int NOT NULL,
  PRIMARY KEY (`post_id`),
  KEY `student_id` (`student_id`),
  KEY `officer_id` (`officer_id`),
  KEY `admin_id` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `proposal`
--

DROP TABLE IF EXISTS `proposal`;
CREATE TABLE IF NOT EXISTS `proposal` (
  `proposal_id` int NOT NULL AUTO_INCREMENT,
  `event_name` varchar(50) NOT NULL,
  `event_description` varchar(500) NOT NULL,
  `date` date NOT NULL,
  `time` varchar(50) NOT NULL,
  `location` varchar(50) NOT NULL,
  `officer_id` int NOT NULL,
  `participant_limit` int DEFAULT NULL,
  `picture` varchar(250) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`proposal_id`),
  KEY `proposal_ibfk_1` (`officer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `proposal`
--

INSERT INTO `proposal` (`proposal_id`, `event_name`, `event_description`, `date`, `time`, `location`, `officer_id`, `participant_limit`, `picture`, `status`) VALUES
(1, 'Eco Friendly Workshop', 'Feel free to join us for protecting our natural environment!!!!', '2026-01-30', '14:25', 'Auditorium 4, Level 3', 1, 80, '1768020158_eco workshop.jpg', 'pending');

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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
  `title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `time_limit` int NOT NULL,
  `picture` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('draft','published','','') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
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
-- Table structure for table `quiz_attempt`
--

DROP TABLE IF EXISTS `quiz_attempt`;
CREATE TABLE IF NOT EXISTS `quiz_attempt` (
  `attempt_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `quiz_id` int NOT NULL,
  `score` decimal(5,2) NOT NULL,
  `quiz_completed` enum('Completed','Not Completed','','') NOT NULL,
  `attempted_count` int NOT NULL,
  `attempted_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`attempt_id`),
  KEY `user_id` (`user_id`),
  KEY `quiz_id` (`quiz_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `quiz_attempt`
--

INSERT INTO `quiz_attempt` (`attempt_id`, `user_id`, `quiz_id`, `score`, `quiz_completed`, `attempted_count`, `attempted_date`) VALUES
(2, 2, 2, 0.00, 'Not Completed', 1, '2026-01-11 17:25:02');

-- --------------------------------------------------------

--
-- Table structure for table `react_post`
--

DROP TABLE IF EXISTS `react_post`;
CREATE TABLE IF NOT EXISTS `react_post` (
  `react_id` int NOT NULL,
  `reaction_type` enum('like','love','cry','angry') NOT NULL,
  `user_id` int NOT NULL,
  `post_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`react_id`),
  KEY `post_id` (`post_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registration`
--

DROP TABLE IF EXISTS `registration`;
CREATE TABLE IF NOT EXISTS `registration` (
  `registration_id` int NOT NULL,
  `student_id` int NOT NULL,
  `event_id` int NOT NULL,
  `attendance` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`registration_id`),
  KEY `event_id` (`event_id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `report`
--

DROP TABLE IF EXISTS `report`;
CREATE TABLE IF NOT EXISTS `report` (
  `report_id` int NOT NULL,
  `report_details` varchar(250) NOT NULL,
  `student_id` int NOT NULL,
  `post_id` int NOT NULL,
  PRIMARY KEY (`report_id`),
  KEY `student_id` (`student_id`),
  KEY `post_id` (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `smart_tips`
--

DROP TABLE IF EXISTS `smart_tips`;
CREATE TABLE IF NOT EXISTS `smart_tips` (
  `tip_id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `thumbnail` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_by` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`tip_id`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `smart_tips`
--

INSERT INTO `smart_tips` (`tip_id`, `title`, `content`, `thumbnail`, `created_by`, `created_at`) VALUES
(1, 'Turn off lights', 'Turn off lights when not in use to save energy.', 'tip_69637ee677588.jpg', 1, '2025-01-01 10:00:00'),
(2, 'Unplug devices', 'Unplug chargers and electronics when they are fully charged or not in use.', 'tip_69637ebb1e486.jpg', 1, '2025-01-02 14:30:00');

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `role` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `username`, `password`, `role`) VALUES
(1, 'TP082747', '$2y$12$yqzGY4Dg0UXzHVRg95dPf.c1BGBilj9E2yP.9fLJvz.fhYI14EB1y', 'officer'),
(2, 'TP083719', '$2y$12$yqzGY4Dg0UXzHVRg95dPf.c1BGBilj9E2yP.9fLJvz.fhYI14EB1y', 'student'),
(3, 'TP083259', '$2y$12$yqzGY4Dg0UXzHVRg95dPf.c1BGBilj9E2yP.9fLJvz.fhYI14EB1y', 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `user_achievement`
--

DROP TABLE IF EXISTS `user_achievement`;
CREATE TABLE IF NOT EXISTS `user_achievement` (
  `user_achievement_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `achievement_id` int NOT NULL,
  `date_awarded` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_achievement_id`),
  KEY `user_id` (`user_id`),
  KEY `achievement_id` (`achievement_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user_achievement`
--

INSERT INTO `user_achievement` (`user_achievement_id`, `user_id`, `achievement_id`, `date_awarded`) VALUES
(1, 2, 2, '2026-01-11 17:27:20');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `admin_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `approval`
--
ALTER TABLE `approval`
  ADD CONSTRAINT `approval_ibfk_1` FOREIGN KEY (`proposal_id`) REFERENCES `proposal` (`proposal_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `approval_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `approval_ibfk_3` FOREIGN KEY (`event_id`) REFERENCES `event` (`event_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `approval_ibfk_4` FOREIGN KEY (`officer_id`) REFERENCES `officer` (`officer_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `comment`
--
ALTER TABLE `comment`
  ADD CONSTRAINT `comment_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `post` (`post_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `comment_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `event`
--
ALTER TABLE `event`
  ADD CONSTRAINT `event_ibfk_1` FOREIGN KEY (`proposal_id`) REFERENCES `proposal` (`proposal_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `event_ibfk_2` FOREIGN KEY (`approval_id`) REFERENCES `approval` (`approval_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `login_streak`
--
ALTER TABLE `login_streak`
  ADD CONSTRAINT `login_streak_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `officer`
--
ALTER TABLE `officer`
  ADD CONSTRAINT `officer_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `post`
--
ALTER TABLE `post`
  ADD CONSTRAINT `post_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `post_ibfk_2` FOREIGN KEY (`officer_id`) REFERENCES `officer` (`officer_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `post_ibfk_3` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `proposal`
--
ALTER TABLE `proposal`
  ADD CONSTRAINT `proposal_ibfk_1` FOREIGN KEY (`officer_id`) REFERENCES `officer` (`officer_id`) ON DELETE CASCADE ON UPDATE CASCADE;

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
-- Constraints for table `quiz_attempt`
--
ALTER TABLE `quiz_attempt`
  ADD CONSTRAINT `quiz_attempt_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `quiz_attempt_ibfk_2` FOREIGN KEY (`quiz_id`) REFERENCES `quiz` (`quiz_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `smart_tips`
--
ALTER TABLE `smart_tips`
  ADD CONSTRAINT `smart_tips_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_achievement`
--
ALTER TABLE `user_achievement`
  ADD CONSTRAINT `user_achievement_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `user_achievement_ibfk_2` FOREIGN KEY (`achievement_id`) REFERENCES `achievement` (`achievement_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

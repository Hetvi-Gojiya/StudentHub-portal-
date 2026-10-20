-- phpMyAdmin SQL Dump
-- Practical 8: MySQL Schema Design, ER Model, PDO Connectivity, and Prepared Statements

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- 
-- Database: `studenthub_db`
-- 
CREATE DATABASE IF NOT EXISTS `studenthub_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `studenthub_db`;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `event_date` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`title`, `description`, `event_date`) VALUES
('Tech Hackathon 2026', 'A 48-hour coding marathon.', '2026-11-15'),
('Cultural Fest', 'Annual college cultural festival.', '2026-12-05');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mobile` varchar(15) NOT NULL,
  `password` varchar(255) NOT NULL,
  `course` varchar(50) NOT NULL,
  `year` int(11) NOT NULL,
  `gender` varchar(10) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'student',
  `last_login` datetime DEFAULT NULL,
  `remember_token` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`full_name`, `email`, `mobile`, `password`, `course`, `year`, `gender`, `role`) VALUES
('Hetvi', 'hetvi90233@gmail.com', '9023301088', '$2y$10$wT0Xh9M9tT1f0u8.G6cXeO7X9W1.0.Y5Y10z.K4A7a1C.8E1k2sOe', 'B.Tech CE', 2, 'Female', 'student'),
('System Admin', 'admin@studenthub.com', '9999999999', '$2y$10$n2O7XFm9yR.0A1b2C3d4eOX1Z.9P0Q1R2S3T4U5V6W7X8Y9Z0a1b2', 'Admin', 0, 'Other', 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `registrations`
--

CREATE TABLE `registrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `registered_on` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `event_id` (`event_id`),
  CONSTRAINT `fk_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `registrations`
--

INSERT INTO `registrations` (`student_id`, `event_id`) VALUES
(1, 1);


COMMIT;

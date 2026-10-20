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
  `venue` varchar(100) NOT NULL DEFAULT 'Main Auditorium',
  `category` varchar(50) NOT NULL DEFAULT 'Technical',
  `status` varchar(20) NOT NULL DEFAULT 'open',
  `poster` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `events` (Practical 12)
--

INSERT INTO `events` (`title`, `description`, `event_date`, `venue`, `category`, `status`, `poster`) VALUES
('CodeStorm Hackathon 2026', 'A 36-hour national coding and algorithmic problem-solving hackathon.', '2026-11-20', 'Lab 301 & Innovation Hall', 'Technical', 'open', 'codestorm.png'),
('Spandan Cultural Fest', 'Annual inter-college cultural fest featuring music, drama, dance, and arts.', '2026-12-10', 'Central Open Air Theatre', 'Cultural', 'open', 'cultural.png'),
('RoboWar Championship', 'Battle of autonomous and remote-controlled combat robots.', '2026-11-25', 'Indoor Sports Complex', 'Technical', 'open', 'robowar.png'),
('Tech Talks: AI & Cloud Future', 'Expert panel discussion on Generative AI, MLOps, and Cloud Scalability.', '2026-10-15', 'Seminar Hall B', 'Seminar', 'closed', 'techtalk.png'),
('Campus Football League', 'Inter-department 7-a-side football tournament for engineering students.', '2026-11-05', 'University Sports Ground', 'Sports', 'open', 'football.png');

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
  `status` varchar(10) NOT NULL DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `remember_token` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `students` (Practical 11: Minimum 20 records)
--

INSERT INTO `students` (`full_name`, `email`, `mobile`, `password`, `course`, `year`, `gender`, `role`, `status`) VALUES
('System Admin', 'admin@studenthub.com', '9999999999', '$2y$10$mh2i7GiMsMyjAij/c8fEdObri5DlOGM0Y3FhbeR9PHEpS0bvTBVS.', 'Admin', 0, 'Other', 'admin', 'active'),
('Hetvi Gojiya', 'hetvi90233@gmail.com', '9023301088', '$2y$10$wT0Xh9M9tT1f0u8.G6cXeO7X9W1.0.Y5Y10z.K4A7a1C.8E1k2sOe', 'B.Tech CE', 2, 'Female', 'student', 'active'),
('Aarav Sharma', 'aarav.sharma@example.com', '9876543210', '$2y$10$wT0Xh9M9tT1f0u8.G6cXeO7X9W1.0.Y5Y10z.K4A7a1C.8E1k2sOe', 'B.Tech CE', 2, 'Male', 'student', 'active'),
('Diya Patel', 'diya.patel@example.com', '9823456781', '$2y$10$wT0Xh9M9tT1f0u8.G6cXeO7X9W1.0.Y5Y10z.K4A7a1C.8E1k2sOe', 'B.Tech IT', 3, 'Female', 'student', 'active'),
('Rohan Mehta', 'rohan.mehta@example.com', '9812345672', '$2y$10$wT0Xh9M9tT1f0u8.G6cXeO7X9W1.0.Y5Y10z.K4A7a1C.8E1k2sOe', 'B.Tech CSE', 1, 'Male', 'student', 'active'),
('Ananya Joshi', 'ananya.j@example.com', '9890123453', '$2y$10$wT0Xh9M9tT1f0u8.G6cXeO7X9W1.0.Y5Y10z.K4A7a1C.8E1k2sOe', 'B.Tech IT', 2, 'Female', 'student', 'active'),
('Kavya Shah', 'kavya.shah@example.com', '9765432104', '$2y$10$wT0Xh9M9tT1f0u8.G6cXeO7X9W1.0.Y5Y10z.K4A7a1C.8E1k2sOe', 'B.Tech CE', 3, 'Female', 'student', 'active'),
('Devansh Dave', 'devansh.d@example.com', '9754321095', '$2y$10$wT0Xh9M9tT1f0u8.G6cXeO7X9W1.0.Y5Y10z.K4A7a1C.8E1k2sOe', 'B.Tech CSE', 4, 'Male', 'student', 'active'),
('Pooja Trivedi', 'pooja.t@example.com', '9743210986', '$2y$10$wT0Xh9M9tT1f0u8.G6cXeO7X9W1.0.Y5Y10z.K4A7a1C.8E1k2sOe', 'B.Tech IT', 2, 'Female', 'student', 'active'),
('Siddharth Vora', 'sid.vora@example.com', '9732109877', '$2y$10$wT0Xh9M9tT1f0u8.G6cXeO7X9W1.0.Y5Y10z.K4A7a1C.8E1k2sOe', 'B.Tech CE', 1, 'Male', 'student', 'active'),
('Isha Desai', 'isha.desai@example.com', '9721098768', '$2y$10$wT0Xh9M9tT1f0u8.G6cXeO7X9W1.0.Y5Y10z.K4A7a1C.8E1k2sOe', 'B.Tech CSE', 3, 'Female', 'student', 'active'),
('Manan Bhatt', 'manan.b@example.com', '9710987659', '$2y$10$wT0Xh9M9tT1f0u8.G6cXeO7X9W1.0.Y5Y10z.K4A7a1C.8E1k2sOe', 'B.Tech CE', 4, 'Male', 'student', 'active'),
('Tanvi Rana', 'tanvi.rana@example.com', '9698765430', '$2y$10$wT0Xh9M9tT1f0u8.G6cXeO7X9W1.0.Y5Y10z.K4A7a1C.8E1k2sOe', 'B.Tech IT', 2, 'Female', 'student', 'active'),
('Aditya Panchal', 'aditya.p@example.com', '9687654321', '$2y$10$wT0Xh9M9tT1f0u8.G6cXeO7X9W1.0.Y5Y10z.K4A7a1C.8E1k2sOe', 'B.Tech CSE', 1, 'Male', 'student', 'active'),
('Riddhi Parekh', 'riddhi.p@example.com', '9676543212', '$2y$10$wT0Xh9M9tT1f0u8.G6cXeO7X9W1.0.Y5Y10z.K4A7a1C.8E1k2sOe', 'B.Tech CE', 3, 'Female', 'student', 'active'),
('Yash Thakkar', 'yash.t@example.com', '9665432103', '$2y$10$wT0Xh9M9tT1f0u8.G6cXeO7X9W1.0.Y5Y10z.K4A7a1C.8E1k2sOe', 'B.Tech IT', 4, 'Male', 'student', 'active'),
('Khushi Soni', 'khushi.soni@example.com', '9654321094', '$2y$10$wT0Xh9M9tT1f0u8.G6cXeO7X9W1.0.Y5Y10z.K4A7a1C.8E1k2sOe', 'B.Tech CSE', 2, 'Female', 'student', 'active'),
('Harsh Makwana', 'harsh.m@example.com', '9643210985', '$2y$10$wT0Xh9M9tT1f0u8.G6cXeO7X9W1.0.Y5Y10z.K4A7a1C.8E1k2sOe', 'B.Tech CE', 1, 'Male', 'student', 'active'),
('Bhavya Rawal', 'bhavya.r@example.com', '9632109876', '$2y$10$wT0Xh9M9tT1f0u8.G6cXeO7X9W1.0.Y5Y10z.K4A7a1C.8E1k2sOe', 'B.Tech IT', 3, 'Female', 'student', 'active'),
('Jaydeep Gohil', 'jaydeep.g@example.com', '9621098767', '$2y$10$wT0Xh9M9tT1f0u8.G6cXeO7X9W1.0.Y5Y10z.K4A7a1C.8E1k2sOe', 'B.Tech CSE', 4, 'Male', 'student', 'active'),
('Nidhi Rathod', 'nidhi.r@example.com', '9610987658', '$2y$10$wT0Xh9M9tT1f0u8.G6cXeO7X9W1.0.Y5Y10z.K4A7a1C.8E1k2sOe', 'B.Tech CE', 2, 'Female', 'student', 'active'),
('Varun Parmar', 'varun.p@example.com', '9598765439', '$2y$10$wT0Xh9M9tT1f0u8.G6cXeO7X9W1.0.Y5Y10z.K4A7a1C.8E1k2sOe', 'B.Tech IT', 1, 'Male', 'student', 'inactive'),
('Meera Vyas', 'meera.vyas@example.com', '9587654320', '$2y$10$wT0Xh9M9tT1f0u8.G6cXeO7X9W1.0.Y5Y10z.K4A7a1C.8E1k2sOe', 'B.Tech CSE', 3, 'Female', 'student', 'active');

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

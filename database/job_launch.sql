-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 25, 2025 at 08:56 PM
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
-- Database: `job_launch`
--

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `app_id` int(11) NOT NULL,
  `job_id` int(11) DEFAULT NULL,
  `seeker_id` int(11) DEFAULT NULL,
  `resumee` varchar(255) DEFAULT NULL,
  `job_status` enum('pending','accepted','rejected','viewed') DEFAULT 'pending',
  `cover_letter` text DEFAULT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `skill_level` enum('Entry','Junior','Mid','Senior') DEFAULT NULL,
  `fullname` varchar(100) DEFAULT NULL,
  `expected_salary` int(50) DEFAULT NULL,
  `telegram` varchar(100) DEFAULT NULL,
  `portfolio` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`app_id`, `job_id`, `seeker_id`, `resumee`, `job_status`, `cover_letter`, `applied_at`, `skill_level`, `fullname`, `expected_salary`, `telegram`, `portfolio`) VALUES
(2, 1, 8, 'https://resume.com', 'pending', 'i got you', '2025-12-25 19:21:50', 'Junior', 'Julas', 20000, '@Jul4s', 'https://julas.vercel.app'),
(3, 1, NULL, 'https://resume.com', 'pending', 'hellow', '2025-12-25 19:39:36', 'Senior', 'Julas', 20000, '@Jul4s', 'https://julas.vercel.app');

-- --------------------------------------------------------

--
-- Table structure for table `company`
--

CREATE TABLE `company` (
  `company_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `company_name` varchar(150) DEFAULT NULL,
  `contact_name` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `representative` enum('General Manager','representative') DEFAULT NULL,
  `location` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `company`
--

INSERT INTO `company` (`company_id`, `user_id`, `company_name`, `contact_name`, `description`, `representative`, `location`) VALUES
(10, 17, 'faniel', 'negasi', 'general', 'General Manager', 'mekelle'),
(11, 20, 'julas', 'julasc', 'hrManager', 'representative', 'Ethiopia');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `job_id` int(11) NOT NULL,
  `company_id` int(11) DEFAULT NULL,
  `title` varchar(150) DEFAULT NULL,
  `descriptions` text DEFAULT NULL,
  `job_type` enum('Full-time','Part-time','Internship') DEFAULT NULL,
  `locatons` varchar(100) DEFAULT NULL,
  `salary` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `requirements` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`job_id`, `company_id`, `title`, `descriptions`, `job_type`, `locatons`, `salary`, `created_at`, `requirements`) VALUES
(1, 11, 'Graphic Design', 'We need an expreieced Desginer', '', 'Ethiopia, Tigray, Meklle', 'Negotialble', '2025-12-25 17:56:47', '3 years of experience in Professional Graphics design works either as an enterpneur or an employee');

-- --------------------------------------------------------

--
-- Table structure for table `job_seeker`
--

CREATE TABLE `job_seeker` (
  `seeker_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `fullname` varchar(100) DEFAULT NULL,
  `profession_title` varchar(100) DEFAULT NULL,
  `skill_level` enum('Bignner','Intermidate','Advanced') DEFAULT NULL,
  `city` varchar(150) DEFAULT NULL,
  `primary_interest` varchar(100) DEFAULT NULL,
  `bio` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_seeker`
--

INSERT INTO `job_seeker` (`seeker_id`, `user_id`, `fullname`, `profession_title`, `skill_level`, `city`, `primary_interest`, `bio`) VALUES
(7, 16, 'Faniel Negasi', 'web developer', 'Advanced', 'mekelle', 'front_end', 'faniel.vercel'),
(8, 18, 'tsgabu', 'devv', '', 'mekelle', 'frontend', 'Dev'),
(9, 19, 'julas', 'front', 'Advanced', 'mekelle', 'graphic julas', 'jhkjhksdlkhf');

-- --------------------------------------------------------

--
-- Table structure for table `skills`
--

CREATE TABLE `skills` (
  `skill_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `skill_name` varchar(100) DEFAULT NULL,
  `skill_levle` enum('Beginner','Intermidate','Advanced') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password_hash` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `roles` enum('job_seeker','company') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `email`, `password_hash`, `created_at`, `roles`) VALUES
(16, 'fanielnegasi123@gmail.com', '$2y$10$vd5odqHHpB1ydvU2c/1rluVi5o31mrCspJx3Xj2NxQJkl7.yfH8Gq', '2025-12-25 10:33:09', 'job_seeker'),
(17, 'fanielnegasi1234@gmail.com', '$2y$10$8LWQaVtCI18WQsiPKlh/behNCefE0m2zwYtwvdj/9L54IuuLg1.8u', '2025-12-25 10:41:17', 'company'),
(18, 'tgbal222@gmail.com', '$2y$10$S3C/wb31a2oukiaBK1kPaeM8wckgmVp.ENVNELP6bktizBBffFPqK', '2025-12-25 13:31:35', 'job_seeker'),
(19, 'julas@gmail.com', '$2y$10$YjyFgp1DtenI2sDTdnX8f.lKveH8Zh56Vy7KcotH6TU2xUONZTbzi', '2025-12-25 14:39:59', 'job_seeker'),
(20, 'julasmame@gmail.com', '$2y$10$ypR59wwhKzCaD8MKnw7yneWCcCZXB6M7X7Tka2.bxYusp6yjkJ9AO', '2025-12-25 17:25:37', 'company');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`app_id`),
  ADD KEY `job_id` (`job_id`),
  ADD KEY `seeker_id` (`seeker_id`);

--
-- Indexes for table `company`
--
ALTER TABLE `company`
  ADD PRIMARY KEY (`company_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`job_id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `job_seeker`
--
ALTER TABLE `job_seeker`
  ADD PRIMARY KEY (`seeker_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `skills`
--
ALTER TABLE `skills`
  ADD PRIMARY KEY (`skill_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `app_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `company`
--
ALTER TABLE `company`
  MODIFY `company_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `job_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `job_seeker`
--
ALTER TABLE `job_seeker`
  MODIFY `seeker_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `skills`
--
ALTER TABLE `skills`
  MODIFY `skill_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`job_id`),
  ADD CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`seeker_id`) REFERENCES `job_seeker` (`seeker_id`);

--
-- Constraints for table `company`
--
ALTER TABLE `company`
  ADD CONSTRAINT `company_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `jobs`
--
ALTER TABLE `jobs`
  ADD CONSTRAINT `jobs_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `company` (`company_id`);

--
-- Constraints for table `job_seeker`
--
ALTER TABLE `job_seeker`
  ADD CONSTRAINT `job_seeker_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `skills`
--
ALTER TABLE `skills`
  ADD CONSTRAINT `skills_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

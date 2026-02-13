-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Feb 13, 2026 at 11:48 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dzsepetto_local`
--

-- --------------------------------------------------------

--
-- Table structure for table `answer_option`
--

CREATE TABLE `answer_option` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `question_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `label` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `is_correct` tinyint(1) NOT NULL,
  `order_index` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `answer_option`
--

INSERT INTO `answer_option` (`id`, `question_id`, `label`, `is_correct`, `order_index`) VALUES
('784b2695-076a-11f1-af60-00059a3c7a00', '784ae71d-076a-11f1-af60-00059a3c7a00', 'igen', 1, 1),
('784b630f-076a-11f1-af60-00059a3c7a00', '784ae71d-076a-11f1-af60-00059a3c7a00', 'nem', 0, 2),
('fe50ff58-0814-11f1-af60-00059a3c7a00', 'fe50c173-0814-11f1-af60-00059a3c7a00', 'Igen', 1, 1),
('fe513944-0814-11f1-af60-00059a3c7a00', 'fe50c173-0814-11f1-af60-00059a3c7a00', 'Nem', 0, 2);

-- --------------------------------------------------------

--
-- Table structure for table `matching_left_item`
--

CREATE TABLE `matching_left_item` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `question_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `order_index` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `matching_left_item`
--

INSERT INTO `matching_left_item` (`id`, `question_id`, `text`, `order_index`) VALUES
('fe5173fe-0814-11f1-af60-00059a3c7a00', 'fe515954-0814-11f1-af60-00059a3c7a00', 'alekosz', 1),
('fe51ccbc-0814-11f1-af60-00059a3c7a00', 'fe515954-0814-11f1-af60-00059a3c7a00', 'Péter', 2);

-- --------------------------------------------------------

--
-- Table structure for table `matching_pair`
--

CREATE TABLE `matching_pair` (
  `id` char(36) NOT NULL,
  `question_id` char(36) NOT NULL,
  `left_id` char(36) NOT NULL,
  `right_id` char(36) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `matching_pair`
--

INSERT INTO `matching_pair` (`id`, `question_id`, `left_id`, `right_id`) VALUES
('fe51aad9-0814-11f1-af60-00059a3c7a00', 'fe515954-0814-11f1-af60-00059a3c7a00', 'fe5173fe-0814-11f1-af60-00059a3c7a00', 'fe518f6e-0814-11f1-af60-00059a3c7a00'),
('fe52053d-0814-11f1-af60-00059a3c7a00', 'fe515954-0814-11f1-af60-00059a3c7a00', 'fe51ccbc-0814-11f1-af60-00059a3c7a00', 'fe51eb37-0814-11f1-af60-00059a3c7a00');

-- --------------------------------------------------------

--
-- Table structure for table `matching_right_item`
--

CREATE TABLE `matching_right_item` (
  `id` char(36) NOT NULL,
  `question_id` char(36) NOT NULL,
  `text` text NOT NULL,
  `order_index` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `matching_right_item`
--

INSERT INTO `matching_right_item` (`id`, `question_id`, `text`, `order_index`) VALUES
('fe518f6e-0814-11f1-af60-00059a3c7a00', 'fe515954-0814-11f1-af60-00059a3c7a00', 'nagy', 1),
('fe51eb37-0814-11f1-af60-00059a3c7a00', 'fe515954-0814-11f1-af60-00059a3c7a00', 'Hajdú', 2);

-- --------------------------------------------------------

--
-- Table structure for table `question`
--

CREATE TABLE `question` (
  `id` char(36) NOT NULL,
  `quiz_id` char(36) NOT NULL,
  `type` varchar(20) NOT NULL,
  `question_text` text NOT NULL,
  `order_index` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `question`
--

INSERT INTO `question` (`id`, `quiz_id`, `type`, `question_text`, `order_index`, `created_at`) VALUES
('784ae71d-076a-11f1-af60-00059a3c7a00', '7849852f-076a-11f1-af60-00059a3c7a00', 'MULTIPLE_CHOICE', 'ez igaz?', 1, '2026-02-11 16:55:29'),
('fe50c173-0814-11f1-af60-00059a3c7a00', '5d59c8e7-0775-11f1-af60-00059a3c7a00', 'MULTIPLE_CHOICE', 'alekosz gróf?', 1, '2026-02-12 13:16:08'),
('fe515954-0814-11f1-af60-00059a3c7a00', '5d59c8e7-0775-11f1-af60-00059a3c7a00', 'MATCHING', 'Párosítsd a vezetékneveket', 2, '2026-02-12 13:16:08');

-- --------------------------------------------------------

--
-- Table structure for table `quiz`
--

CREATE TABLE `quiz` (
  `id` char(36) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `difficulty` smallint DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT '1',
  `language_code` varchar(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `quiz`
--

INSERT INTO `quiz` (`id`, `slug`, `title`, `description`, `difficulty`, `is_published`, `created_at`, `updated_at`, `created_by`, `is_public`, `language_code`) VALUES
('5d59c8e7-0775-11f1-af60-00059a3c7a00', 'alekosz-1-161058', 'Alekosz 1', 'alekosz a hős', NULL, 1, '2026-02-11 18:13:28', '2026-02-11 18:13:28', 1, 1, 'hu'),
('7849852f-076a-11f1-af60-00059a3c7a00', 'test-quiz-64f789', 'test quiz', 'valami', NULL, 1, '2026-02-11 16:55:29', '2026-02-11 16:55:29', 1, 0, 'hu');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_attempt`
--

CREATE TABLE `quiz_attempt` (
  `id` char(36) NOT NULL DEFAULT (uuid()),
  `quiz_id` char(36) NOT NULL,
  `user_id` int NOT NULL,
  `score` int NOT NULL,
  `max_score` int NOT NULL,
  `duration_sec` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `quiz_attempt`
--

INSERT INTO `quiz_attempt` (`id`, `quiz_id`, `user_id`, `score`, `max_score`, `duration_sec`, `created_at`) VALUES
('6f80898b-08cb-11f1-af60-00059a3c7a00', 'alekosz-1-161058', 1, 2, 2, NULL, '2026-02-13 12:02:06');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_viewer_email`
--

CREATE TABLE `quiz_viewer_email` (
  `quiz_id` char(36) NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `quiz_viewer_email`
--

INSERT INTO `quiz_viewer_email` (`quiz_id`, `user_email`, `created_at`) VALUES
('7601daf4-074a-11f1-af60-00059a3c7a00', 'pinterbence2002@gmail.com', '2026-02-11 13:06:21'),
('ab0e8346-074a-11f1-af60-00059a3c7a00', 'pinterbence2002@gmail.com', '2026-02-11 13:07:50'),
('7849852f-076a-11f1-af60-00059a3c7a00', 'pinterbence2002@gmail.com', '2026-02-11 16:55:29'),
('adee780c-074a-11f1-af60-00059a3c7a00', 'pinterbence2002@gmail.com', '2026-02-11 17:26:39'),
('adee780c-074a-11f1-af60-00059a3c7a00', 'valami@valami.hu', '2026-02-11 17:26:39'),
('adee780c-074a-11f1-af60-00059a3c7a00', 'pinterbence2002@gmail.com', '2026-02-11 17:26:39');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_hungarian_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_hungarian_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `name`, `created_at`) VALUES
(1, 'pinterbence2002@gmail.com', 'pintér benedek', '2026-02-10 17:11:36');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `answer_option`
--
ALTER TABLE `answer_option`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_ANSWER_QUESTION` (`question_id`);

--
-- Indexes for table `matching_left_item`
--
ALTER TABLE `matching_left_item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_MATCH_LEFT_QUESTION` (`question_id`);

--
-- Indexes for table `matching_pair`
--
ALTER TABLE `matching_pair`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_MATCH_PAIR_QUESTION` (`question_id`);

--
-- Indexes for table `matching_right_item`
--
ALTER TABLE `matching_right_item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_MATCH_RIGHT_QUESTION` (`question_id`);

--
-- Indexes for table `question`
--
ALTER TABLE `question`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_QUESTION_QUIZ` (`quiz_id`);

--
-- Indexes for table `quiz`
--
ALTER TABLE `quiz`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `quiz_attempt`
--
ALTER TABLE `quiz_attempt`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_quiz` (`user_id`,`quiz_id`),
  ADD KEY `IDX_ATTEMPT_QUIZ` (`quiz_id`);

--
-- Indexes for table `quiz_viewer_email`
--
ALTER TABLE `quiz_viewer_email`
  ADD KEY `IDX_VIEWER_QUIZ` (`quiz_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

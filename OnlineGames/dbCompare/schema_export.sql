-- Schema export
-- Database: `dzsepetto_local_quiz`
-- Generated at: 2026-04-15 10:06:07

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';

CREATE DATABASE IF NOT EXISTS `dzsepetto_local_quiz` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `dzsepetto_local_quiz`;

-- --------------------------------------------------------
-- Table structure for table `answer_option`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `answer_option`;
CREATE TABLE `answer_option` (
  `id` char(36) NOT NULL,
  `question_id` char(36) NOT NULL,
  `label` text NOT NULL,
  `is_correct` tinyint(1) NOT NULL,
  `order_index` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_ANSWER_QUESTION` (`question_id`),
  CONSTRAINT `FK_ANSWER_OPTION_QUESTION` FOREIGN KEY (`question_id`) REFERENCES `question` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------
-- Table structure for table `game_answers`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `game_answers`;
CREATE TABLE `game_answers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `game_id` varchar(6) DEFAULT NULL,
  `player_id` int DEFAULT NULL,
  `question_id` char(36) DEFAULT NULL,
  `answer_id` char(36) DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT NULL,
  `answered_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `game_id` (`game_id`),
  KEY `player_id` (`player_id`),
  KEY `question_id` (`question_id`),
  KEY `answer_id` (`answer_id`),
  CONSTRAINT `game_answers_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `game_sessions` (`id`),
  CONSTRAINT `game_answers_ibfk_2` FOREIGN KEY (`player_id`) REFERENCES `game_players` (`id`),
  CONSTRAINT `game_answers_ibfk_3` FOREIGN KEY (`question_id`) REFERENCES `question` (`id`),
  CONSTRAINT `game_answers_ibfk_4` FOREIGN KEY (`answer_id`) REFERENCES `answer_option` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------
-- Table structure for table `game_players`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `game_players`;
CREATE TABLE `game_players` (
  `id` int NOT NULL AUTO_INCREMENT,
  `game_id` varchar(6) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `score` int DEFAULT '0',
  `joined_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `game_id` (`game_id`),
  CONSTRAINT `game_players_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `game_sessions` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------
-- Table structure for table `game_sessions`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `game_sessions`;
CREATE TABLE `game_sessions` (
  `id` varchar(6) NOT NULL,
  `quiz_id` char(36) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `current_question_index` int DEFAULT '0',
  `question_started_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `quiz_id` (`quiz_id`),
  CONSTRAINT `game_sessions_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `quiz` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------
-- Table structure for table `matching_left_item`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `matching_left_item`;
CREATE TABLE `matching_left_item` (
  `id` char(36) NOT NULL,
  `question_id` char(36) NOT NULL,
  `text` text NOT NULL,
  `order_index` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_MATCH_LEFT_QUESTION` (`question_id`),
  CONSTRAINT `FK_LEFT_QUESTION` FOREIGN KEY (`question_id`) REFERENCES `question` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------
-- Table structure for table `matching_pair`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `matching_pair`;
CREATE TABLE `matching_pair` (
  `id` char(36) NOT NULL,
  `question_id` char(36) NOT NULL,
  `left_id` char(36) NOT NULL,
  `right_id` char(36) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_MATCH_PAIR_QUESTION` (`question_id`),
  KEY `FK_PAIR_LEFT` (`left_id`),
  KEY `FK_PAIR_RIGHT` (`right_id`),
  CONSTRAINT `FK_PAIR_LEFT` FOREIGN KEY (`left_id`) REFERENCES `matching_left_item` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_PAIR_QUESTION` FOREIGN KEY (`question_id`) REFERENCES `question` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_PAIR_RIGHT` FOREIGN KEY (`right_id`) REFERENCES `matching_right_item` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------
-- Table structure for table `matching_right_item`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `matching_right_item`;
CREATE TABLE `matching_right_item` (
  `id` char(36) NOT NULL,
  `question_id` char(36) NOT NULL,
  `text` text NOT NULL,
  `order_index` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_MATCH_RIGHT_QUESTION` (`question_id`),
  CONSTRAINT `FK_RIGHT_QUESTION` FOREIGN KEY (`question_id`) REFERENCES `question` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------
-- Table structure for table `question`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `question`;
CREATE TABLE `question` (
  `id` char(36) NOT NULL,
  `quiz_id` char(36) NOT NULL,
  `type` varchar(20) NOT NULL,
  `question_text` text NOT NULL,
  `order_index` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `IDX_QUESTION_QUIZ` (`quiz_id`),
  CONSTRAINT `FK_QUESTION_QUIZ` FOREIGN KEY (`quiz_id`) REFERENCES `quiz` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------
-- Table structure for table `quiz`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `quiz`;
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
  `language_code` varchar(5) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_QUIZ_USER` (`created_by`),
  CONSTRAINT `FK_QUIZ_USER` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------
-- Table structure for table `quiz_attempt`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `quiz_attempt`;
CREATE TABLE `quiz_attempt` (
  `id` char(36) NOT NULL,
  `quiz_id` char(36) NOT NULL,
  `user_id` int NOT NULL,
  `score` int NOT NULL,
  `max_score` int NOT NULL,
  `duration_sec` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_quiz` (`user_id`,`quiz_id`),
  KEY `IDX_ATTEMPT_QUIZ` (`quiz_id`),
  CONSTRAINT `FK_QA_QUIZ` FOREIGN KEY (`quiz_id`) REFERENCES `quiz` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_QA_USER` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------
-- Table structure for table `quiz_viewer_email`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `quiz_viewer_email`;
CREATE TABLE `quiz_viewer_email` (
  `quiz_id` char(36) NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `IDX_VIEWER_QUIZ` (`quiz_id`),
  CONSTRAINT `FK_VIEWER_QUIZ` FOREIGN KEY (`quiz_id`) REFERENCES `quiz` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8mb4_hungarian_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_hungarian_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

SET FOREIGN_KEY_CHECKS=1;

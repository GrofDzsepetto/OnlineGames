-- phpMyAdmin SQL Dump
-- version 4.4.15.5
-- http://www.phpmyadmin.net
--
-- Gép: localhost
-- Létrehozás ideje: 2026. Feb 13. 12:48
-- Kiszolgáló verziója: 11.8.3-MariaDB-deb12
-- PHP verzió: 7.1.33-68+0~20250707.110+debian12~1.gbp5b05bb

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Adatbázis: `dzsepetto_online_quiz`
--

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `answer_option`
--

CREATE TABLE IF NOT EXISTS `answer_option` (
  `id` uuid NOT NULL,
  `question_id` uuid NOT NULL,
  `label` text NOT NULL,
  `is_correct` tinyint(1) NOT NULL,
  `order_index` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- A tábla adatainak kiíratása `answer_option`
--

INSERT INTO `answer_option` (`id`, `question_id`, `label`, `is_correct`, `order_index`) VALUES
('dc5439c0-033b-11f1-a712-7cc255ac7256', 'dc543705-033b-11f1-a712-7cc255ac7256', 'Igen', 1, 1),
('dc543ba6-033b-11f1-a712-7cc255ac7256', 'dc543705-033b-11f1-a712-7cc255ac7256', 'Nem', 0, 2),
('dc543f2a-033b-11f1-a712-7cc255ac7256', 'dc543d68-033b-11f1-a712-7cc255ac7256', 'Gróf', 0, 1),
('dc5440b3-033b-11f1-a712-7cc255ac7256', 'dc543d68-033b-11f1-a712-7cc255ac7256', 'Focista', 1, 2),
('dc544237-033b-11f1-a712-7cc255ac7256', 'dc543d68-033b-11f1-a712-7cc255ac7256', 'Karinthy Gyűrű várományosa', 0, 3),
('dc5443bc-033b-11f1-a712-7cc255ac7256', 'dc543d68-033b-11f1-a712-7cc255ac7256', 'testépítő', 0, 4),
('dc54593f-033b-11f1-a712-7cc255ac7256', 'dc545794-033b-11f1-a712-7cc255ac7256', 'Igen', 1, 1),
('dc545acc-033b-11f1-a712-7cc255ac7256', 'dc545794-033b-11f1-a712-7cc255ac7256', 'Nem', 0, 2),
('dc545dd0-033b-11f1-a712-7cc255ac7256', 'dc545c45-033b-11f1-a712-7cc255ac7256', 'Nyugdíjasok', 0, 1),
('dc545f44-033b-11f1-a712-7cc255ac7256', 'dc545c45-033b-11f1-a712-7cc255ac7256', 'Fiatalok', 0, 2),
('dc5460b7-033b-11f1-a712-7cc255ac7256', 'dc545c45-033b-11f1-a712-7cc255ac7256', 'Droidok', 1, 3),
('dc5463b5-033b-11f1-a712-7cc255ac7256', 'dc546224-033b-11f1-a712-7cc255ac7256', 'Indukciós főzőlap', 0, 1),
('dc54652d-033b-11f1-a712-7cc255ac7256', 'dc546224-033b-11f1-a712-7cc255ac7256', 'Golyóálló üveg', 0, 2),
('dc54669d-033b-11f1-a712-7cc255ac7256', 'dc546224-033b-11f1-a712-7cc255ac7256', 'Légkondi', 1, 3),
('0f171877-05e3-11f1-a712-7cc255ac7256', '0f171611-05e3-11f1-a712-7cc255ac7256', 'igen', 0, 1),
('0f171a65-05e3-11f1-a712-7cc255ac7256', '0f171611-05e3-11f1-a712-7cc255ac7256', 'nem', 0, 2),
('ab48d0a5-05f9-11f1-a712-7cc255ac7256', 'ab48ccab-05f9-11f1-a712-7cc255ac7256', 'a', 1, 1),
('ab48d33b-05f9-11f1-a712-7cc255ac7256', 'ab48ccab-05f9-11f1-a712-7cc255ac7256', 'bv', 0, 2);

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `language`
--

CREATE TABLE IF NOT EXISTS `language` (
  `code` varchar(5) NOT NULL,
  `name` varchar(50) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- A tábla adatainak kiíratása `language`
--

INSERT INTO `language` (`code`, `name`, `is_active`) VALUES
('en', 'English', 1),
('hu', 'Hungarian', 1);

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `matching_left_item`
--

CREATE TABLE IF NOT EXISTS `matching_left_item` (
  `id` char(36) NOT NULL,
  `question_id` uuid NOT NULL,
  `text` text NOT NULL,
  `order_index` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- A tábla adatainak kiíratása `matching_left_item`
--

INSERT INTO `matching_left_item` (`id`, `question_id`, `text`, `order_index`) VALUES
('dc5446df-033b-11f1-a712-7cc255ac7256', 'dc544533-033b-11f1-a712-7cc255ac7256', 'Nagy Alekosz', 1),
('dc544f87-033b-11f1-a712-7cc255ac7256', 'dc544533-033b-11f1-a712-7cc255ac7256', 'Hajdú Péter', 2),
('dc546995-033b-11f1-a712-7cc255ac7256', 'dc54680b-033b-11f1-a712-7cc255ac7256', 'ALekosz', 1),
('dc546e55-033b-11f1-a712-7cc255ac7256', 'dc54680b-033b-11f1-a712-7cc255ac7256', 'Önök', 2);

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `matching_pair`
--

CREATE TABLE IF NOT EXISTS `matching_pair` (
  `id` char(36) NOT NULL,
  `question_id` uuid NOT NULL,
  `left_id` char(36) NOT NULL,
  `right_id` char(36) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- A tábla adatainak kiíratása `matching_pair`
--

INSERT INTO `matching_pair` (`id`, `question_id`, `left_id`, `right_id`) VALUES
('dc544a2b-033b-11f1-a712-7cc255ac7256', 'dc544533-033b-11f1-a712-7cc255ac7256', 'dc5446df-033b-11f1-a712-7cc255ac7256', 'dc54488f-033b-11f1-a712-7cc255ac7256'),
('dc544dbe-033b-11f1-a712-7cc255ac7256', 'dc544533-033b-11f1-a712-7cc255ac7256', 'dc5446df-033b-11f1-a712-7cc255ac7256', 'dc544c17-033b-11f1-a712-7cc255ac7256'),
('dc5452a0-033b-11f1-a712-7cc255ac7256', 'dc544533-033b-11f1-a712-7cc255ac7256', 'dc544f87-033b-11f1-a712-7cc255ac7256', 'dc545113-033b-11f1-a712-7cc255ac7256'),
('dc5455e5-033b-11f1-a712-7cc255ac7256', 'dc544533-033b-11f1-a712-7cc255ac7256', 'dc544f87-033b-11f1-a712-7cc255ac7256', 'dc54545a-033b-11f1-a712-7cc255ac7256'),
('dc546ca4-033b-11f1-a712-7cc255ac7256', 'dc54680b-033b-11f1-a712-7cc255ac7256', 'dc546995-033b-11f1-a712-7cc255ac7256', 'dc546b21-033b-11f1-a712-7cc255ac7256'),
('dc547153-033b-11f1-a712-7cc255ac7256', 'dc54680b-033b-11f1-a712-7cc255ac7256', 'dc546e55-033b-11f1-a712-7cc255ac7256', 'dc546fd4-033b-11f1-a712-7cc255ac7256');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `matching_right_item`
--

CREATE TABLE IF NOT EXISTS `matching_right_item` (
  `id` char(36) NOT NULL,
  `question_id` uuid NOT NULL,
  `text` text NOT NULL,
  `order_index` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- A tábla adatainak kiíratása `matching_right_item`
--

INSERT INTO `matching_right_item` (`id`, `question_id`, `text`, `order_index`) VALUES
('dc54488f-033b-11f1-a712-7cc255ac7256', 'dc544533-033b-11f1-a712-7cc255ac7256', 'ITT!!!', 1),
('dc544c17-033b-11f1-a712-7cc255ac7256', 'dc544533-033b-11f1-a712-7cc255ac7256', 'A 3-as portán', 2),
('dc545113-033b-11f1-a712-7cc255ac7256', 'dc544533-033b-11f1-a712-7cc255ac7256', 'Hol vagy most biztonsági őr?', 3),
('dc54545a-033b-11f1-a712-7cc255ac7256', 'dc544533-033b-11f1-a712-7cc255ac7256', 'Itt???', 4),
('dc546b21-033b-11f1-a712-7cc255ac7256', 'dc54680b-033b-11f1-a712-7cc255ac7256', 'Itt fent +', 1),
('dc546fd4-033b-11f1-a712-7cc255ac7256', 'dc54680b-033b-11f1-a712-7cc255ac7256', 'Ott lent', 2);

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `question`
--

CREATE TABLE IF NOT EXISTS `question` (
  `id` uuid NOT NULL,
  `quiz_id` uuid NOT NULL,
  `type` varchar(20) NOT NULL,
  `question_text` text NOT NULL,
  `order_index` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- A tábla adatainak kiíratása `question`
--

INSERT INTO `question` (`id`, `quiz_id`, `type`, `question_text`, `order_index`, `created_at`) VALUES
('dc543705-033b-11f1-a712-7cc255ac7256', '15e02cb2-020d-11f1-a712-7cc255ac7256', 'MULTIPLE_CHOICE', 'Hordhat Alekosz fegyvert utcán?', 1, '2026-02-06 09:11:45'),
('dc543d68-033b-11f1-a712-7cc255ac7256', '15e02cb2-020d-11f1-a712-7cc255ac7256', 'MULTIPLE_CHOICE', 'Milyen titulus nem tartozik Alekoszhoz?', 2, '2026-02-06 09:11:45'),
('dc544533-033b-11f1-a712-7cc255ac7256', '15e02cb2-020d-11f1-a712-7cc255ac7256', 'MATCHING', 'A hires interjúban ki mondta?', 3, '2026-02-06 09:11:45'),
('dc545794-033b-11f1-a712-7cc255ac7256', '15e02cb2-020d-11f1-a712-7cc255ac7256', 'MULTIPLE_CHOICE', 'Nagy Alekosz Gróf?', 4, '2026-02-06 09:11:45'),
('dc545c45-033b-11f1-a712-7cc255ac7256', '15e02cb2-020d-11f1-a712-7cc255ac7256', 'MULTIPLE_CHOICE', 'Kik vásárolnak a pennyben?', 5, '2026-02-06 09:11:45'),
('dc546224-033b-11f1-a712-7cc255ac7256', '15e02cb2-020d-11f1-a712-7cc255ac7256', 'MULTIPLE_CHOICE', 'Milyen háztartási eszközt szereltetett be a népszerűségét kihasználva', 6, '2026-02-06 09:11:45'),
('dc54680b-033b-11f1-a712-7cc255ac7256', '15e02cb2-020d-11f1-a712-7cc255ac7256', 'MATCHING', 'Hol vannak a beszélgetés résztvevői?', 7, '2026-02-06 09:11:45'),
('0f171611-05e3-11f1-a712-7cc255ac7256', 'ca735422-043f-11f1-a712-7cc255ac7256', 'MULTIPLE_CHOICE', 'igen', 1, '2026-02-09 18:13:38'),
('ab48ccab-05f9-11f1-a712-7cc255ac7256', 'ab489ce2-05f9-11f1-a712-7cc255ac7256', 'MULTIPLE_CHOICE', 'a', 1, '2026-02-09 20:55:29');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `quiz`
--

CREATE TABLE IF NOT EXISTS `quiz` (
  `id` uuid NOT NULL,
  `slug` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `difficulty` smallint(6) DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 1,
  `language_code` varchar(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- A tábla adatainak kiíratása `quiz`
--

INSERT INTO `quiz` (`id`, `slug`, `title`, `description`, `difficulty`, `is_published`, `created_at`, `updated_at`, `created_by`, `is_public`, `language_code`) VALUES
('15e02cb2-020d-11f1-a712-7cc255ac7256', 'nagy-alekosz-quick-quiz-728bda', 'Nagy Alekosz quick QUIZ', 'csak okosaknak', NULL, 1, '2026-02-04 21:04:24', '2026-02-04 21:04:24', 1, 1, 'hu'),
('ca735422-043f-11f1-a712-7cc255ac7256', 'teszt2-e92c60', 'TesztQuiz láthatóság', 'valami', NULL, 1, '2026-02-07 16:12:24', '2026-02-07 16:12:24', 1, 0, 'hu'),
('ab489ce2-05f9-11f1-a712-7cc255ac7256', 'asdfas-d4131d', 'asdfas', 'asdfasdf', NULL, 1, '2026-02-09 20:55:29', '2026-02-09 20:55:29', 1, 1, 'hu');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `quiz_attempt`
--

CREATE TABLE IF NOT EXISTS `quiz_attempt` (
  `id` uuid NOT NULL,
  `quiz_id` uuid NOT NULL,
  `user_id` int(11) NOT NULL,
  `score` int(11) NOT NULL,
  `max_score` int(11) NOT NULL,
  `duration_sec` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- A tábla adatainak kiíratása `quiz_attempt`
--

INSERT INTO `quiz_attempt` (`id`, `quiz_id`, `user_id`, `score`, `max_score`, `duration_sec`, `created_at`) VALUES
('4f050f2b-0520-11f1-a712-7cc255ac7256', '15e02cb2-020d-11f1-a712-7cc255ac7256', 1, 7, 7, 0, '2026-02-08 19:59:34'),
('554d9d7c-05e3-11f1-a712-7cc255ac7256', '15e02cb2-020d-11f1-a712-7cc255ac7256', 3, 7, 7, 0, '2026-02-09 19:15:36');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `quiz_viewer_email`
--

CREATE TABLE IF NOT EXISTS `quiz_viewer_email` (
  `quiz_id` uuid NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- A tábla adatainak kiíratása `quiz_viewer_email`
--

INSERT INTO `quiz_viewer_email` (`quiz_id`, `user_email`, `created_at`) VALUES
('ca735422-043f-11f1-a712-7cc255ac7256', 'pinterbence2002@gmail.com', '2026-02-09 18:13:38'),
('ca735422-043f-11f1-a712-7cc255ac7256', 'valami@valami.hu', '2026-02-09 18:13:38'),
('ca735422-043f-11f1-a712-7cc255ac7256', 'valami2@valami.hu', '2026-02-09 18:13:38');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- A tábla adatainak kiíratása `users`
--

INSERT INTO `users` (`id`, `email`, `name`, `created_at`) VALUES
(1, 'pinterbence2002@gmail.com', 'pintér benedek', '2026-02-03 20:28:34'),
(2, '421agnes@gmail.com', 'Ágnes Vitálos', '2026-02-05 22:01:15'),
(3, 'geppo2tv@gmail.com', 'geppo2 tv', '2026-02-09 18:12:43');

--
-- Indexek a kiírt táblákhoz
--

--
-- A tábla indexei `answer_option`
--
ALTER TABLE `answer_option`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_ANSWER_OPTION_QUESTION_ID` (`question_id`);

--
-- A tábla indexei `language`
--
ALTER TABLE `language`
  ADD PRIMARY KEY (`code`);

--
-- A tábla indexei `matching_left_item`
--
ALTER TABLE `matching_left_item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_LEFT_QUESTION` (`question_id`);

--
-- A tábla indexei `matching_pair`
--
ALTER TABLE `matching_pair`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNQ_RIGHT` (`right_id`),
  ADD KEY `IDX_PAIR_QUESTION` (`question_id`),
  ADD KEY `IDX_PAIR_LEFT` (`left_id`),
  ADD KEY `IDX_PAIR_RIGHT` (`right_id`);

--
-- A tábla indexei `matching_right_item`
--
ALTER TABLE `matching_right_item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_RIGHT_QUESTION` (`question_id`);

--
-- A tábla indexei `question`
--
ALTER TABLE `question`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_QUESTION_QUIZ_ID` (`quiz_id`);

--
-- A tábla indexei `quiz`
--
ALTER TABLE `quiz`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `SLUG` (`slug`),
  ADD KEY `FK_QUIZ_LANGUAGE` (`language_code`);

--
-- A tábla indexei `quiz_attempt`
--
ALTER TABLE `quiz_attempt`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UQ_QA_QUIZ_USER` (`quiz_id`,`user_id`),
  ADD KEY `IDX_QA_QUIZ_ID` (`quiz_id`),
  ADD KEY `IDX_QA_USER_ID` (`user_id`),
  ADD KEY `IDX_QA_QUIZ_SCORE` (`quiz_id`,`score`,`duration_sec`,`created_at`);

--
-- A tábla indexei `quiz_viewer_email`
--
ALTER TABLE `quiz_viewer_email`
  ADD PRIMARY KEY (`quiz_id`,`user_email`);

--
-- A tábla indexei `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- A kiírt táblák AUTO_INCREMENT értéke
--

--
-- AUTO_INCREMENT a táblához `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;
--
-- Megkötések a kiírt táblákhoz
--

--
-- Megkötések a táblához `answer_option`
--
ALTER TABLE `answer_option`
  ADD CONSTRAINT `FK_ANSWER_OPTION_QUESTION` FOREIGN KEY (`question_id`) REFERENCES `question` (`id`) ON DELETE CASCADE;

--
-- Megkötések a táblához `matching_left_item`
--
ALTER TABLE `matching_left_item`
  ADD CONSTRAINT `FK_LEFT_QUESTION` FOREIGN KEY (`question_id`) REFERENCES `question` (`id`) ON DELETE CASCADE;

--
-- Megkötések a táblához `matching_pair`
--
ALTER TABLE `matching_pair`
  ADD CONSTRAINT `FK_PAIR_LEFT` FOREIGN KEY (`left_id`) REFERENCES `matching_left_item` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_PAIR_QUESTION` FOREIGN KEY (`question_id`) REFERENCES `question` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_PAIR_RIGHT` FOREIGN KEY (`right_id`) REFERENCES `matching_right_item` (`id`) ON DELETE CASCADE;

--
-- Megkötések a táblához `matching_right_item`
--
ALTER TABLE `matching_right_item`
  ADD CONSTRAINT `FK_RIGHT_QUESTION` FOREIGN KEY (`question_id`) REFERENCES `question` (`id`) ON DELETE CASCADE;

--
-- Megkötések a táblához `question`
--
ALTER TABLE `question`
  ADD CONSTRAINT `FK_QUESTION_QUIZ` FOREIGN KEY (`quiz_id`) REFERENCES `quiz` (`id`) ON DELETE CASCADE;

--
-- Megkötések a táblához `quiz`
--
ALTER TABLE `quiz`
  ADD CONSTRAINT `FK_QUIZ_LANGUAGE` FOREIGN KEY (`language_code`) REFERENCES `language` (`code`);

--
-- Megkötések a táblához `quiz_attempt`
--
ALTER TABLE `quiz_attempt`
  ADD CONSTRAINT `FK_QA_QUIZ` FOREIGN KEY (`quiz_id`) REFERENCES `quiz` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_QA_USER` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Megkötések a táblához `quiz_viewer_email`
--
ALTER TABLE `quiz_viewer_email`
  ADD CONSTRAINT `QUIZ_VIEWER_EMAIL_QUIZ_FK` FOREIGN KEY (`quiz_id`) REFERENCES `quiz` (`id`) ON DELETE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

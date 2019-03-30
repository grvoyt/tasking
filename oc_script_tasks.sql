-- phpMyAdmin SQL Dump
-- version 4.6.6deb4
-- https://www.phpmyadmin.net/
--
-- Хост: localhost
-- Время создания: Мар 30 2019 г., 16:25
-- Версия сервера: 10.1.26-MariaDB-0+deb9u1
-- Версия PHP: 7.0.33-0+deb9u3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `istylespb`
--

-- --------------------------------------------------------

--
-- Структура таблицы `oc_script_tasks`
--

CREATE TABLE `oc_script_tasks` (
  `id` int(11) NOT NULL,
  `type` int(11) NOT NULL DEFAULT '0',
  `status` int(2) NOT NULL,
  `error` varchar(255) DEFAULT NULL,
  `date_start` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_end` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `oc_script_tasks`
--

INSERT INTO `oc_script_tasks` (`id`, `type`, `status`, `error`, `date_start`, `date_end`) VALUES
(1, 0, 1, NULL, '2019-03-08 09:38:25', NULL),
(2, 0, 0, NULL, '2019-03-08 09:38:25', NULL),
(3, 0, 0, NULL, '2019-03-08 09:38:25', NULL),
(4, 0, 2, 'Ошибка скрипты на строке 1', '2019-03-08 09:38:25', NULL),
(5, 0, 0, NULL, '2019-03-08 09:38:25', NULL),
(6, 0, 0, NULL, '2019-03-08 09:38:25', NULL),
(7, 0, 0, NULL, '2019-03-08 09:38:25', NULL),
(8, 0, 2, 'Invalid token format', '2019-03-30 13:48:51', NULL),
(9, 0, 2, 'Invalid token format', '2019-03-30 13:49:03', NULL);

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `oc_script_tasks`
--
ALTER TABLE `oc_script_tasks`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `oc_script_tasks`
--
ALTER TABLE `oc_script_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

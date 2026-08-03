-- ============================================================
-- Profiles — дамп базы данных
-- Совместимо с MySQL 5.7+ / MariaDB 10.2+
-- Кодировка: utf8mb4 / utf8mb4_unicode_ci
-- (в исходном дампе использовалась utf8mb4_0900_ai_ci — она
--  доступна только с MySQL 8.0+ и не импортируется на 5.7,
--  поэтому здесь коллация заменена на universal-совместимую)
-- ============================================================
-- ВАЖНО (миграция для уже установленного проекта): таблицы создаются
-- через CREATE TABLE IF NOT EXISTS, поэтому повторный импорт этого
-- файла НЕ применит изменения структуры к уже существующей базе. Если
-- вы уже разворачивали проект раньше, выполните в phpMyAdmin (вкладка SQL):
--
--     ALTER TABLE users CHANGE COLUMN patreon_url boosty_url VARCHAR(255) DEFAULT NULL;
--     ALTER TABLE users ADD COLUMN profile_bg_url VARCHAR(255) DEFAULT NULL AFTER avatar_url;
--
-- (или просто удалите базу profiles_db и импортируйте этот файл заново,
-- если тестовые данные не жалко).
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `profiles_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `profiles_db`;

-- --------------------------------------------------------
-- Таблица `users` — пользователи
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `avatar_url` VARCHAR(255) DEFAULT 'assets/img/default-avatar.png',
  -- profile_bg_url: полупрозрачное фоновое изображение карточки профиля (необязательно)
  `profile_bg_url` VARCHAR(255) DEFAULT NULL,
  `bio` TEXT,
  `profession` VARCHAR(100) DEFAULT NULL,
  -- status_badge: бейдж профессионального статуса (доп. поле к ТЗ,
  -- нужно для выпадающего списка из раздела 2.4/2.6 технического задания)
  `status_badge` ENUM('active','looking','vacation','freelance','student') DEFAULT NULL,
  `primary_color` VARCHAR(7) DEFAULT '#00BCD4',
  `button_color` VARCHAR(7) DEFAULT 'default',
  `bg_color` VARCHAR(7) DEFAULT 'default',
  `text_color` VARCHAR(7) DEFAULT '#FFFFFF',
  `separate_colors` TINYINT(1) DEFAULT 0,
  `grid_size` TINYINT DEFAULT 2,
  `post_display_style` ENUM('standard','image_only') DEFAULT 'standard',
  `instagram_url` VARCHAR(255) DEFAULT NULL,
  `telegram_url` VARCHAR(255) DEFAULT NULL,
  `github_url` VARCHAR(255) DEFAULT NULL,
  `vk_url` VARCHAR(255) DEFAULT NULL,
  -- paypal_url: хранит email или ID кошелька PayPal (не ссылку) — см. socialLinkHref()
  `paypal_url` VARCHAR(255) DEFAULT NULL,
  -- boosty_url: ссылка на профиль Boosty (раньше был Patreon, заменён по просьбе заказчика)
  `boosty_url` VARCHAR(255) DEFAULT NULL,
  `tutorial_completed` TINYINT(1) DEFAULT 0,
  `role` ENUM('user','admin') DEFAULT 'user',
  `last_login` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_username` (`username`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Таблица `posts` — посты
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `posts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(100) DEFAULT NULL,
  `content` TEXT NOT NULL,
  `image_url` VARCHAR(255) DEFAULT NULL,
  `post_type` ENUM('text','image','mixed') DEFAULT 'text',
  -- sort_order: позиция в сетке профиля, меняется через drag-and-drop
  `sort_order` INT DEFAULT 0,
  `edited_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Таблица `profile_blocks` — текстовые заметки между постами
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `profile_blocks` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `content` TEXT NOT NULL,
  `sort_order` INT DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `profile_blocks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Таблица `interactions` — лайки и подписки
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `interactions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `target_id` INT UNSIGNED NOT NULL,
  `type` ENUM('like','follow') NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_interaction` (`user_id`,`target_id`,`type`),
  KEY `idx_target` (`target_id`,`type`),
  CONSTRAINT `interactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Таблица `post_edits` — история правок постов
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `post_edits` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id` INT UNSIGNED NOT NULL,
  `content_old` TEXT,
  `content_new` TEXT,
  `edited_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_post_id` (`post_id`),
  CONSTRAINT `post_edits_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Тестовые данные
-- Логин администратора: admin / password
-- ('password' захеширован через password_hash() — bcrypt, PASSWORD_DEFAULT)
-- --------------------------------------------------------

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `avatar_url`, `bio`, `profession`, `status_badge`, `role`, `primary_color`, `button_color`, `bg_color`, `text_color`) VALUES
(1, 'admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'assets/img/default-avatar.png', 'Администратор сети Profiles.', 'Администратор', 'active', 'admin', '#00BCD4', 'default', 'default', '#FFFFFF'),
(2, 'Diana', 'diana@example.com', '$2y$10$GDtCqF4X/kEW3Dw28a3JQ.L3dMffzlOi20RTHliPz/Z2xh8MnTKQW', 'assets/img/default-avatar.png', 'Разработчик проекта Profiles.', 'Веб-разработчик', 'student', 'user', '#8c2b84', '#FF6B6B', '#2E4053', '#FFFFFF');

INSERT INTO `posts` (`id`, `user_id`, `title`, `content`, `image_url`, `post_type`, `created_at`) VALUES
(1, 1, 'Добро пожаловать!', 'Это тестовый пост. Зарегистрируйтесь и создайте свой первый пост!', NULL, 'text', '2026-07-22 11:32:41'),
(2, 2, 'Первая публикация', 'Всем привет! Это учебный проект — локальная социальная сеть-портфолио Profiles.', NULL, 'text', '2026-08-01 16:41:12');

INSERT INTO `profile_blocks` (`id`, `user_id`, `content`, `sort_order`) VALUES
(1, 2, 'Здесь можно оставить текстовую заметку между постами — например, ссылку на резюме или короткое "обо мне".', 0);

INSERT INTO `interactions` (`id`, `user_id`, `target_id`, `type`, `created_at`) VALUES
(1, 2, 1, 'like', '2026-07-22 14:06:14');

ALTER TABLE `users` AUTO_INCREMENT = 3;
ALTER TABLE `posts` AUTO_INCREMENT = 3;
ALTER TABLE `profile_blocks` AUTO_INCREMENT = 2;
ALTER TABLE `interactions` AUTO_INCREMENT = 2;

COMMIT;

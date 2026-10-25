<?php
/**
 * ============================================================
 * config.php
 * Конфигурация приложения
 * PHP 7.4+
 * ============================================================
 */

// ============================================================
// 1. НАСТРОЙКИ БАЗЫ ДАННЫХ
// ============================================================
// 'localhost' подходит для большинства локальных серверов (OpenServer, XAMPP, WAMP).
// Если MySQL в OpenServer не отвечает на 'localhost' — укажите имя модуля
// MySQL из настроек OpenServer (вкладка "Модули"), например 'MySQL-8.4'.

define('DB_HOST', 'MySQL-8.4');
define('DB_NAME', 'profiles_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// ============================================================
// 2. НАСТРОЙКИ САЙТА
// ============================================================

define('SITE_NAME', 'Profiles');
define('SITE_URL', 'http://profiles.local/');

// ============================================================
// 3. ПУТИ К ПАПКАМ
// ============================================================
// *_DIR — физический путь на диске (для move_uploaded_file)
// *_URL — относительный путь для <img src="...">

define('ROOT_PATH', dirname(__DIR__));
define('AVATAR_UPLOAD_DIR', ROOT_PATH . '/uploads/avatars/');
define('POST_UPLOAD_DIR', ROOT_PATH . '/uploads/posts/');
// Фоновое изображение карточки профиля (задание "Фон карточки профиля")
define('PROFILE_BG_UPLOAD_DIR', ROOT_PATH . '/uploads/profile_bg/');
define('AVATAR_UPLOAD_URL', 'uploads/avatars/');
define('POST_UPLOAD_URL', 'uploads/posts/');
define('PROFILE_BG_UPLOAD_URL', 'uploads/profile_bg/');
define('DEFAULT_AVATAR', 'assets/img/default-avatar.png');

// ============================================================
// 4. ОГРАНИЧЕНИЯ
// ============================================================

define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 МБ — для аватаров и картинок постов
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Фон карточки профиля — отдельные, более строгие ограничения по заданию:
// максимум 50 МБ и без GIF (анимированный фон карточки был бы неуместен)
define('MAX_PROFILE_BG_SIZE', 50 * 1024 * 1024); // 50 МБ
define('ALLOWED_PROFILE_BG_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('ALLOWED_PROFILE_BG_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);

// ============================================================
// 5. РЕЖИМ РАЗРАБОТКИ
// ============================================================
// В боевом режиме поставьте false, чтобы не показывать ошибки пользователям.

define('DEBUG_MODE', true);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

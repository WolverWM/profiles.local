<?php
// Настройки сессии: cookie только по HTTP (без JS) и без передачи ID в URL
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// Запрещаем браузеру кэшировать страницы, зависящие от сессии.
// Без этого после логаута/входа под другим пользователем браузер иногда
// показывает старую сохранённую в кэше версию страницы (со старыми
// цветами/данными) вместо свежего запроса к серверу.
if (!headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}
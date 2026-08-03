<?php
/**
 * ============================================================
 * logout.php — выход из аккаунта: очищаем сессию и cookie
 * ============================================================
 */
require_once 'includes/config.php';
require_once 'includes/session.php';
require_once 'includes/functions.php';

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

redirect('login.php');

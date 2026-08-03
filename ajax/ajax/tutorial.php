<?php
/**
 * ============================================================
 * ajax/tutorial.php — отмечает туториал как пройденный (задание 2.7)
 * ============================================================
 */
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/session.php';
require_once '../includes/csrf.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !loggedIn()) {
    http_response_code(loggedIn() ? 405 : 401);
    exit(json_encode(['success' => false]));
}

csrf_require();

$userId = (int)currentUser()['id'];
$pdo->prepare("UPDATE users SET tutorial_completed = 1 WHERE id = ?")->execute([$userId]);
$_SESSION['user']['tutorial_completed'] = 1;

echo json_encode(['success' => true]);

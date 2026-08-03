<?php
/**
 * ============================================================
 * ajax/follow.php — подписаться/отписаться (AJAX)
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

$targetId = (int)($_POST['target_id'] ?? 0);
$userId = (int)currentUser()['id'];

if (!$targetId || $targetId === $userId) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'error' => 'Нельзя подписаться на самого себя']));
}

$exists = $pdo->prepare("SELECT id FROM users WHERE id = ?");
$exists->execute([$targetId]);
if (!$exists->fetch()) {
    http_response_code(404);
    exit(json_encode(['success' => false]));
}

$stmt = $pdo->prepare("SELECT id FROM interactions WHERE user_id = ? AND target_id = ? AND type = 'follow'");
$stmt->execute([$userId, $targetId]);
$following = (bool) $stmt->fetch();

if ($following) {
    $pdo->prepare("DELETE FROM interactions WHERE user_id = ? AND target_id = ? AND type = 'follow'")
        ->execute([$userId, $targetId]);
} else {
    $pdo->prepare("INSERT INTO interactions (user_id, target_id, type) VALUES (?, ?, 'follow')")
        ->execute([$userId, $targetId]);
}

$count = $pdo->prepare("SELECT COUNT(*) FROM interactions WHERE target_id = ? AND type = 'follow'");
$count->execute([$targetId]);

echo json_encode([
    'success' => true,
    'following' => !$following,
    'followers' => (int)$count->fetchColumn(),
]);

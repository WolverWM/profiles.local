<?php
/**
 * ============================================================
 * ajax/like.php — поставить/убрать лайк (AJAX, без перезагрузки)
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

$postId = (int)($_POST['post_id'] ?? 0);
$userId = (int)currentUser()['id'];

$exists = $pdo->prepare("SELECT id FROM posts WHERE id = ?");
$exists->execute([$postId]);
if (!$postId || !$exists->fetch()) {
    http_response_code(404);
    exit(json_encode(['success' => false]));
}

$stmt = $pdo->prepare("SELECT id FROM interactions WHERE user_id = ? AND target_id = ? AND type = 'like'");
$stmt->execute([$userId, $postId]);
$liked = (bool) $stmt->fetch();

if ($liked) {
    $pdo->prepare("DELETE FROM interactions WHERE user_id = ? AND target_id = ? AND type = 'like'")
        ->execute([$userId, $postId]);
} else {
    $pdo->prepare("INSERT INTO interactions (user_id, target_id, type) VALUES (?, ?, 'like')")
        ->execute([$userId, $postId]);
}

$count = $pdo->prepare("SELECT COUNT(*) FROM interactions WHERE target_id = ? AND type = 'like'");
$count->execute([$postId]);

echo json_encode([
    'success' => true,
    'liked' => !$liked,
    'likes' => (int)$count->fetchColumn(),
]);

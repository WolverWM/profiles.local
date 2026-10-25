<?php
/**
 * ============================================================
 * ajax/delete_block.php — удаление текстовой заметки профиля
 * Доступно только владельцу заметки (задание 2.4, drag-and-drop блок)
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

$blockId = (int)($_POST['id'] ?? 0);
$userId = (int)currentUser()['id'];

if (!$blockId) {
    http_response_code(400);
    exit(json_encode(['success' => false]));
}

// Условие "AND user_id = ?" не даёт удалить чужую заметку, даже если
// кто-то напрямую отправит запрос с подобранным id
$stmt = $pdo->prepare("DELETE FROM profile_blocks WHERE id = ? AND user_id = ?");
$stmt->execute([$blockId, $userId]);

echo json_encode(['success' => $stmt->rowCount() > 0]);

<?php
/**
 * ============================================================
 * ajax/reorder.php — сохраняет новый порядок карточек в профиле
 * после Drag-and-Drop (задание 2.4, только для владельца)
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
$order = json_decode($_POST['order'] ?? '[]', true);

if (!is_array($order)) {
    http_response_code(400);
    exit(json_encode(['success' => false]));
}

$position = 0;
foreach ($order as $entry) {
    $type = $entry['type'] ?? '';
    $id = (int)($entry['id'] ?? 0);
    if (!$id) continue;

    if ($type === 'post') {
        // Обновляем только посты, которые принадлежат текущему пользователю
        $pdo->prepare("UPDATE posts SET sort_order = ? WHERE id = ? AND user_id = ?")
            ->execute([$position, $id, $userId]);
    } elseif ($type === 'block') {
        $pdo->prepare("UPDATE profile_blocks SET sort_order = ? WHERE id = ? AND user_id = ?")
            ->execute([$position, $id, $userId]);
    }
    $position++;
}

echo json_encode(['success' => true]);

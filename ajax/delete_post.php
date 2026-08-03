<?php
/**
 * ============================================================
 * delete_post.php — удаление поста (владелец или админ)
 * ============================================================
 */
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/session.php';
require_once 'includes/csrf.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Метод не разрешён');
}

csrf_require();

$postId = (int)($_POST['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$postId]);
$post = $stmt->fetch();

if (!$post) show404();

// Удалить может только автор поста или администратор (задание "Роли пользователей")
if (!isOwner((int)$post['user_id']) && !isAdmin()) {
    http_response_code(403);
    exit('Недостаточно прав для удаления этого поста');
}

// Удаляем файл изображения, если он был
if (!empty($post['image_url'])) {
    $filePath = ROOT_PATH . '/' . ltrim($post['image_url'], '/');
    if (is_file($filePath)) {
        @unlink($filePath);
    }
}

$pdo->prepare("DELETE FROM posts WHERE id = ?")->execute([$postId]);

$ownerId = (int)$post['user_id'];
$referer = $_SERVER['HTTP_REFERER'] ?? '';
if (strpos($referer, 'index.php') !== false) {
    redirect('index.php');
}
redirect('profile.php?id=' . $ownerId);

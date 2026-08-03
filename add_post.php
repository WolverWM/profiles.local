<?php
/**
 * ============================================================
 * add_post.php — обработчик создания поста
 * Вызывается формами с index.php и profile.php
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

$title = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? '');
$image = $_FILES['image'] ?? null;
$userId = (int)currentUser()['id'];

if ($content === '') {
    http_response_code(400);
    exit('Введите текст поста');
}

// Загрузка изображения (необязательно)
$imageUrl = null;
if ($image && $image['error'] !== UPLOAD_ERR_NO_FILE) {
    $upload = uploadImage($image, POST_UPLOAD_DIR, POST_UPLOAD_URL, MAX_FILE_SIZE);
    if ($upload['success']) {
        $imageUrl = $upload['url'];
    } elseif ($image['error'] !== UPLOAD_ERR_OK) {
        // Ошибка загрузки не должна блокировать текстовый пост — просто публикуем без картинки
        $imageUrl = null;
    }
}

$postType = $imageUrl ? 'mixed' : 'text';

$stmt = $pdo->prepare("INSERT INTO posts (user_id, title, content, image_url, post_type) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$userId, $title ?: null, $content, $imageUrl, $postType]);

// Возвращаемся туда, откуда пришла форма (лента или профиль)
$referer = $_SERVER['HTTP_REFERER'] ?? '';
if (strpos($referer, 'index.php') !== false) {
    redirect('index.php?created=1');
}
redirect('profile.php?id=' . $userId . '&created=1');

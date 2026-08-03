<?php
/**
 * ============================================================
 * ajax/search.php — динамический поиск ленты (без перезагрузки)
 * "@имя" -> поиск пользователей, обычный текст -> поиск постов
 * ============================================================
 */
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/session.php';
require_once '../includes/csrf.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

header('Content-Type: text/html; charset=utf-8');

$userId = currentUser()['id'] ?? 0;
$search = trim($_GET['q'] ?? '');
$filter = $_GET['filter'] ?? 'all';

// Поиск пользователей по @username
if (strpos($search, '@') === 0) {
    $needle = trim(substr($search, 1));
    if ($needle === '') {
        echo '<p class="empty-state">Введите имя пользователя после @.</p>';
        exit;
    }
    $stmt = $pdo->prepare("SELECT id, username, avatar_url FROM users WHERE username LIKE ? ORDER BY username ASC LIMIT 30");
    $stmt->execute(['%' . $needle . '%']);
    $users = $stmt->fetchAll();

    if (empty($users)) {
        echo '<p class="empty-state">Пользователи не найдены.</p>';
        exit;
    }
    echo '<div class="follow-grid">';
    foreach ($users as $u) {
        echo renderUserCard($u);
    }
    echo '</div>';
    exit;
}

// Поиск постов (с учётом фильтра)
$sql = "
    SELECT p.*, u.username, u.avatar_url, u.post_display_style, u.id as user_id,
    (SELECT COUNT(*) FROM interactions WHERE target_id = p.id AND type = 'like') as likes_count,
    (SELECT COUNT(*) FROM interactions WHERE target_id = p.id AND type = 'like' AND user_id = ?) as user_liked
    FROM posts p JOIN users u ON p.user_id = u.id WHERE 1=1
";
$params = [$userId ?: 0];

if ($filter === 'image') { $sql .= " AND p.image_url IS NOT NULL"; }
elseif ($filter === 'text') { $sql .= " AND p.image_url IS NULL"; }

if ($search !== '') {
    $sql .= " AND (p.content LIKE ? OR p.title LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY p.created_at DESC LIMIT 60";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll();

echo '<div class="posts-grid" id="feedGrid">';
if (empty($posts)) {
    echo '<p class="empty-state">Ничего не найдено.</p>';
} else {
    foreach ($posts as $post) {
        echo renderPostCard([
            'id' => $post['id'],
            'user_id' => $post['user_id'],
            'username' => $post['username'],
            'avatar_url' => $post['avatar_url'],
            'created_at' => $post['created_at'],
            'title' => $post['title'],
            'content' => $post['content'],
            'image_url' => $post['image_url'],
            'likes_count' => $post['likes_count'],
            'liked' => (bool)($post['user_liked'] ?? 0),
            'image_only' => ($post['post_display_style'] ?? 'standard') === 'image_only',
        ], loggedIn());
    }
}
echo '</div>';

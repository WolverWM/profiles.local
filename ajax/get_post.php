<?php
/**
 * ============================================================
 * ajax/get_post.php — HTML-фрагмент полной версии поста
 * для модального окна (задание 2.7)
 * ============================================================
 */
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/session.php';
require_once '../includes/csrf.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

header('Content-Type: text/html; charset=utf-8');

$postId = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT p.*, u.username, u.avatar_url FROM posts p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
$stmt->execute([$postId]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
    echo '<p class="empty-state">Пост не найден.</p>';
    exit;
}

$likeCountStmt = $pdo->prepare("SELECT COUNT(*) FROM interactions WHERE target_id = ? AND type = 'like'");
$likeCountStmt->execute([$postId]);
$likesCount = (int)$likeCountStmt->fetchColumn();

$liked = false;
if (loggedIn()) {
    $userId = (int)currentUser()['id'];
    $likedStmt = $pdo->prepare("SELECT 1 FROM interactions WHERE user_id = ? AND target_id = ? AND type = 'like'");
    $likedStmt->execute([$userId, $postId]);
    $liked = (bool)$likedStmt->fetchColumn();
}

$canManage = isOwner((int)$post['user_id']) || isAdmin();
?>
<div class="modal-post">
    <div class="modal-post-header">
        <img src="<?php echo escape(avatarSrc($post['avatar_url'])); ?>" alt="Аватар пользователя <?php echo escape($post['username']); ?>">
        <div>
            <a href="profile.php?id=<?php echo (int)$post['user_id']; ?>"><?php echo escape($post['username']); ?></a>
            <span class="modal-post-date">
                <?php echo timeAgo($post['created_at']); ?>
                <?php if ($post['edited_at']): ?> · изменено<?php endif; ?>
            </span>
        </div>
    </div>

    <?php if ($post['image_url']): ?>
        <img src="<?php echo escape($post['image_url']); ?>" class="modal-post-image" alt="<?php echo escape($post['title'] ?: ('Изображение к посту пользователя ' . $post['username'])); ?>">
    <?php endif; ?>

    <?php if ($post['title']): ?><h2 class="modal-post-title"><?php echo escape($post['title']); ?></h2><?php endif; ?>
    <p class="modal-post-text"><?php echo nl2br(escape($post['content'])); ?></p>

    <div class="modal-post-footer">
        <?php if (loggedIn()): ?>
            <button type="button" class="like-btn" data-post-id="<?php echo (int)$post['id']; ?>">
                <i class="fa-heart <?= $liked ? 'fas liked' : 'far' ?>"></i>
                <span class="like-count"><?php echo $likesCount; ?></span>
            </button>
        <?php else: ?>
            <span class="like-static"><i class="far fa-heart"></i> <?php echo $likesCount; ?></span>
        <?php endif; ?>

        <?php if ($canManage): ?>
            <a href="edit_post.php?id=<?php echo (int)$post['id']; ?>" class="btn-icon" title="Редактировать"><i class="fas fa-pen"></i></a>
            <form method="POST" action="delete_post.php" onsubmit="return confirm('Удалить этот пост?');" class="inline-form">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" value="<?php echo (int)$post['id']; ?>">
                <button type="submit" class="btn-icon btn-icon-danger" title="Удалить"><i class="fas fa-trash"></i></button>
            </form>
        <?php endif; ?>
    </div>
</div>

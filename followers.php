<?php
/**
 * ============================================================
 * followers.php — список подписчиков пользователя
 * ============================================================
 */
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/session.php';
require_once 'includes/csrf.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$userId = (int)($_GET['id'] ?? 0);
if (!$userId) {
    $me = currentUser();
    if (!$me) redirect('login.php');
    $userId = (int)$me['id'];
}

// SELECT * (а не только id/username/avatar_url), чтобы забрать и цвета
// темы владельца — эта страница тоже оформляется в его цветах
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$profile = $stmt->fetch();
if (!$profile) show404();

// Подписчики: те, кто подписан НА этого пользователя (target_id = userId)
$stmt = $pdo->prepare("
    SELECT u.id, u.username, u.avatar_url
    FROM interactions i
    JOIN users u ON i.user_id = u.id
    WHERE i.target_id = ? AND i.type = 'follow'
    ORDER BY i.created_at DESC
");
$stmt->execute([$userId]);
$followers = $stmt->fetchAll();

$pageTitle = 'Подписчики ' . $profile['username'];
$themeUser = $profile; // цвета владельца профиля, а не текущего зрителя
require_once 'includes/header.php';
?>

<div class="follow-page">
    <h1><i class="fas fa-users"></i> Подписчики</h1>
    <p class="follow-subtitle">Пользователи, подписанные на <strong><?php echo escape($profile['username']); ?></strong></p>

    <?php if (empty($followers)): ?>
        <div class="empty-state">
            <p>У этого пользователя пока нет подписчиков.</p>
        </div>
    <?php else: ?>
        <div class="follow-grid">
            <?php foreach ($followers as $follow): ?>
                <a href="profile.php?id=<?php echo (int)$follow['id']; ?>" class="follow-card">
                    <img src="<?php echo escape(avatarSrc($follow['avatar_url'])); ?>" alt="Аватар пользователя <?php echo escape($follow['username']); ?>">
                    <span><?php echo escape($follow['username']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <p class="back-link"><a href="profile.php?id=<?php echo (int)$userId; ?>">← Вернуться в профиль</a></p>
</div>

<?php require_once 'includes/footer.php'; ?>

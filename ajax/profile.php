<?php
/**
 * ============================================================
 * profile.php — страница профиля пользователя
 * Шапка, соцссылки, сетка постов + текстовых блоков (drag-and-drop)
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

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$profile = $stmt->fetch();
if (!$profile) {
    show404();
}

$isOwnerView = isOwner((int)$profile['id']);

// Владелец может добавить текстовый блок между постами (задание 2.4)
$blockMessage = '';
if ($isOwnerView && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_block'])) {
    csrf_require();
    $content = trim($_POST['block_content'] ?? '');
    if ($content !== '') {
        $pdo->prepare("INSERT INTO profile_blocks (user_id, content) VALUES (?, ?)")
            ->execute([$profile['id'], $content]);
    }
    redirect('profile.php?id=' . $profile['id']);
}

// Посты пользователя
$postsStmt = $pdo->prepare("SELECT *, 'post' as item_type FROM posts WHERE user_id = ? ORDER BY sort_order ASC, created_at DESC");
$postsStmt->execute([$userId]);
$posts = $postsStmt->fetchAll();

// Текстовые заметки между постами
$blocksStmt = $pdo->prepare("SELECT *, 'block' as item_type FROM profile_blocks WHERE user_id = ? ORDER BY sort_order ASC, created_at DESC");
$blocksStmt->execute([$userId]);
$blocks = $blocksStmt->fetchAll();

// Объединяем посты и блоки в одну сетку, сортируем по sort_order (для drag-and-drop)
$gridItems = array_merge($posts, $blocks);
usort($gridItems, function ($a, $b) {
    $orderA = (int)($a['sort_order'] ?? 0);
    $orderB = (int)($b['sort_order'] ?? 0);
    if ($orderA !== $orderB) return $orderA <=> $orderB;
    return strtotime($b['created_at']) <=> strtotime($a['created_at']);
});

$currentUserId = currentUser()['id'] ?? 0;
$likedStmt = $pdo->prepare("SELECT target_id FROM interactions WHERE user_id = ? AND type = 'like'");
$likedStmt->execute([$currentUserId ?: 0]);
$likedPostIds = array_column($likedStmt->fetchAll(), 'target_id');

$followersCount = countFollowers($pdo, $profile['id']);
$followingCount = countFollowing($pdo, $profile['id']);
$isFollowingProfile = loggedIn() ? isFollowing($pdo, (int)$currentUserId, (int)$profile['id']) : false;
$gridClass = gridSizeClass((int)($profile['grid_size'] ?? 2));
// Фон карточки профиля: если владелец загрузил картинку — передаём её в CSS
// через переменную --profile-bg-image; если нет — переменная просто не
// задаётся, и .profile-header остаётся на обычном цветовом фоне (см. style.css)
$profileBgUrl = profileBgSrc($profile['profile_bg_url'] ?? null);

$pageTitle = $profile['username'];
$pageDescription = 'Профиль пользователя ' . $profile['username'] . ' в Profiles' . (!empty($profile['profession']) ? ' — ' . $profile['profession'] : '') . '.';
// Цвета на этой странице — цвета ВЛАДЕЛЬЦА профиля (не того, кто сейчас смотрит),
// поэтому цветовая схема "принадлежит" пользователю, а не сессии просмотра
$themeUser = $profile;
require_once 'includes/header.php';
?>

<?php if (isset($_GET['created'])): ?>
    <div class="alert success">✅ Пост опубликован!</div>
<?php endif; ?>

<div class="profile-header" <?php if ($profileBgUrl): ?>style="--profile-bg-image: url('<?php echo escape($profileBgUrl); ?>');"<?php endif; ?>>
    <img src="<?php echo escape(avatarSrc($profile['avatar_url'])); ?>" class="profile-avatar" alt="Аватар пользователя <?php echo escape($profile['username']); ?>">
    <div class="profile-info">
        <div class="profile-name-row">
            <h1><?php echo escape($profile['username']); ?></h1>
            <?php if (!empty($profile['status_badge'])): ?>
                <span class="status-badge badge-<?php echo escape($profile['status_badge']); ?>"><?php echo escape(statusBadgeLabel($profile['status_badge'])); ?></span>
            <?php endif; ?>
        </div>
        <?php if (!empty($profile['profession'])): ?>
            <p class="profession"><?php echo escape($profile['profession']); ?></p>
        <?php endif; ?>
        <?php if (!empty($profile['bio'])): ?>
            <p class="bio"><?php echo escape($profile['bio']); ?></p>
        <?php endif; ?>

        <div class="stats">
            <span><i class="fas fa-note-sticky"></i> <?php echo countPosts($pdo, $profile['id']); ?> постов</span>
            <span><i class="fas fa-heart"></i> <?php echo countLikes($pdo, $profile['id']); ?> лайков</span>
        </div>
        <div class="subscription-links">
            <a href="followers.php?id=<?php echo (int)$profile['id']; ?>"><i class="fas fa-users"></i> Подписчики (<?php echo $followersCount; ?>)</a>
            <a href="following.php?id=<?php echo (int)$profile['id']; ?>"><i class="fas fa-user-check"></i> Подписки (<?php echo $followingCount; ?>)</a>
        </div>
        <p class="last-action">
            <i class="fas fa-clock"></i>
            <?php echo $profile['last_login'] ? 'Последний визит: ' . timeAgo($profile['last_login']) : 'Ещё не заходил(а)'; ?>
        </p>

        <?php
        // Соцссылки: показываем только заполненные (задание 2.4)
        $links = [];
        foreach (socialPlatforms() as $key => $meta) {
            $url = $profile[$key . '_url'] ?? '';
            if (!empty($url)) $links[$key] = $meta + ['url' => $url];
        }
        ?>
        <?php if (!empty($links)): ?>
            <div class="social-links">
                <?php foreach ($links as $key => $meta): ?>
                    <a href="<?php echo escape(socialLinkHref($key, $meta['url'])); ?>" target="_blank" rel="noopener noreferrer" title="<?php echo escape($meta['label']); ?>">
                        <i class="<?php echo escape($meta['icon']); ?>"></i>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="profile-actions">
            <a href="index.php" class="btn btn-secondary"><i class="fas fa-rss"></i> Общая лента</a>
            <?php if ($isOwnerView): ?>
                <a href="dashboard.php" class="btn"><i class="fas fa-pen"></i> Редактировать профиль</a>
            <?php elseif (loggedIn()): ?>
                <button type="button" id="followBtn" class="btn <?= $isFollowingProfile ? 'btn-secondary' : '' ?>" data-target-id="<?php echo (int)$profile['id']; ?>" data-following="<?= $isFollowingProfile ? '1' : '0' ?>">
                    <i class="fas <?= $isFollowingProfile ? 'fa-user-check' : 'fa-user-plus' ?>"></i>
                    <span><?= $isFollowingProfile ? 'Вы подписаны' : 'Подписаться' ?></span>
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($isOwnerView): ?>
    <div class="create-post-inline">
        <h3><i class="fas fa-pen"></i> Создать пост</h3>
        <form method="POST" action="add_post.php" enctype="multipart/form-data" data-validate novalidate>
            <?php echo csrf_field(); ?>
            <input type="text" name="title" placeholder="Заголовок (необязательно)" maxlength="100">
            <textarea name="content" placeholder="Что нового?" data-rule="required"></textarea>
            <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
            <button type="submit">Опубликовать</button>
        </form>
    </div>

    <div class="create-post-inline">
        <h3><i class="fas fa-note-sticky"></i> Добавить текстовый блок</h3>
        <form method="POST" data-validate novalidate>
            <?php echo csrf_field(); ?>
            <textarea name="block_content" placeholder="Заметка, которая появится между постами..." data-rule="required"></textarea>
            <button type="submit" name="add_block">Добавить блок</button>
        </form>
    </div>
<?php endif; ?>

<div class="posts-grid <?php echo escape($gridClass); ?>" id="profileGrid">
    <?php if (empty($gridItems)): ?>
        <p class="empty-state">Пока здесь пусто.</p>
    <?php endif; ?>
    <?php foreach ($gridItems as $item): ?>
        <?php if ($item['item_type'] === 'post'):
            $likeCountStmt = $pdo->prepare("SELECT COUNT(*) FROM interactions WHERE target_id = ? AND type = 'like'");
            $likeCountStmt->execute([$item['id']]);
            echo renderPostCard([
                'id' => $item['id'],
                'user_id' => $profile['id'],
                'username' => $profile['username'],
                'avatar_url' => $profile['avatar_url'],
                'created_at' => $item['created_at'],
                'title' => $item['title'],
                'content' => $item['content'],
                'image_url' => $item['image_url'],
                'likes_count' => (int)$likeCountStmt->fetchColumn(),
                'liked' => in_array($item['id'], $likedPostIds),
                'image_only' => ($profile['post_display_style'] ?? 'standard') === 'image_only',
            ], loggedIn(), $isOwnerView);
        else: ?>
            <div class="text-block-card" data-type="block" data-id="<?php echo (int)$item['id']; ?>" <?php echo $isOwnerView ? 'draggable="true"' : ''; ?>>
                <?php if ($isOwnerView): ?>
                    <!-- Кнопка удаления заметки видна только владельцу профиля -->
                    <button type="button" class="block-delete-btn" data-block-id="<?php echo (int)$item['id']; ?>" draggable="false" title="Удалить заметку">&times;</button>
                <?php endif; ?>
                <i class="fas fa-quote-left"></i>
                <p><?php echo nl2br(escape($item['content'])); ?></p>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

<?php if ($isOwnerView): ?>
    <p class="grid-hint"><i class="fas fa-arrows-up-down-left-right"></i> Перетаскивайте карточки, чтобы изменить порядок.</p>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>

<?php
/**
 * ============================================================
 * index.php — главная страница (лента постов)
 * Слайдер последних постов с картинками, поиск, фильтры, лента
 * ============================================================
 */
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/session.php';
require_once 'includes/csrf.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$user = currentUser();
$userId = $user['id'] ?? null;

$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['search'] ?? '');

// Лента постов (JOIN с users, чтобы сразу получить аватар/имя автора и лайки)
try {
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
        if (strpos($search, '@') === 0) {
            $sql .= " AND u.username LIKE ?";
            $params[] = '%' . substr($search, 1) . '%';
        } else {
            $sql .= " AND (p.content LIKE ? OR p.title LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
    }

    $sql .= " ORDER BY p.created_at DESC LIMIT 60";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $posts = $stmt->fetchAll();
} catch (PDOException $e) {
    $posts = [];
}

// Слайдер: 9 самых популярных постов с изображением (по числу лайков), из них
// одновременно на экране видно 5 — остальные докручиваются автопрокруткой
// (см. .slider-container в style.css и initSlider() в main.js). Запрос
// выполняется заново при каждой загрузке страницы, поэтому порядок всегда
// отражает актуальное число лайков на момент открытия ленты.
try {
    $sliderStmt = $pdo->prepare("
        SELECT p.*, u.username, u.id as user_id,
        (SELECT COUNT(*) FROM interactions WHERE target_id = p.id AND type = 'like') as likes_count
        FROM posts p JOIN users u ON p.user_id = u.id
        WHERE p.image_url IS NOT NULL
        ORDER BY likes_count DESC, p.created_at DESC
        LIMIT 9
    ");
    $sliderStmt->execute();
    $sliderPosts = $sliderStmt->fetchAll();
} catch (PDOException $e) {
    $sliderPosts = [];
}

$pageTitle = 'Лента';
$pageDescription = 'Лента последних постов пользователей Profiles — текст, изображения, лайки и подписки.';
require_once 'includes/header.php';
?>

<?php if (isset($_GET['created'])): ?>
    <div class="alert success">✅ Пост опубликован!</div>
<?php endif; ?>

<?php if (!empty($sliderPosts)): ?>
<div class="slider-container" id="sliderContainer">
    <?php foreach ($sliderPosts as $post): ?>
        <div class="slide">
            <a href="profile.php?id=<?php echo (int)$post['user_id']; ?>">
                <img src="<?php echo escape($post['image_url']); ?>" alt="Изображение из поста пользователя <?php echo escape($post['username']); ?>">
                <div class="slide-username">@<?php echo escape($post['username']); ?></div>
            </a>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Форма создания поста убрана с ленты по просьбе заказчика — публикация
     теперь доступна только на странице своего профиля (profile.php),
     чтобы не загромождать общую ленту. -->

<div class="feed-controls">
    <div class="search-bar">
        <i class="fas fa-search"></i>
        <input type="text" id="searchInput" placeholder="Поиск постов или @пользователей..." value="<?php echo escape($search); ?>">
    </div>
    <div class="filter-buttons" id="filterButtons">
        <button type="button" class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>" data-filter="all">Все</button>
        <button type="button" class="filter-btn <?= $filter === 'image' ? 'active' : '' ?>" data-filter="image">С картинками</button>
        <button type="button" class="filter-btn <?= $filter === 'text' ? 'active' : '' ?>" data-filter="text">Текстовые</button>
    </div>
</div>

<div id="feedResults">
<div class="posts-grid" id="feedGrid">
    <?php if (empty($posts)): ?>
        <p class="empty-state">Ничего не найдено.</p>
    <?php else: foreach ($posts as $post):
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
    endforeach; endif; ?>
</div>
</div>

<?php require_once 'includes/footer.php'; ?>

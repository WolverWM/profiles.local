<?php
/**
 * ============================================================
 * edit_post.php — редактирование своего поста
 * Старая версия текста сохраняется в post_edits (история правок)
 * ============================================================
 */
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/session.php';
require_once 'includes/csrf.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

requireLogin();

$postId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$postId]);
$post = $stmt->fetch();

if (!$post) show404();

// Редактировать может только автор поста или администратор
if (!isOwner((int)$post['user_id']) && !isAdmin()) {
    http_response_code(403);
    exit('Недостаточно прав для редактирования этого поста');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $newTitle = trim($_POST['title'] ?? '');
    $newContent = trim($_POST['content'] ?? '');

    if ($newContent === '') {
        $errors[] = 'Текст поста не может быть пустым';
    } else {
        // Сохраняем старую версию в историю правок перед обновлением
        $pdo->prepare("INSERT INTO post_edits (post_id, content_old, content_new) VALUES (?, ?, ?)")
            ->execute([$post['id'], $post['content'], $newContent]);

        $pdo->prepare("UPDATE posts SET title = ?, content = ?, edited_at = NOW() WHERE id = ?")
            ->execute([$newTitle ?: null, $newContent, $post['id']]);

        redirect('profile.php?id=' . (int)$post['user_id']);
    }
}

$pageTitle = 'Редактирование поста';
require_once 'includes/header.php';
?>

<div class="auth-page">
    <h1><i class="fas fa-pen"></i> Редактировать пост</h1>

    <?php foreach ($errors as $error): ?>
        <p class="alert error"><?php echo escape($error); ?></p>
    <?php endforeach; ?>

    <form method="POST" class="auth-form">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="id" value="<?php echo (int)$post['id']; ?>">

        <label>Заголовок</label>
        <input type="text" name="title" maxlength="100" value="<?php echo escape($_POST['title'] ?? $post['title'] ?? ''); ?>">

        <label>Текст</label>
        <textarea name="content" rows="6" required><?php echo escape($_POST['content'] ?? $post['content']); ?></textarea>

        <?php if ($post['image_url']): ?>
            <label>Текущее изображение (замена изображения не поддерживается — при необходимости удалите пост и создайте новый)</label>
            <img src="<?php echo escape($post['image_url']); ?>" alt="Текущее изображение поста" style="max-width:200px;border-radius:16px;margin-bottom:12px;">
        <?php endif; ?>

        <button type="submit" class="btn">Сохранить изменения</button>
    </form>
    <p class="auth-switch"><a href="profile.php?id=<?php echo (int)$post['user_id']; ?>">← Отмена</a></p>
</div>

<?php require_once 'includes/footer.php'; ?>

<?php
/**
 * ============================================================
 * 404.php — страница «не найдено»
 * Может открываться напрямую или подключаться через show404()
 * из includes/functions.php (например, если профиль не найден)
 * ============================================================
 */
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/session.php';
require_once 'includes/csrf.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

if (!headers_sent()) {
    http_response_code(404);
}

$pageTitle = 'Страница не найдена';
require_once 'includes/header.php';
?>

<div class="error-page">
    <div class="error-icon">📄</div>
    <h1>404 — Страница не найдена</h1>
    <p>Такой страницы не существует или она была удалена.</p>
    <a href="index.php" class="btn"><i class="fas fa-house"></i> Вернуться на главную</a>
</div>

<?php require_once 'includes/footer.php'; ?>

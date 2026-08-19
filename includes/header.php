<?php
/**
 * ============================================================
 * header.php
 * Шапка сайта: навигация с фиксированным аватаром + CSS-переменные
 * для динамической цветовой схемы пользователя (задание 2.2, 2.8, 8)
 * ============================================================
 */

$isLoggedIn = isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
$user = $isLoggedIn ? $_SESSION['user'] : null;
$isAdmin = $isLoggedIn && ($user['role'] ?? '') === 'admin';
$avatarUrl = avatarSrc($user['avatar_url'] ?? null);

// Цветовая схема закреплена за пользователем, а не за сессией (задание "динамическая
// цветовая схема"): каждая страница может ДО подключения header.php задать
// $themeUser — чей именно набор цветов показывать (например, profile.php
// передаёт сюда владельца просматриваемого профиля). Если страница этого не
// сделала — используем цвета текущего авторизованного пользователя (лента,
// личный кабинет и т.д.), как и раньше. Гостю или незалогиненному владельцу —
// цвета по умолчанию.
$themeUser = $themeUser ?? $user ?? [];
$primaryColor = $themeUser['primary_color'] ?? '#6b8b82';
$textColor = $themeUser['text_color'] ?? '#ffffff';
$buttonColor = $themeUser['button_color'] ?? null;
$bgColor = $themeUser['bg_color'] ?? null;
$buttonTextColor = $themeUser['button_text_color'] ?? null;
$currentPage = basename($_SERVER['PHP_SELF']);

// Если кнопки/фон не заданы отдельно (галочка выключена) — вычисляем автоматически
// Фон = основной цвет, затемнённый на 15%. Кнопки = основной цвет, осветлённый на 25%.
$finalButtonColor = ($buttonColor && $buttonColor !== 'default') ? $buttonColor : lightenColor($primaryColor, 25);
$finalBgColor = ($bgColor && $bgColor !== 'default') ? $bgColor : darkenColor($primaryColor, 15);
// Цвет текста на кнопках — отдельная настройка (задание "Раздельный цвет текста кнопок"),
// по умолчанию (не задан или 'default') совпадает с общим цветом текста темы, как и раньше.
$finalButtonTextColor = ($buttonTextColor && $buttonTextColor !== 'default') ? $buttonTextColor : $textColor;
$showTutorial = $isLoggedIn && empty($user['tutorial_completed']);

// SEO: заголовок и описание страницы. Каждая страница может задать свои
// $pageTitle / $pageDescription ДО подключения header.php — если не задала,
// используется общее описание сайта (внутренняя SEO-оптимизация, раздел 6 ТЗ).
$metaTitle = isset($pageTitle) ? escape($pageTitle) . ' — ' . SITE_NAME : SITE_NAME;
$metaDescription = $pageDescription ?? 'Profiles — локальная социальная сеть-портфолио: публикуйте посты, собирайте портфолио и находите единомышленников.';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $metaTitle; ?></title>
    <meta name="description" content="<?php echo escape($metaDescription); ?>">
    <!-- Open Graph — внешняя SEO-оптимизация: корректное превью при расшаривании ссылки -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo $metaTitle; ?>">
    <meta property="og:description" content="<?php echo escape($metaDescription); ?>">
    <meta property="og:image" content="<?php echo escape(DEFAULT_AVATAR); ?>">
    <meta property="og:locale" content="ru_RU">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php /* ?v=время последнего изменения файла — при каждой правке style.css ссылка
             меняется, и браузер не показывает старую версию из кэша (иначе после
             обновления файлов на сервере пользователи какое-то время видели бы
             старые стили, пока кэш не истечёт сам) */ ?>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo filemtime(ROOT_PATH . '/assets/css/style.css'); ?>">
    <style>
        /* Динамическая цветовая схема — переменные грузятся из БД (раздел 8 ТЗ) */
        :root {
            --primary-color: <?php echo escape($primaryColor); ?>;
            --button-color: <?php echo escape($finalButtonColor); ?>;
            --bg-color: <?php echo escape($finalBgColor); ?>;
            --text-color: <?php echo escape($textColor); ?>;
            --button-text-color: <?php echo escape($finalButtonTextColor); ?>;
        }
        body {
            background: linear-gradient(135deg, var(--primary-color), var(--bg-color));
        }
    </style>
    <script>
        window.CSRF_TOKEN = "<?php echo csrf_token(); ?>";
        window.USER_ID = <?php echo $user['id'] ?? 'null'; ?>;
        window.SHOW_TUTORIAL = <?php echo $showTutorial ? 'true' : 'false'; ?>;
    </script>
</head>
<body>

<div class="container">

<!-- ============================================================
     ФИКСИРОВАННАЯ НАВИГАЦИЯ С АВАТАРОМ (задание 2.2)
     ============================================================ -->
<nav>
    <!-- Аватар слева: клик -> в свой профиль -->
    <div class="nav-avatar">
        <?php if ($isLoggedIn): ?>
            <a href="profile.php?id=<?= (int)$user['id'] ?>" title="Мой профиль">
                <img src="<?= escape($avatarUrl) ?>" alt="Аватар пользователя <?= escape($user['username']) ?> — перейти в свой профиль" width="56" height="56">
            </a>
        <?php else: ?>
            <a href="login.php" title="Войти">
                <img src="<?= escape(DEFAULT_AVATAR) ?>" alt="Войти в аккаунт" width="56" height="56">
            </a>
        <?php endif; ?>
    </div>

    <!-- Левая часть: Лента / Профиль -->
    <div class="nav-center">
        <a href="index.php" class="<?= $currentPage == 'index.php' ? 'active' : '' ?>">
            <i class="fas fa-rss"></i> <span>Лента</span>
        </a>
        <?php if ($isLoggedIn): ?>
            <a href="profile.php?id=<?= (int)$user['id'] ?>" class="<?= $currentPage == 'profile.php' ? 'active' : '' ?>">
                <i class="fas fa-user"></i> <span>Профиль</span>
            </a>
        <?php endif; ?>
    </div>

    <!-- Правая часть: Настройки/Выйти или Войти/Регистрация -->
    <div class="nav-right">
        <?php if ($isLoggedIn): ?>
            <a href="dashboard.php" class="<?= $currentPage == 'dashboard.php' ? 'active' : '' ?>">
                <i class="fas fa-sliders-h"></i> <span>Настройки</span>
            </a>
            <a href="logout.php">
                <i class="fas fa-sign-out-alt"></i> <span>Выйти</span>
            </a>
        <?php else: ?>
            <a href="login.php" class="<?= $currentPage == 'login.php' ? 'active' : '' ?>">
                <i class="fas fa-sign-in-alt"></i> <span>Войти</span>
            </a>
            <a href="register.php" class="<?= $currentPage == 'register.php' ? 'active' : '' ?>">
                <i class="fas fa-user-plus"></i> <span>Регистрация</span>
            </a>
        <?php endif; ?>
    </div>
</nav>

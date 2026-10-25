<?php
declare(strict_types=1);

// ============================================================
// functions.php — вспомогательные функции, используются везде
// ============================================================

function escape(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): void {
    if (!headers_sent()) {
        header('Location: ' . $url, true, 302);
    }
    exit;
}

// Показывает страницу 404.php с правильным HTTP-статусом и завершает скрипт
function show404(): void {
    http_response_code(404);
    require __DIR__ . '/../404.php';
    exit;
}

function timeAgo(?string $datetime): string {
    if (empty($datetime)) return '';
    try {
        $time = new DateTime($datetime);
    } catch (Exception $e) {
        return '';
    }
    $now = new DateTime();
    if ($time > $now) return 'только что';
    $diff = $now->getTimestamp() - $time->getTimestamp();
    if ($diff < 10) return 'только что';
    if ($diff < 60) return $diff . ' сек назад';
    $minutes = floor($diff / 60);
    if ($minutes < 60) return $minutes . ' мин назад';
    $hours = floor($minutes / 60);
    if ($hours < 24) return $hours . ' ч назад';
    $days = floor($hours / 24);
    if ($days === 1) return 'вчера';
    if ($days < 7) return $days . ' дн назад';
    $weeks = floor($days / 7);
    if ($weeks < 5) return $weeks . ' нед назад';
    $months = floor($days / 30);
    if ($months < 12) return $months . ' мес назад';
    $years = floor($days / 365);
    return $years . ' г назад';
}

function isImage(?string $filename): bool {
    if (empty($filename)) return false;
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
}

function validateEmail(?string $email): bool {
    if ($email === null) return false;
    $email = trim($email);
    if ($email === '') return false;
    if (mb_strlen($email) > 100) return false;
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validateUsername(?string $username): bool {
    if ($username === null) return false;
    $username = trim($username);
    if ($username === '') return false;
    $length = mb_strlen($username);
    if ($length < 3 || $length > 50) return false;
    return preg_match('/^[A-Za-z0-9_-]+$/', $username) === 1;
}

// Упрощённое требование к паролю (курсовая, PHP 7.4): минимум 6 символов
function validatePassword(?string $password): bool {
    if ($password === null) return false;
    $length = strlen($password);
    return $length >= 6 && $length <= 255;
}

function userColor(?string $hexColor): string {
    $lightText = '#FFFFFF';
    $darkText = '#1A1A2E';
    if ($hexColor === null) return $darkText;
    $hexColor = trim($hexColor);
    if ($hexColor === '') return $darkText;
    $hexColor = ltrim($hexColor, '#');
    if (strlen($hexColor) === 3) {
        $hexColor = $hexColor[0] . $hexColor[0] .
                    $hexColor[1] . $hexColor[1] .
                    $hexColor[2] . $hexColor[2];
    }
    if (strlen($hexColor) !== 6 || !ctype_xdigit($hexColor)) {
        return $darkText;
    }
    $red = hexdec(substr($hexColor, 0, 2));
    $green = hexdec(substr($hexColor, 2, 2));
    $blue = hexdec(substr($hexColor, 4, 2));
    $brightness = (($red * 299) + ($green * 587) + ($blue * 114)) / 1000;
    return ($brightness >= 186) ? $darkText : $lightText;
}

function renderAvatar(?string $avatarPath, string $class = '', int $width = 60, int $height = 60, string $username = 'User'): string {
    $defaultAvatar = DEFAULT_AVATAR;
    $avatarPath = trim((string)$avatarPath);
    if ($avatarPath === '' || !file_exists(ROOT_PATH . '/' . $avatarPath)) {
        $avatarPath = $defaultAvatar;
    }
    $classes = trim('avatar ' . $class);
    $alt = trim($username);
    if ($alt === '') $alt = 'User';
    $alt .= ' avatar';
    return sprintf(
        '<img src="%s" class="%s" width="%d" height="%d" alt="%s" loading="lazy" decoding="async">',
        escape($avatarPath),
        escape($classes),
        max(1, $width),
        max(1, $height),
        escape($alt)
    );
}

// Возвращает путь к аватару пользователя (с проверкой, что файл существует на диске)
function avatarSrc(?string $avatarPath): string {
    $avatarPath = trim((string)$avatarPath);
    if ($avatarPath === '' || !file_exists(ROOT_PATH . '/' . $avatarPath)) {
        return DEFAULT_AVATAR;
    }
    return $avatarPath;
}

// Возвращает URL фонового изображения карточки профиля, только если оно
// задано и файл действительно существует на диске; иначе — null (тогда
// карточка остаётся полностью на цветовом фоне, без картинки)
function profileBgSrc(?string $bgPath): ?string {
    $bgPath = trim((string)$bgPath);
    if ($bgPath === '' || !file_exists(ROOT_PATH . '/' . $bgPath)) {
        return null;
    }
    return $bgPath;
}

// Загружает изображение (аватар, пост или фон карточки профиля) и возвращает
// как путь на диске, так и URL для <img src>. $allowedExtensions/$allowedMimeTypes
// позволяют задать свой список форматов для конкретной загрузки (например,
// для фона профиля запрещён GIF) — если не переданы, берутся общие константы.
function uploadImage(array $file, string $uploadDir, string $uploadUrl, int $maxSize = 5242880, ?array $allowedExtensions = null, ?array $allowedMimeTypes = null): array {
    $allowedExtensions = $allowedExtensions ?? ALLOWED_IMAGE_EXTENSIONS;
    $allowedMimeTypes = $allowedMimeTypes ?? ALLOWED_IMAGE_TYPES;

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'Файл слишком большой (превышен лимит PHP)',
            UPLOAD_ERR_FORM_SIZE => 'Файл слишком большой',
            UPLOAD_ERR_PARTIAL => 'Файл загружен частично',
            UPLOAD_ERR_NO_FILE => 'Файл не выбран',
            UPLOAD_ERR_NO_TMP_DIR => 'Отсутствует временная папка',
            UPLOAD_ERR_CANT_WRITE => 'Ошибка записи на диск',
            UPLOAD_ERR_EXTENSION => 'Загрузка остановлена расширением PHP',
        ];
        return ['success' => false, 'url' => '', 'error' => $errors[$file['error']] ?? 'Неизвестная ошибка загрузки'];
    }
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'url' => '', 'error' => 'Файл слишком большой. Максимальный размер: ' . ($maxSize / 1024 / 1024) . ' МБ'];
    }
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) {
        return ['success' => false, 'url' => '', 'error' => 'Недопустимый формат. Разрешены: ' . implode(', ', $allowedExtensions)];
    }
    if (!function_exists('finfo_open')) {
        return ['success' => false, 'url' => '', 'error' => 'Расширение Fileinfo не установлено'];
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mimeType, $allowedMimeTypes, true)) {
        return ['success' => false, 'url' => '', 'error' => 'Файл не является изображением или имеет недопустимый MIME-тип'];
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'url' => '', 'error' => 'Подозрительный файл'];
    }
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            return ['success' => false, 'url' => '', 'error' => 'Не удалось создать папку для загрузки'];
        }
    }
    $newFilename = bin2hex(random_bytes(16)) . '.' . $extension;
    $destination = rtrim($uploadDir, '/') . '/' . $newFilename;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'url' => '', 'error' => 'Не удалось сохранить файл'];
    }
    // В базу пишем ОТНОСИТЕЛЬНЫЙ url (для <img src>), а не путь на диске
    return ['success' => true, 'url' => rtrim($uploadUrl, '/') . '/' . $newFilename, 'filename' => $newFilename];
}

function countPosts(PDO $pdo, int $userId): int {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

function countLikes(PDO $pdo, int $userId): int {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM interactions
        WHERE target_id IN (SELECT id FROM posts WHERE user_id = ?) AND type = 'like'
    ");
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

function countFollowers(PDO $pdo, int $userId): int {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM interactions WHERE target_id = ? AND type = 'follow'");
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

function countFollowing(PDO $pdo, int $userId): int {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM interactions WHERE user_id = ? AND type = 'follow'");
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

// Подписан ли $userId на $targetId
function isFollowing(PDO $pdo, int $userId, int $targetId): bool {
    if (!$userId || !$targetId) return false;
    $stmt = $pdo->prepare("SELECT 1 FROM interactions WHERE user_id = ? AND target_id = ? AND type = 'follow'");
    $stmt->execute([$userId, $targetId]);
    return (bool) $stmt->fetchColumn();
}

// ============================================================
// БЕЙДЖ ПРОФЕССИОНАЛЬНОГО СТАТУСА
// ============================================================

function statusBadgeOptions(): array {
    return [
        'active'    => 'Активно работаю',
        'looking'   => 'Ищу предложения',
        'vacation'  => 'В отпуске',
        'freelance' => 'Фриланс',
        'student'   => 'Студент',
    ];
}

function statusBadgeLabel(?string $badge): string {
    $options = statusBadgeOptions();
    // Если это не один из пяти стандартных ключей — значит, пользователь
    // ввёл статус вручную (см. "Другое" в личном кабинете), и сам текст
    // статуса уже и есть подпись бейджа.
    return $options[$badge] ?? (string)$badge;
}

// CSS-класс цвета бейджа. Для произвольного (кастомного) статуса
// стандартного класса нет — используем нейтральный badge-custom,
// иначе имя класса пришлось бы собирать из текста пользователя
// (пробелы и спецсимволы сломали бы атрибут class).
function statusBadgeClass(?string $badge): string {
    return array_key_exists((string)$badge, statusBadgeOptions()) ? 'badge-' . $badge : 'badge-custom';
}

// ============================================================
// СОЦИАЛЬНЫЕ ССЫЛКИ
// ============================================================

// Список платформ: ключ = колонка в БД (без _url), иконка Font Awesome, подпись
function socialPlatforms(): array {
    return [
        'instagram' => ['label' => 'Instagram', 'icon' => 'fab fa-instagram'],
        'telegram'  => ['label' => 'Telegram',  'icon' => 'fab fa-telegram'],
        'github'    => ['label' => 'GitHub',    'icon' => 'fab fa-github'],
        'vk'        => ['label' => 'VK',        'icon' => 'fab fa-vk'],
        // PayPal хранит email или ID кошелька, а не ссылку — см. socialLinkHref()
        'paypal'    => ['label' => 'PayPal',    'icon' => 'fas fa-wallet'],
        // Boosty — платформа для поддержки авторов (популярна в СНГ), обычная ссылка на профиль.
        // У Font Awesome нет фирменной иконки Boosty, поэтому берём похожую по смыслу.
        'boosty'    => ['label' => 'Boosty',    'icon' => 'fas fa-mug-hot'],
    ];
}

// Строит правильную ссылку для иконки соцсети (задание "Социальные ссылки").
// Для большинства платформ значение из БД уже готовая ссылка — используем как есть.
// Для PayPal значение — это email или ID кошелька, а не URL, поэтому собираем
// ссылку сами: email -> mailto:, иначе считаем это именем на paypal.me.
// Для Telegram значение — имя пользователя (с @ или без), а не ссылка —
// собираем ссылку на t.me сами.
function socialLinkHref(string $platform, string $value): string {
    if ($platform === 'paypal') {
        if (strpos($value, '@') !== false) {
            return 'mailto:' . $value;
        }
        return 'https://paypal.me/' . rawurlencode($value);
    }
    if ($platform === 'telegram') {
        return 'https://t.me/' . rawurlencode(ltrim($value, '@'));
    }
    return $value;
}

// БД-колонка, в которой хранится значение соцсети — для большинства платформ
// это "<ключ>_url" (общее правило), но у Telegram теперь не ссылка, а имя
// пользователя, поэтому колонка называется иначе. Вынесено отдельной функцией,
// чтобы profile.php мог собирать соцссылки в одном общем цикле по всем
// платформам, не выписывая Telegram отдельным условием в разметке.
function socialFieldName(string $platform): string {
    return $platform === 'telegram' ? 'telegram_username' : $platform . '_url';
}

// QR-код Telegram — та же логика, что и у profileBgSrc(): вернуть URL, только
// если файл реально существует на диске, иначе null (значит, не загружен)
function telegramQrSrc(?string $qrPath): ?string {
    $qrPath = trim((string)$qrPath);
    if ($qrPath === '' || !file_exists(ROOT_PATH . '/' . $qrPath)) {
        return null;
    }
    return $qrPath;
}

// ============================================================
// РАЗМЕР СЕТКИ ПРОФИЛЯ
// ============================================================

function gridSizeOptions(): array {
    return [1 => 'Маленький', 2 => 'Средний', 3 => 'Большой'];
}

function gridSizeClass(?int $size): string {
    $classes = [1 => 'grid-small', 2 => 'grid-medium', 3 => 'grid-large'];
    return $classes[$size] ?? 'grid-medium';
}

// ============================================================
// ОТРИСОВКА КАРТОЧКИ ПОСТА
// Используется в index.php, profile.php и ajax/search.php,
// чтобы разметка везде была одинаковой
// ============================================================
// Ожидает ключи: id, user_id, username, avatar_url, created_at, title,
// content, image_url, likes_count, liked, image_only
function renderPostCard(array $p, bool $loggedIn, bool $draggable = false): string {
    $imageOnly = !empty($p['image_only']) && !empty($p['image_url']);
    $draggableAttr = $draggable ? 'draggable="true"' : '';

    $html = '<div class="post-card ' . ($imageOnly ? 'post-card-image-only' : '') . '" '
        . 'data-post-id="' . (int)$p['id'] . '" data-type="post" data-id="' . (int)$p['id'] . '" ' . $draggableAttr . '>';
    $html .= '<div class="post-header">';
    $html .= '<img src="' . escape(avatarSrc($p['avatar_url'])) . '" alt="Аватар пользователя ' . escape($p['username']) . '">';
    $html .= '<a href="profile.php?id=' . (int)$p['user_id'] . '" onclick="event.stopPropagation()">' . escape($p['username']) . '</a>';
    $html .= '<span>' . escape(timeAgo($p['created_at'])) . '</span>';
    $html .= '</div>';
    $html .= '<div class="post-body">';
    if (!empty($p['image_url'])) {
        $imgAlt = !empty($p['title']) ? $p['title'] : ('Изображение к посту пользователя ' . $p['username']);
        $html .= '<img src="' . escape($p['image_url']) . '" class="post-image" alt="' . escape($imgAlt) . '">';
    }
    if (!$imageOnly) {
        if (!empty($p['title'])) $html .= '<h3>' . escape($p['title']) . '</h3>';
        $content = trim((string)$p['content']);
$excerpt = mb_substr($content, 0, 300, 'UTF-8');
if (mb_strlen($content, 'UTF-8') > 300) {
    $excerpt .= '…';
}

$html .= '<p>' . nl2br(escape($excerpt)) . '</p>';
    }
    $html .= '</div>';
    $html .= '<div class="post-footer">';
    if ($loggedIn) {
        $heartClass = !empty($p['liked']) ? 'fas liked' : 'far';
        $html .= '<button type="button" class="like-btn" data-post-id="' . (int)$p['id'] . '">'
            . '<i class="fa-heart ' . $heartClass . '"></i> '
            . '<span class="like-count">' . (int)($p['likes_count'] ?? 0) . '</span></button>';
    } else {
        $html .= '<span class="like-static"><i class="far fa-heart"></i> ' . (int)($p['likes_count'] ?? 0) . '</span>';
    }
    $html .= '</div></div>';
    return $html;
}

// Карточка пользователя для результатов поиска по @username
function renderUserCard(array $u): string {
    return '<a href="profile.php?id=' . (int)$u['id'] . '" class="follow-card">'
        . '<img src="' . escape(avatarSrc($u['avatar_url'])) . '" alt="Аватар пользователя ' . escape($u['username']) . '">'
        . '<span>' . escape($u['username']) . '</span></a>';
}

// ============================================================
// ЦВЕТОВЫЕ ФУНКЦИИ
// ============================================================

// Затемняет HEX-цвет на указанный процент (используется для фона)
function darkenColor(string $hex, int $percent): string {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $r = max(0, $r - ($r * $percent / 100));
    $g = max(0, $g - ($g * $percent / 100));
    $b = max(0, $b - ($b * $percent / 100));
    return sprintf("#%02x%02x%02x", $r, $g, $b);
}

// Осветляет HEX-цвет на указанный процент (используется для кнопок)
function lightenColor(string $hex, int $percent): string {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $r = min(255, $r + ((255 - $r) * $percent / 100));
    $g = min(255, $g + ((255 - $g) * $percent / 100));
    $b = min(255, $b + ((255 - $b) * $percent / 100));
    return sprintf("#%02x%02x%02x", $r, $g, $b);
}

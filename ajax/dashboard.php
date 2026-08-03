<?php
/**
 * ============================================================
 * dashboard.php — личный кабинет: аватар, статус, цвета,
 * сетка, соцссылки, график статистики, (для admin — админ-панель)
 * ============================================================
 */
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/session.php';
require_once 'includes/csrf.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

requireLogin();
$user = currentUser();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    // --- О СЕБЕ / СТАТУС / БЕЙДЖ ---
    if (isset($_POST['update_profile'])) {
        $bio = trim($_POST['bio'] ?? '');
        $profession = trim($_POST['profession'] ?? '');
        $badge = $_POST['status_badge'] ?? '';
        if (!array_key_exists($badge, statusBadgeOptions())) $badge = null;
        $pdo->prepare("UPDATE users SET bio = ?, profession = ?, status_badge = ? WHERE id = ?")
            ->execute([$bio, $profession, $badge, $user['id']]);
        $message = '✅ Профиль обновлён!';
    }

    // --- АВАТАР ---
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload = uploadImage($_FILES['avatar'], AVATAR_UPLOAD_DIR, AVATAR_UPLOAD_URL, MAX_FILE_SIZE);
        if ($upload['success']) {
            $pdo->prepare("UPDATE users SET avatar_url = ? WHERE id = ?")->execute([$upload['url'], $user['id']]);
            $_SESSION['user']['avatar_url'] = $upload['url'];
            $message = '✅ Аватар обновлён!';
        } else {
            $message = '❌ ' . $upload['error'];
        }
    }

    // --- ФОН КАРТОЧКИ ПРОФИЛЯ ---
    if (isset($_POST['update_profile_bg'])) {
        if (isset($_POST['remove_profile_bg'])) {
            // Пользователь захотел вернуться к обычному цветовому фону —
            // удаляем файл с диска (если он был) и очищаем поле в базе
            if (!empty($user['profile_bg_url'])) {
                $oldPath = ROOT_PATH . '/' . $user['profile_bg_url'];
                if (is_file($oldPath)) @unlink($oldPath);
            }
            $pdo->prepare("UPDATE users SET profile_bg_url = NULL WHERE id = ?")->execute([$user['id']]);
            $message = '✅ Фоновое изображение удалено!';
        } elseif (isset($_FILES['profile_bg']) && $_FILES['profile_bg']['error'] !== UPLOAD_ERR_NO_FILE) {
            // Свой (более строгий) набор ограничений: до 2 МБ, без GIF
            $upload = uploadImage(
                $_FILES['profile_bg'],
                PROFILE_BG_UPLOAD_DIR,
                PROFILE_BG_UPLOAD_URL,
                MAX_PROFILE_BG_SIZE,
                ALLOWED_PROFILE_BG_EXTENSIONS,
                ALLOWED_PROFILE_BG_TYPES
            );
            if ($upload['success']) {
                $pdo->prepare("UPDATE users SET profile_bg_url = ? WHERE id = ?")->execute([$upload['url'], $user['id']]);
                $message = '✅ Фон карточки профиля обновлён!';
            } else {
                $message = '❌ ' . $upload['error'];
            }
        }
    }

    // --- ЦВЕТОВАЯ СХЕМА (задание 2.6, 8) ---
    if (isset($_POST['update_colors'])) {
        $primary = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['primary_color'] ?? '') ? $_POST['primary_color'] : '#00BCD4';
        $text = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['text_color'] ?? '') ? $_POST['text_color'] : '#FFFFFF';
        $separate = isset($_POST['separate_colors']) ? 1 : 0;

        // Галочка выключена -> фон/кнопки вычисляются автоматически из основного цвета
        $button = 'default';
        $bg = 'default';
        if ($separate) {
            $button = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['button_color'] ?? '') ? $_POST['button_color'] : $primary;
            $bg = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['bg_color'] ?? '') ? $_POST['bg_color'] : $primary;
        }

        $pdo->prepare("UPDATE users SET primary_color = ?, text_color = ?, button_color = ?, bg_color = ?, separate_colors = ? WHERE id = ?")
            ->execute([$primary, $text, $button, $bg, $separate, $user['id']]);

        $_SESSION['user']['primary_color'] = $primary;
        $_SESSION['user']['text_color'] = $text;
        $_SESSION['user']['button_color'] = $button;
        $_SESSION['user']['bg_color'] = $bg;
        $message = '✅ Цветовая схема обновлена!';
    }

    // --- РАЗМЕР СЕТКИ И ФОРМАТ ПОСТОВ ---
    if (isset($_POST['update_display'])) {
        $gridSize = (int)($_POST['grid_size'] ?? 2);
        if (!array_key_exists($gridSize, gridSizeOptions())) $gridSize = 2;
        $displayStyle = ($_POST['post_display_style'] ?? 'standard') === 'image_only' ? 'image_only' : 'standard';
        $pdo->prepare("UPDATE users SET grid_size = ?, post_display_style = ? WHERE id = ?")
            ->execute([$gridSize, $displayStyle, $user['id']]);
        $message = '✅ Настройки отображения сохранены!';
    }

    // --- СОЦИАЛЬНЫЕ ССЫЛКИ ---
    if (isset($_POST['update_social'])) {
        $values = [];
        foreach (array_keys(socialPlatforms()) as $key) {
            $raw = trim($_POST[$key . '_url'] ?? '');

            if ($raw === '') {
                $values[$key] = null;
            } elseif ($key === 'paypal') {
                // PayPal — это email или ID кошелька, а не ссылка, поэтому
                // проверяем только длину, а не формат URL (иначе поле сохранить не получится)
                $values[$key] = (mb_strlen($raw) <= 100) ? $raw : null;
            } else {
                // Если пользователь забыл написать "https://" — молча дописываем сами,
                // иначе filter_var() отбраковывает такую ссылку и поле тихо не сохраняется
                if (!preg_match('~^https?://~i', $raw)) {
                    $raw = 'https://' . $raw;
                }
                $values[$key] = filter_var($raw, FILTER_VALIDATE_URL) ? $raw : null;
            }
        }
        $pdo->prepare("UPDATE users SET instagram_url=?, telegram_url=?, github_url=?, vk_url=?, paypal_url=?, boosty_url=? WHERE id = ?")
            ->execute([$values['instagram'], $values['telegram'], $values['github'], $values['vk'], $values['paypal'], $values['boosty'], $user['id']]);
        $message = '✅ Социальные ссылки сохранены!';
    }

    // --- АДМИН: УДАЛЕНИЕ ПОЛЬЗОВАТЕЛЯ ---
    if (isAdmin() && isset($_POST['delete_user_id'])) {
        $targetId = (int)$_POST['delete_user_id'];
        if ($targetId && $targetId !== (int)$user['id']) {
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$targetId]);
            $message = '✅ Пользователь удалён';
        }
    }
}

// Перечитываем актуальные данные пользователя из БД
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user['id']]);
$user = $stmt->fetch();
$_SESSION['user'] = array_merge($_SESSION['user'], $user);

$pageTitle = 'Настройки';
require_once 'includes/header.php';
?>

<div class="dashboard">
    <h1><i class="fas fa-sliders-h"></i> Настройки профиля</h1>
    <?php if ($message): ?>
        <div class="success"><?php echo escape($message); ?></div>
    <?php endif; ?>

    <!-- АВАТАР -->
    <form method="POST" enctype="multipart/form-data" class="settings-form">
        <?php echo csrf_field(); ?>
        <h3><i class="fas fa-camera"></i> Фото профиля</h3>
        <div class="avatar-upload">
            <img src="<?php echo escape(avatarSrc($user['avatar_url'])); ?>" class="preview-avatar" alt="Текущий аватар пользователя <?php echo escape($user['username']); ?>">
            <input type="file" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp">
        </div>
        <p class="field-hint">jpg, png, gif, webp — до 5 МБ</p>
        <button type="submit">Загрузить</button>
    </form>

    <!-- ФОН КАРТОЧКИ ПРОФИЛЯ -->
    <form method="POST" enctype="multipart/form-data" class="settings-form">
        <?php echo csrf_field(); ?>
        <h3><i class="fas fa-image"></i> Фон карточки профиля</h3>
        <?php $currentBg = profileBgSrc($user['profile_bg_url'] ?? null); ?>
        <?php if ($currentBg): ?>
            <img src="<?php echo escape($currentBg); ?>" class="preview-bg" alt="Текущий фон карточки профиля">
            <div class="theme-group checkbox-group">
                <label>
                    <input type="checkbox" name="remove_profile_bg" value="1"> Удалить текущее изображение
                </label>
            </div>
        <?php endif; ?>
        <input type="file" name="profile_bg" accept="image/jpeg,image/png,image/webp">
        <p class="field-hint">jpg, png, webp — до 2 МБ. Изображение будет полупрозрачным фоном шапки профиля (стекло остаётся видно поверх).</p>
        <button type="submit" name="update_profile_bg">Сохранить</button>
    </form>

    <!-- О СЕБЕ / СТАТУС / БЕЙДЖ -->
    <form method="POST" class="settings-form">
        <?php echo csrf_field(); ?>
        <h3><i class="fas fa-id-card"></i> О себе</h3>
        <label>Статус (род деятельности)</label>
        <input type="text" name="profession" maxlength="100" placeholder="Например: Frontend-разработчик" value="<?php echo escape($user['profession'] ?? ''); ?>">

        <label>Профессиональный статус</label>
        <select name="status_badge">
            <option value="">— не выбрано —</option>
            <?php foreach (statusBadgeOptions() as $value => $label): ?>
                <option value="<?php echo escape($value); ?>" <?= ($user['status_badge'] ?? '') === $value ? 'selected' : '' ?>><?php echo escape($label); ?></option>
            <?php endforeach; ?>
        </select>

        <label>О себе</label>
        <textarea name="bio" placeholder="Расскажите немного о себе..."><?php echo escape($user['bio'] ?? ''); ?></textarea>

        <button type="submit" name="update_profile">Сохранить</button>
    </form>

    <!-- ЦВЕТОВАЯ СХЕМА -->
    <form method="POST" class="settings-form" id="colorForm">
        <?php echo csrf_field(); ?>
        <h3><i class="fas fa-palette"></i> Цветовая схема</h3>

        <div class="theme-group checkbox-group">
            <label>
                <input type="checkbox" name="separate_colors" value="1"
                       <?php echo !empty($user['separate_colors']) ? 'checked' : ''; ?>
                       id="separateColorsCheckbox">
                Раздельная настройка фона и кнопок
            </label>
        </div>

        <!-- Все цветовые пикеры в один ряд (компактной сеткой на узких экранах) -->
        <div class="theme-colors-row">
            <div class="theme-group">
                <label>Основной цвет</label>
                <input type="color" name="primary_color" value="<?php echo escape($user['primary_color'] ?? '#00BCD4'); ?>">
            </div>

            <div class="theme-group">
                <label>Цвет шрифта</label>
                <input type="color" name="text_color" value="<?php echo escape($user['text_color'] ?? '#FFFFFF'); ?>">
            </div>

            <div class="theme-group" id="bgColorGroup" style="<?php echo !empty($user['separate_colors']) ? '' : 'display:none;'; ?>">
                <label>Цвет фона</label>
                <input type="color" name="bg_color" value="<?php echo escape((!empty($user['bg_color']) && $user['bg_color'] !== 'default') ? $user['bg_color'] : darkenColor($user['primary_color'] ?? '#00BCD4', 15)); ?>">
            </div>
            <div class="theme-group" id="buttonColorGroup" style="<?php echo !empty($user['separate_colors']) ? '' : 'display:none;'; ?>">
                <label>Цвет кнопок</label>
                <input type="color" name="button_color" value="<?php echo escape((!empty($user['button_color']) && $user['button_color'] !== 'default') ? $user['button_color'] : lightenColor($user['primary_color'] ?? '#00BCD4', 25)); ?>">
            </div>
        </div>

        <button type="submit" name="update_colors">Применить схему</button>
    </form>

    <!-- СЕТКА И ФОРМАТ ОТОБРАЖЕНИЯ -->
    <form method="POST" class="settings-form">
        <?php echo csrf_field(); ?>
        <h3><i class="fas fa-table-cells"></i> Отображение профиля</h3>

        <label>Размер сетки</label>
        <select name="grid_size">
            <?php foreach (gridSizeOptions() as $value => $label): ?>
                <option value="<?php echo $value; ?>" <?= (int)($user['grid_size'] ?? 2) === $value ? 'selected' : '' ?>><?php echo escape($label); ?></option>
            <?php endforeach; ?>
        </select>

        <label>Формат отображения постов</label>
        <select name="post_display_style">
            <option value="standard" <?= ($user['post_display_style'] ?? 'standard') === 'standard' ? 'selected' : '' ?>>Стандартный</option>
            <option value="image_only" <?= ($user['post_display_style'] ?? '') === 'image_only' ? 'selected' : '' ?>>Только изображение</option>
        </select>

        <button type="submit" name="update_display">Сохранить</button>
    </form>

    <!-- СОЦИАЛЬНЫЕ ССЫЛКИ -->
    <form method="POST" class="settings-form">
        <?php echo csrf_field(); ?>
        <h3><i class="fas fa-link"></i> Социальные ссылки</h3>
        <?php foreach (socialPlatforms() as $key => $meta): ?>
            <label><i class="<?php echo escape($meta['icon']); ?>"></i> <?php echo escape($meta['label']); ?></label>
            <?php if ($key === 'paypal'): ?>
                <!-- PayPal: не ссылка, а email или ID кошелька для переводов -->
                <input type="text" name="paypal_url" placeholder="Email или ID кошелька PayPal" maxlength="100" value="<?php echo escape($user['paypal_url'] ?? ''); ?>">
            <?php else: ?>
                <input type="url" name="<?php echo $key; ?>_url" placeholder="https://..." value="<?php echo escape($user[$key . '_url'] ?? ''); ?>">
            <?php endif; ?>
        <?php endforeach; ?>
        <button type="submit" name="update_social">Сохранить ссылки</button>
    </form>

    <!-- ГРАФИК СТАТИСТИКИ -->
    <div class="settings-form">
        <h3><i class="fas fa-chart-line"></i> Статистика за неделю</h3>
        <canvas id="statsChart" height="220"></canvas>
    </div>

    <?php if (isAdmin()): ?>
    <!-- ============================================================
         АДМИН-ПАНЕЛЬ: доступна только роли admin (задание "Роли")
         ============================================================ -->
    <div class="settings-form admin-panel">
        <h3><i class="fas fa-user-shield"></i> Админ-панель</h3>
        <p class="field-hint">Управление пользователями. Удаление необратимо (посты пользователя удалятся каскадно).</p>
        <table class="admin-table">
            <thead><tr><th>ID</th><th>Пользователь</th><th>Email</th><th>Роль</th><th></th></tr></thead>
            <tbody>
                <?php
                $allUsers = $pdo->query("SELECT id, username, email, role FROM users ORDER BY id ASC")->fetchAll();
                foreach ($allUsers as $u):
                ?>
                <tr>
                    <td><?php echo (int)$u['id']; ?></td>
                    <td><a href="profile.php?id=<?php echo (int)$u['id']; ?>"><?php echo escape($u['username']); ?></a></td>
                    <td><?php echo escape($u['email']); ?></td>
                    <td><?php echo escape($u['role']); ?></td>
                    <td>
                        <?php if ((int)$u['id'] !== (int)$user['id']): ?>
                        <form method="POST" onsubmit="return confirm('Удалить пользователя <?php echo escape($u['username']); ?>?');" style="display:inline;">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="delete_user_id" value="<?php echo (int)$u['id']; ?>">
                            <button type="submit" class="btn-danger-small"><i class="fas fa-trash"></i></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <a href="logout.php" class="btn btn-secondary logout-link"><i class="fas fa-sign-out-alt"></i> Выйти из аккаунта</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script src="assets/js/dashboard.js"></script>

<?php require_once 'includes/footer.php'; ?>

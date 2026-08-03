<?php
/**
 * ============================================================
 * login.php — вход по email или имени пользователя
 * ============================================================
 */
require_once 'includes/config.php';
require_once 'includes/db_connect.php';
require_once 'includes/session.php';
require_once 'includes/csrf.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

if (loggedIn()) redirect('index.php');

$errors = [];
$login = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($login === '' || $password === '') {
        $errors[] = 'Заполните все поля';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$login, $login]);
        $found = $stmt->fetch();

        // Одна и та же ошибка и для несуществующего логина, и для неверного пароля —
        // чтобы нельзя было угадать, какие email/username зарегистрированы
        if ($found && password_verify($password, $found['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => $found['id'],
                'username' => $found['username'],
                'email' => $found['email'],
                'avatar_url' => $found['avatar_url'],
                'role' => $found['role'] ?? 'user',
                'primary_color' => $found['primary_color'] ?? '#00BCD4',
                'text_color' => $found['text_color'] ?? '#FFFFFF',
                'button_color' => $found['button_color'] ?? 'default',
                'bg_color' => $found['bg_color'] ?? 'default',
                'tutorial_completed' => (int)($found['tutorial_completed'] ?? 0),
            ];
            $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$found['id']]);
            redirect('index.php');
        } else {
            $errors[] = 'Неверный логин или пароль';
        }
    }
}

$pageTitle = 'Вход';
require_once 'includes/header.php';
?>

<div class="auth-page">
    <h1><i class="fas fa-sign-in-alt"></i> Вход</h1>

    <?php if (isset($_GET['registered'])): ?>
        <p class="alert success">✅ Регистрация успешна! Теперь войдите.</p>
    <?php endif; ?>
    <?php foreach ($errors as $error): ?>
        <p class="alert error"><?php echo escape($error); ?></p>
    <?php endforeach; ?>

    <form method="POST" class="auth-form" data-validate novalidate>
        <?php echo csrf_field(); ?>
        <label for="login">Имя пользователя или Email</label>
        <input type="text" id="login" name="login" value="<?php echo escape($login); ?>" autofocus data-rule="required">

        <label for="password">Пароль</label>
        <input type="password" id="password" name="password" data-rule="required">

        <button type="submit" class="btn">Войти</button>
    </form>
    <p class="auth-switch">Нет аккаунта? <a href="register.php">Зарегистрируйтесь</a></p>
</div>

<?php require_once 'includes/footer.php'; ?>

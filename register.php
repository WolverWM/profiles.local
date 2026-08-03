<?php
/**
 * ============================================================
 * register.php — регистрация нового пользователя
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
$username = $email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if (!validateUsername($username)) {
        $errors[] = 'Имя пользователя: 3-50 символов, латиница/цифры/_/-';
    }
    if (!validateEmail($email)) {
        $errors[] = 'Некорректный email';
    }
    if (!validatePassword($password)) {
        $errors[] = 'Пароль должен быть не короче 6 символов';
    }
    if ($password !== $passwordConfirm) {
        $errors[] = 'Пароли не совпадают';
    }

    // Проверка уникальности логина и email
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $errors[] = 'Пользователь с таким именем или email уже существует';
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)")
            ->execute([$username, $email, $hash]);
        redirect('login.php?registered=1');
    }
}

$pageTitle = 'Регистрация';
require_once 'includes/header.php';
?>

<div class="auth-page">
    <h1><i class="fas fa-user-plus"></i> Регистрация</h1>

    <?php foreach ($errors as $error): ?>
        <p class="alert error"><?php echo escape($error); ?></p>
    <?php endforeach; ?>

    <form method="POST" class="auth-form" data-validate novalidate>
        <?php echo csrf_field(); ?>
        <label for="username">Имя пользователя</label>
        <input type="text" id="username" name="username" value="<?php echo escape($username); ?>" maxlength="50" data-rule="required|username">

        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?php echo escape($email); ?>" maxlength="100" data-rule="required|email">

        <label for="password">Пароль</label>
        <input type="password" id="password" name="password" data-rule="required|minlength:6">

        <label for="password_confirm">Повторите пароль</label>
        <input type="password" id="password_confirm" name="password_confirm" data-rule="required|match:password">

        <button type="submit" class="btn">Создать аккаунт</button>
    </form>
    <p class="auth-switch">Уже есть аккаунт? <a href="login.php">Войти</a></p>
</div>

<?php require_once 'includes/footer.php'; ?>

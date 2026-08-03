<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/functions.php';

function loggedIn(): bool {
    return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

function requireLogin(): void {
    if (!loggedIn()) redirect('login.php');
}

function currentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

function isAdmin(): bool {
    return loggedIn() && ($_SESSION['user']['role'] ?? '') === 'admin';
}

function isOwner(int $userId): bool {
    return loggedIn() && ($_SESSION['user']['id'] ?? 0) === $userId;
}
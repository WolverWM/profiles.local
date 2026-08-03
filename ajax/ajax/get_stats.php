<?php
/**
 * ============================================================
 * ajax/get_stats.php — данные для графика Chart.js в dashboard.php
 * Лайки и подписки за последние 7 дней (задание 2.6)
 * ============================================================
 */
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

requireLogin();
$userId = (int)currentUser()['id'];

$dayNames = ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'];
$labels = [];
$dates = [];
for ($i = 6; $i >= 0; $i--) {
    $date = (new DateTime())->modify("-$i day");
    $dates[] = $date->format('Y-m-d');
    $labels[] = $dayNames[(int)$date->format('w')];
}

// Лайки, полученные постами пользователя, по дням
$likesStmt = $pdo->prepare("
    SELECT DATE(created_at) as d, COUNT(*) as c
    FROM interactions
    WHERE type = 'like'
      AND target_id IN (SELECT id FROM posts WHERE user_id = ?)
      AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(created_at)
");
$likesStmt->execute([$userId]);
$likesByDate = array_column($likesStmt->fetchAll(), 'c', 'd');

// Новые подписчики по дням
$followsStmt = $pdo->prepare("
    SELECT DATE(created_at) as d, COUNT(*) as c
    FROM interactions
    WHERE type = 'follow' AND target_id = ?
      AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(created_at)
");
$followsStmt->execute([$userId]);
$followsByDate = array_column($followsStmt->fetchAll(), 'c', 'd');

$likes = [];
$follows = [];
foreach ($dates as $d) {
    $likes[] = (int)($likesByDate[$d] ?? 0);
    $follows[] = (int)($followsByDate[$d] ?? 0);
}

echo json_encode(['labels' => $labels, 'likes' => $likes, 'follows' => $follows]);

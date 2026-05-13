<?php
session_start();
if (empty($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'ログインが必要です']);
    exit;
}
require_once __DIR__ . '/db_connect.php';
$stmt = $pdo->prepare('SELECT category, progress_score, last_activity FROM progress WHERE user_id = :user_id ORDER BY last_activity DESC LIMIT 10');
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$progress = $stmt->fetchAll();
if (empty($progress)) {
    $progress = [
        ['category' => $_SESSION['user_genre'] ?? 'unknown', 'progress_score' => 75, 'last_activity' => date('Y-m-d H:i:s')],
    ];
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'user_name' => $_SESSION['user_name'] ?? null,
    'genre' => $_SESSION['user_genre'] ?? null,
    'progress' => $progress,
]);

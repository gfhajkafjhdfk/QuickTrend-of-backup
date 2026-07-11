<?php
require_once __DIR__ . '/session_boot.php';
header('Content-Type: application/json; charset=utf-8');
if (empty($_SESSION['user_id'])) {//チャット履歴はログインユーザーのみ閲覧できる
    http_response_code(403);
    echo json_encode(['error' => 'ログインが必要です']);
    exit;
}
require_once __DIR__ . '/db_connect.php';
// ジャンル指定があればそのルームのみ、なければ全件（旧クライアント互換）
$genre = $_GET['genre'] ?? '';
if ($genre !== '' && in_array($genre, require __DIR__ . '/chat_genres.php', true)) {
    $stmt = $pdo->prepare('SELECT cm.message, cm.created_at, u.name AS user_name FROM chat_messages cm JOIN users u ON cm.user_id = u.id WHERE cm.genre = :genre ORDER BY cm.created_at DESC LIMIT 50');
    $stmt->execute(['genre' => $genre]);
} else {
    $stmt = $pdo->query('SELECT cm.message, cm.created_at, u.name AS user_name FROM chat_messages cm JOIN users u ON cm.user_id = u.id ORDER BY cm.created_at DESC LIMIT 50');
}
$rows = array_reverse($stmt->fetchAll());
echo json_encode(['messages' => $rows]);

<?php
session_start();
if (empty($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'ログインが必要です']);
    exit;
}
require_once __DIR__ . '/db_connect.php';
$genre = $_SESSION['user_genre'] ?? 'music';
$stmt = $pdo->prepare('SELECT id, name, genre, profile FROM users WHERE genre = :genre AND id != :user_id ORDER BY created_at DESC LIMIT 10');
$stmt->execute(['genre' => $genre, 'user_id' => $_SESSION['user_id']]);
$candidates = $stmt->fetchAll();
if (empty($candidates)) {
    $candidates = [
        ['id' => 0, 'name' => 'サンプル太郎', 'genre' => 'music', 'profile' => '音楽好きのサンプルユーザー'],
    ];
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['candidates' => $candidates]);

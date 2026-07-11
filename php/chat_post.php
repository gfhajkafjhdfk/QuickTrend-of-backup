<?php
require_once __DIR__ . '/session_boot.php';
if (empty($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'ログインが必要です']);
    exit;
}
if (!csrf_verify()) {
    http_response_code(403);
    echo json_encode(['error' => '不正なリクエストです']);
    exit;
}
require_once __DIR__ . '/db_connect.php';
$message = trim($_POST['message'] ?? '');
if ($message === '') {
    http_response_code(400);
    echo json_encode(['error' => 'メッセージが空です']);
    exit;
}
if (mb_strlen($message) > 1000) {//スパム・過大データ対策
    http_response_code(400);
    echo json_encode(['error' => 'メッセージは1000文字以内で入力してください']);
    exit;
}
$stmt = $pdo->prepare('INSERT INTO chat_messages (user_id, message, created_at) VALUES (:user_id, :message, NOW())');
$stmt->execute(['user_id' => $_SESSION['user_id'], 'message' => $message]);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['success' => true]);

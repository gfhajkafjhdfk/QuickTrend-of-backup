<?php
require_once __DIR__ . '/db_connect.php';
header('Content-Type: application/json; charset=utf-8');
$stmt = $pdo->query('SELECT cm.message, cm.created_at, u.name AS user_name FROM chat_messages cm JOIN users u ON cm.user_id = u.id ORDER BY cm.created_at DESC LIMIT 50');
$rows = array_reverse($stmt->fetchAll());
echo json_encode(['messages' => $rows]);

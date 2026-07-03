<?php
require_once __DIR__ . '/session_boot.php';
header('Content-Type: application/json; charset=utf-8');
if (empty($_SESSION['user_id'])) {
    echo json_encode(['logged_in' => false]);
    exit;
}
echo json_encode([
    'logged_in' => true,
    'user_id' => $_SESSION['user_id'],
    'user_name' => $_SESSION['user_name'] ?? null,
    'user_genre' => $_SESSION['user_genre'] ?? null,
]);

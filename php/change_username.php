<?php
// ニックネーム変更API
require_once __DIR__ . '/session_boot.php';
require_once __DIR__ . '/validation.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: settings.php');
    exit;
}
if (empty($_SESSION['user_id'])) {
    header('Location: ../sighin.html?msg=login_required');
    exit;
}
if (!csrf_verify()) {
    header('Location: settings.php?msg=invalid_request');
    exit;
}
require_once __DIR__ . '/db_connect.php';
$name = trim($_POST['name'] ?? '');
if (!valid_username($name)) {
    header('Location: settings.php?msg=invalid_name');
    exit;
}
// 重複確認（自分自身の現在の名前は除く）
$stmt = $pdo->prepare('SELECT id FROM users WHERE name = :name AND id != :id');
$stmt->execute(['name' => $name, 'id' => $_SESSION['user_id']]);
if ($stmt->fetch()) {
    header('Location: settings.php?msg=name_taken');
    exit;
}
try {
    $pdo->prepare('UPDATE users SET name = :name, updated_at = NOW() WHERE id = :id')
        ->execute(['name' => $name, 'id' => $_SESSION['user_id']]);
} catch (PDOException $e) {
    // 事前チェックとUPDATEの間に同じ名前が登録された場合（UNIQUE制約違反）
    if ($e->getCode() === '23000') {
        header('Location: settings.php?msg=name_taken');
        exit;
    }
    throw $e;
}
$_SESSION['user_name'] = $name;
header('Location: settings.php?msg=name_changed');

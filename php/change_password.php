<?php
// パスワード変更API。なりすまし防止のため現在のパスワードの確認を必須とする
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
$current = $_POST['current_password'] ?? '';
$new = $_POST['new_password'] ?? '';
$confirm = $_POST['new_password_confirm'] ?? '';
if ($new !== $confirm) {
    header('Location: settings.php?msg=password_mismatch');
    exit;
}
// 新パスワードも登録時と同じポリシー（8文字以上・英字と数字）を適用する
if (!valid_password($new)) {
    header('Location: settings.php?msg=weak_password');
    exit;
}
$stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id');
$stmt->execute(['id' => $_SESSION['user_id']]);
$hash = $stmt->fetchColumn();
if (!$hash || !password_verify($current, $hash)) {
    header('Location: settings.php?msg=wrong_password');
    exit;
}
$pdo->prepare('UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE id = :id')
    ->execute(['hash' => password_hash($new, PASSWORD_DEFAULT), 'id' => $_SESSION['user_id']]);
session_regenerate_id(true);// 資格情報の変更後はセッションIDを更新する
header('Location: settings.php?msg=password_changed');

<?php
require_once __DIR__ . '/session_boot.php';
require_once __DIR__ . '/db_connect.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../sighup.html');
    exit;
}
if (!csrf_verify()) {
    header('Location: ../sighup.html?msg=invalid_request');
    exit;
}
$name = trim($_POST['name'] ?? '');
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? '';
$genre = trim($_POST['genre'] ?? '');
if (!$name || !$email || !$password || !$genre) {
    header('Location: ../sighup.html?msg=signup_failed');
    exit;
}
// パスワードポリシー: 8文字以上かつ英字と数字を両方含む
if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
    header('Location: ../sighup.html?msg=weak_password');
    exit;
}
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
$stmt->execute(['email' => $email]);
if ($stmt->fetch()) {
    header('Location: ../sighup.html?msg=email_taken');
    exit;
}
$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$insert = $pdo->prepare('INSERT INTO users (name, email, password_hash, genre, created_at) VALUES (:name, :email, :password_hash, :genre, NOW())');
$insert->execute([ 'name' => $name, 'email' => $email, 'password_hash' => $passwordHash, 'genre' => $genre ]);
$userId = $pdo->lastInsertId();
session_regenerate_id(true);//セッション固定化攻撃対策
$_SESSION['user_id'] = $userId;
$_SESSION['user_name'] = $name;
$_SESSION['user_genre'] = $genre;
$_SESSION['login_at'] = time();//ここから7日間で自動ログアウト
header('Location: QuickTrend.php');

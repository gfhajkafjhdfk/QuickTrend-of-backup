<?php
require_once __DIR__ . '/session_boot.php';
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/validation.php';
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
// ニックネームポリシー: 2〜50文字・制御文字禁止
if (!valid_username($name)) {
    header('Location: ../sighup.html?msg=invalid_name');
    exit;
}
// パスワードポリシー: 8文字以上かつ英字と数字を両方含む
if (!valid_password($password)) {
    header('Location: ../sighup.html?msg=weak_password');
    exit;
}
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
$stmt->execute(['email' => $email]);
if ($stmt->fetch()) {
    header('Location: ../sighup.html?msg=email_taken');
    exit;
}
// ニックネームの重複確認
$stmt = $pdo->prepare('SELECT id FROM users WHERE name = :name');
$stmt->execute(['name' => $name]);
if ($stmt->fetch()) {
    header('Location: ../sighup.html?msg=name_taken');
    exit;
}
$passwordHash = password_hash($password, PASSWORD_DEFAULT);
try {
    $insert = $pdo->prepare('INSERT INTO users (name, email, password_hash, genre, created_at) VALUES (:name, :email, :password_hash, :genre, NOW())');
    $insert->execute([ 'name' => $name, 'email' => $email, 'password_hash' => $passwordHash, 'genre' => $genre ]);
} catch (PDOException $e) {
    // 事前チェックとINSERTの間に同じメール/ニックネームが登録された場合（UNIQUE制約違反）
    if ($e->getCode() === '23000') {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $msg = $stmt->fetch() ? 'email_taken' : 'name_taken';
        header('Location: ../sighup.html?msg=' . $msg);
        exit;
    }
    throw $e;
}
$userId = $pdo->lastInsertId();
session_regenerate_id(true);//セッション固定化攻撃対策
$_SESSION['user_id'] = $userId;
$_SESSION['user_name'] = $name;
$_SESSION['user_genre'] = $genre;
$_SESSION['login_at'] = time();//ここから7日間で自動ログアウト
header('Location: QuickTrend.php');

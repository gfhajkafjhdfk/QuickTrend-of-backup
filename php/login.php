<?php
session_start();//セッションを開始する
require_once __DIR__ . '/db_connect.php';//データベース接続を読み込む
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {//POSTリクエストでない場合はサインインページにリダイレクトする
    header('Location: sighin.html');//サインインページにリダイレクトする
    exit;
}
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? '';
if (!$email || !$password) {
    header('Location: sighin.html');//サインインページにリダイレクトする
    exit;
}
$stmt = $pdo->prepare('SELECT id, name, password_hash, genre FROM users WHERE email = :email LIMIT 1');
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();
if (!$user || !password_verify($password, $user['password_hash'])) {
    header('Location: sighin.html');//サインインページにリダイレクトする
    exit;
}
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_genre'] = $user['genre'];
header('Location: QuickTrend.php');

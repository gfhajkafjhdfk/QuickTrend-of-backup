<?php
require_once __DIR__ . '/session_boot.php';//セッションを開始する（有効期限・Cookie設定込み）
require_once __DIR__ . '/db_connect.php';//データベース接続を読み込む
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {//POSTリクエストでない場合はサインインページにリダイレクトする
    header('Location: ../sighin.html?msg=login_required');
    exit;
}
if (!csrf_verify()) {//CSRFトークンが不正な場合
    header('Location: ../sighin.html?msg=invalid_request');
    exit;
}
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? '';
if (!$email || !$password) {
    header('Location: ../sighin.html?msg=login_failed');
    exit;
}
$stmt = $pdo->prepare('SELECT id, name, password_hash, genre FROM users WHERE email = :email LIMIT 1');
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();
if (!$user || !password_verify($password, $user['password_hash'])) {
    header('Location: ../sighin.html?msg=login_failed');
    exit;
}
session_regenerate_id(true);//セッション固定化攻撃対策
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_genre'] = $user['genre'];
$_SESSION['login_at'] = time();//ここから7日間で自動ログアウト
header('Location: QuickTrend.php');

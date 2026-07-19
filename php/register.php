<?php
require_once __DIR__ . '/session_boot.php';
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/validation.php';
require_once __DIR__ . '/rate_limit.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../sighup.html');
    exit;
}
if (!csrf_verify()) {
    header('Location: ../sighup.html?msg=invalid_request');
    exit;
}
// IP単位のレート制限: 登録試行は1時間10回まで（ユーザー列挙の総当たりを抑止）
if (rate_limited($pdo, 'register', 10, 3600)) {
    header('Location: ../sighup.html?msg=too_many_attempts');
    exit;
}
rate_record($pdo, 'register');
$name = trim($_POST['name'] ?? '');
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? '';
$genre = trim($_POST['genre'] ?? '');
if (!$name || !$email || !$password || !$genre) {
    header('Location: ../sighup.html?msg=signup_failed');
    exit;
}
// ニックネームポリシー: 2〜50文字・制御文字禁止（自分の入力ミスなので具体的に返してよい）
if (!valid_username($name)) {
    header('Location: ../sighup.html?msg=invalid_name');
    exit;
}
// パスワードポリシー: 8文字以上かつ英字と数字を両方含む（同上）
if (!valid_password($password)) {
    header('Location: ../sighup.html?msg=weak_password');
    exit;
}
// メール/ニックネームの重複は「どちらが埋まっているか」を明かさない汎用エラーに統一する
// （攻撃者が特定メール・名前の登録有無を総当たりで調べる＝ユーザー列挙を防ぐ）
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email OR name = :name LIMIT 1');
$stmt->execute(['email' => $email, 'name' => $name]);
if ($stmt->fetch()) {
    header('Location: ../sighup.html?msg=signup_unavailable');
    exit;
}
$passwordHash = password_hash($password, PASSWORD_DEFAULT);
try {
    $insert = $pdo->prepare('INSERT INTO users (name, email, password_hash, genre, created_at) VALUES (:name, :email, :password_hash, :genre, NOW())');
    $insert->execute([ 'name' => $name, 'email' => $email, 'password_hash' => $passwordHash, 'genre' => $genre ]);
} catch (PDOException $e) {
    // 事前チェックとINSERTの間に同じメール/ニックネームが登録された場合（UNIQUE制約違反）
    // どちらが衝突したかは明かさず汎用エラーに統一する
    if ($e->getCode() === '23000') {
        header('Location: ../sighup.html?msg=signup_unavailable');
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

<?php
require_once __DIR__ . '/session_boot.php';//セッションを開始する（有効期限・Cookie設定込み）
require_once __DIR__ . '/db_connect.php';//データベース接続を読み込む

const LOGIN_MAX_ATTEMPTS = 5;// この回数失敗したらロック
const LOGIN_WINDOW_MINUTES = 15;// 失敗をカウントする時間幅（分）

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

$ip = $_SERVER['REMOTE_ADDR'] ?? '';

// レート制限: 直近15分間の失敗回数を同一メールまたは同一IPでカウントする
$stmt = $pdo->prepare(
    'SELECT COUNT(*) FROM login_attempts
     WHERE (email = :email OR ip_address = :ip)
       AND attempted_at > (NOW() - INTERVAL ' . LOGIN_WINDOW_MINUTES . ' MINUTE)'
);
$stmt->execute(['email' => $email, 'ip' => $ip]);
if ((int) $stmt->fetchColumn() >= LOGIN_MAX_ATTEMPTS) {
    header('Location: ../sighin.html?msg=too_many_attempts');
    exit;
}

$stmt = $pdo->prepare('SELECT id, name, password_hash, genre FROM users WHERE email = :email LIMIT 1');
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();
if (!$user || !password_verify($password, $user['password_hash'])) {
    $record = $pdo->prepare('INSERT INTO login_attempts (email, ip_address) VALUES (:email, :ip)');
    $record->execute(['email' => $email, 'ip' => $ip]);
    header('Location: ../sighin.html?msg=login_failed');
    exit;
}

// ログイン成功: 失敗履歴を消し、古い記録も掃除する
$pdo->prepare('DELETE FROM login_attempts WHERE email = :email OR attempted_at < (NOW() - INTERVAL 1 DAY)')
    ->execute(['email' => $email]);

session_regenerate_id(true);//セッション固定化攻撃対策
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_genre'] = $user['genre'];
$_SESSION['login_at'] = time();//ここから7日間で自動ログアウト
header('Location: QuickTrend.php');

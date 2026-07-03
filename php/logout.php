<?php
require_once __DIR__ . '/session_boot.php';//Cookie設定を揃えるため共通ブートを使用
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    // セッション開始時と同じ属性（SameSite含む）で過去の有効期限を指定し、確実に削除する
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $params['path'],
        'domain' => $params['domain'],
        'secure' => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'],
    ]);
}
session_destroy();
header('Location: ../index.html');

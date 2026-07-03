<?php
require_once __DIR__ . '/session_boot.php';//Cookie設定を揃えるため共通ブートを使用
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'], $params['secure'], $params['httponly']
    );
}
session_destroy();
header('Location: index.html');

<?php
// セッション共通設定（有効期限・Cookie属性・CSRF）。session_start()の代わりに必ずこれをrequireする
const SESSION_LIFETIME = 604800;// セッション有効期限（7日間）

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.gc_maxlifetime', (string) SESSION_LIFETIME);
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path' => '/',
        'domain' => '',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',// HTTPS環境では自動的にSecure属性が付く
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ログインから一定時間経過したセッションを自動的に無効化する
if (!empty($_SESSION['user_id']) && time() - ($_SESSION['login_at'] ?? 0) > SESSION_LIFETIME) {
    $_SESSION = [];
    session_regenerate_id(true);
    $_SESSION['session_expired'] = true;// auth_check.phpで期限切れメッセージを出すためのフラグ
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function csrf_token(): string
{
    return $_SESSION['csrf_token'];
}

// POSTのcsrf_tokenフィールドまたはX-CSRF-Tokenヘッダーを検証する
function csrf_verify(): bool
{
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}

<?php
// セッション共通設定（有効期限・Cookie属性・CSRF）。session_start()の代わりに必ずこれをrequireする
const SESSION_LIFETIME = 604800;// セッション有効期限（7日間）
// リバースプロキシ経由でHTTPS終端する場合、プロキシのIPをここに追加する（VPS構成が決まったら見直す）
const TRUSTED_PROXIES = ['127.0.0.1', '::1'];

// HTTPSかどうかの判定。信頼できるプロキシからのX-Forwarded-Protoのみ考慮する
// （誰でも送れるヘッダーなので、無条件に信用するとSecure属性の誤判定につながる）
function request_is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    return in_array($_SERVER['REMOTE_ADDR'] ?? '', TRUSTED_PROXIES, true)
        && ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.gc_maxlifetime', (string) SESSION_LIFETIME);
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path' => '/',
        'domain' => '',
        'secure' => request_is_https(),// HTTPS環境では自動的にSecure属性が付く
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

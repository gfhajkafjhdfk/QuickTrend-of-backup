<?php
require_once __DIR__ . '/session_boot.php';
if (empty($_SESSION['user_id'])) {
    $msg = !empty($_SESSION['session_expired']) ? 'session_expired' : 'login_required';
    unset($_SESSION['session_expired']);
    header('Location: ../sighin.html?msg=' . $msg);// php/配下から見た相対パス
    exit;
}

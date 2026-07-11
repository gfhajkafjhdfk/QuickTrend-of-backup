<?php
// 入力値の共通バリデーション（新規登録・アカウント設定で共用する）

// ニックネーム: 2〜50文字、制御文字を含まない（日本語・英数字・記号は使用可）
function valid_username(string $name): bool
{
    return (bool) preg_match('/\A[^\x00-\x1F\x7F]{2,50}\z/u', $name);
}

// パスワード: 8文字以上かつ英字と数字を両方含む
function valid_password(string $password): bool
{
    return strlen($password) >= 8
        && preg_match('/[A-Za-z]/', $password)
        && preg_match('/[0-9]/', $password);
}

<?php
// DB接続設定。優先順位: 環境変数 > config.local.php（git管理外） > デフォルト値
// 本番（VPS）では環境変数を設定するか、config.local.php.example を参考に config.local.php を作成する
$config = [
    'host' => getenv('QT_DB_HOST') ?: '127.0.0.1',
    'dbname' => getenv('QT_DB_NAME') ?: 'quicktrend',
    'user' => getenv('QT_DB_USER') ?: 'root',
    'password' => getenv('QT_DB_PASSWORD') ?: '',
    'charset' => 'utf8mb4',
];
$localFile = __DIR__ . '/config.local.php';
if (is_file($localFile)) {
    $config = array_merge($config, require $localFile);
}
return $config;

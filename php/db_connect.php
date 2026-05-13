<?php
$config = require __DIR__ . '/config.php';//config.phpから設定を読み込む
$dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $config['host'], $config['dbname'], $config['charset']);//DSNを作成する
try {
    $pdo = new PDO($dsn, $config['user'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,//エラーを例外としてスローする
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,//デフォルトのフェッチモードを連想配列に設定する
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'データベース接続に失敗しました。']);//エラーメッセージをJSON形式で返す
    exit;
}

<?php
require_once __DIR__ . '/../session_boot.php';
header('Content-Type: application/json; charset=utf-8');
if (empty($_SESSION['user_id'])) {//未ログインでの外部コマンド実行を防ぐ
    http_response_code(403);
    echo json_encode(['error' => 'ログインが必要です']);
    exit;
}
if (!csrf_verify()) {
    http_response_code(403);
    echo json_encode(['error' => '不正なリクエストです']);
    exit;
}

const PREDICT_TIMEOUT_SECONDS = 10;// Pythonの実行タイムアウト

$payload = file_get_contents('php://input');
if (!$payload) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_payload']);
    exit;
}
$input = json_decode($payload, true);
if (!is_array($input) || !is_array($input['user'] ?? null) || !is_array($input['candidates'] ?? null)) {
    http_response_code(400);
    echo json_encode(['error' => 'missing_data']);
    exit;
}

// 実行環境ごとのPythonパスは環境変数QT_PYTHONで指定する（例: /usr/bin/python3, C:\Python312\python.exe）
$python = getenv('QT_PYTHON') ?: 'python';
$script = __DIR__ . '/../../ai/predict.py';

// 一時ファイルを使わず標準入力で直接渡す（ファイル競合・残留のリスクをなくす）
$process = proc_open(
    [$python, $script],
    [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
    $pipes
);
if (!is_resource($process)) {
    http_response_code(500);
    echo json_encode(['error' => 'python_execution_failed']);
    exit;
}
fwrite($pipes[0], json_encode($input, JSON_UNESCAPED_UNICODE));
fclose($pipes[0]);
stream_set_blocking($pipes[1], false);
stream_set_blocking($pipes[2], false);

// タイムアウト付きで出力を読み取る
$output = '';
$deadline = time() + PREDICT_TIMEOUT_SECONDS;
$timedOut = false;
while (true) {
    $status = proc_get_status($process);
    $output .= stream_get_contents($pipes[1]);
    if (!$status['running']) {
        break;
    }
    if (time() > $deadline) {
        proc_terminate($process);
        $timedOut = true;
        break;
    }
    usleep(100000);// 0.1秒待って再確認
}
fclose($pipes[1]);
fclose($pipes[2]);
proc_close($process);

if ($timedOut) {
    http_response_code(504);
    echo json_encode(['error' => 'python_timeout']);
    exit;
}
if (!$output) {
    http_response_code(500);
    echo json_encode(['error' => 'python_execution_failed']);
    exit;
}
$result = json_decode($output, true);
if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'invalid_python_output']);
    exit;
}
echo json_encode($result);

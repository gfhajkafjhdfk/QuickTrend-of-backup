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
$payload = file_get_contents('php://input');
if (!$payload) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_payload']);
    exit;
}
$input = json_decode($payload, true);
if (!$input || !isset($input['user']) || !isset($input['candidates'])) {
    http_response_code(400);
    echo json_encode(['error' => 'missing_data']);
    exit;
}
$inputFile = tempnam(sys_get_temp_dir(), 'qt_');
file_put_contents($inputFile, json_encode($input, JSON_UNESCAPED_UNICODE));
$python = 'python';
$command = escapeshellcmd($python . ' "' . __DIR__ . '/../../ai/predict.py"') . ' < "' . $inputFile . '"';
$output = shell_exec($command);
unlink($inputFile);
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

<?php
// Server-Sent Events: データ更新の通知チャネル
// 設計: SSEは「変わったよ」の通知だけを流し、データ本体はクライアントが既存APIを再取得する
// （WebSocketと違い追加サーバ不要で、共有ホスティング/XAMPPでそのまま動く）
require_once __DIR__ . '/common.php';

// セッションロックを解放（保持したままだと同一ユーザーの他リクエストが全部詰まる）
session_write_close();

header('Content-Type: text/event-stream; charset=utf-8');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');
set_time_limit(70);

// 現在の状態のフィンガープリント（集計時刻 + 投票総数 + 訪問最終ID）
function state_fingerprint(PDO $pdo): string
{
    $agg = $pdo->query("SELECT meta_value FROM aggregate_meta WHERE meta_key = 'last_aggregated_at'")->fetchColumn() ?: '';
    $votes = $pdo->query('SELECT COALESCE(SUM(vote_count), 0) FROM places')->fetchColumn();
    $lastVisit = $pdo->query('SELECT COALESCE(MAX(id), 0) FROM visits')->fetchColumn();
    return $agg . '|' . $votes . '|' . $lastVisit;
}

$lastState = $_SERVER['HTTP_LAST_EVENT_ID'] ?? '';
// 約60秒でストリームを終了し、ブラウザのEventSource自動再接続に任せる
for ($i = 0; $i < 12; $i++) {
    $state = state_fingerprint($pdo);
    if ($state !== $lastState) {
        $lastState = $state;
        echo "id: " . str_replace(["\n", "\r"], '', $state) . "\n";
        echo "event: update\n";
        echo 'data: ' . json_encode(['changedAt' => date('c')]) . "\n\n";
    } else {
        echo ": heartbeat\n\n";// コメント行（接続維持）
    }
    if (ob_get_level() > 0) {
        ob_flush();
    }
    flush();
    if (connection_aborted()) {
        exit;
    }
    sleep(5);
}

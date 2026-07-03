<?php
// 地図トレンドAPIの共通処理
require_once __DIR__ . '/../session_boot.php';
require_once __DIR__ . '/../db_connect.php';
$POPULARITY = require __DIR__ . '/popularity_config.php';

function json_out($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// 名前+丸め座標でスポットを同定する（フロントのfeature_idと同じ規則）。無ければ作成
function find_or_create_place(PDO $pdo, string $name, float $lat, float $lng, ?string $category = null): int
{
    $latR = round($lat, 4);
    $lngR = round($lng, 4);
    $stmt = $pdo->prepare('SELECT id FROM places WHERE name = :name AND lat_r = :lat_r AND lng_r = :lng_r LIMIT 1');
    $stmt->execute(['name' => $name, 'lat_r' => $latR, 'lng_r' => $lngR]);
    $id = $stmt->fetchColumn();
    if ($id) {
        return (int) $id;
    }
    $insert = $pdo->prepare(
        'INSERT INTO places (name, category, latitude, longitude, lat_r, lng_r) VALUES (:name, :category, :lat, :lng, :lat_r, :lng_r)
         ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)'
    );
    $insert->execute(['name' => $name, 'category' => $category, 'lat' => $lat, 'lng' => $lng, 'lat_r' => $latR, 'lng_r' => $lngR]);
    return (int) $pdo->lastInsertId();
}

// クライアントの匿名IDをソルト付きハッシュ化する。生の匿名IDはDBに保存しない
// （DBが漏れてもクライアントのIDと突き合わせられないようにするため）
function anon_hash(string $anonId): string
{
    $salt = getenv('QT_ANON_SALT') ?: 'qt-anon-salt-change-me-on-vps';
    return hash('sha256', $salt . $anonId);
}

// 座標の妥当性チェック
function valid_coords($lat, $lng): bool
{
    return is_numeric($lat) && is_numeric($lng)
        && $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180;
}

// ランキングキャッシュが古ければ再集計する（多重実行はMySQLのGET_LOCKで防止）
function ensure_fresh_aggregates(PDO $pdo, array $config): void
{
    $stmt = $pdo->prepare("SELECT meta_value FROM aggregate_meta WHERE meta_key = 'last_aggregated_at'");
    $stmt->execute();
    $last = $stmt->fetchColumn();
    if ($last && (time() - strtotime($last)) < $config['cache_ttl_seconds']) {
        return;
    }
    require_once __DIR__ . '/aggregate.php';
    run_aggregation($pdo, $config);
}

<?php
// 匿名訪問の記録API
// POST JSON: { anonId, latitude, longitude, staySeconds }
// - ユーザーIDは一切使わない（未ログインでも動作する）
// - GPS座標そのものは保存せず、既知スポット(places)への「訪問」としてのみ記録する
require_once __DIR__ . '/common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['error' => 'method_not_allowed'], 405);
}
if (!csrf_verify()) {
    json_out(['error' => 'invalid_request'], 403);
}
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    json_out(['error' => 'invalid_payload'], 400);
}
$anonId = (string) ($input['anonId'] ?? '');
$lat = $input['latitude'] ?? null;
$lng = $input['longitude'] ?? null;
$stay = (int) ($input['staySeconds'] ?? 0);
if (strlen($anonId) < 16 || strlen($anonId) > 64 || !valid_coords($lat, $lng)) {
    json_out(['error' => 'invalid_data'], 400);
}
$stay = max(0, min($stay, 43200));// 滞在時間は0〜12時間に制限（異常値対策）

// 近くの既知スポットを検索（丸め座標インデックスで絞ってから距離計算。約120m以内）
$lat = (float) $lat;
$lng = (float) $lng;
$delta = 0.0015;
$stmt = $pdo->prepare(
    'SELECT id, latitude, longitude FROM places
     WHERE lat_r BETWEEN :lat_min AND :lat_max AND lng_r BETWEEN :lng_min AND :lng_max'
);
$stmt->execute([
    'lat_min' => $lat - $delta, 'lat_max' => $lat + $delta,
    'lng_min' => $lng - $delta, 'lng_max' => $lng + $delta,
]);
$best = null;
$bestDist = PHP_FLOAT_MAX;
foreach ($stmt->fetchAll() as $p) {
    // 短距離なので平面近似で十分（1度≒111km、経度は緯度で補正）
    $dLat = ($p['latitude'] - $lat) * 111000;
    $dLng = ($p['longitude'] - $lng) * 111000 * cos(deg2rad($lat));
    $dist = sqrt($dLat * $dLat + $dLng * $dLng);
    if ($dist < $bestDist) {
        $bestDist = $dist;
        $best = $p;
    }
}
if (!$best || $bestDist > 120) {
    json_out(['matched' => false]);// 既知スポットの近くでなければ何も保存しない
}

$hash = anon_hash($anonId);
$placeId = (int) $best['id'];

// 重複排除: 同一匿名IDの同一スポット訪問は一定時間に1回のみ
$dedupe = $pdo->prepare(
    'SELECT COUNT(*) FROM visits
     WHERE anon_hash = :hash AND place_id = :place_id
       AND visited_at > (NOW() - INTERVAL ' . (int) $POPULARITY['visit_dedupe_minutes'] . ' MINUTE)'
);
$dedupe->execute(['hash' => $hash, 'place_id' => $placeId]);
if ((int) $dedupe->fetchColumn() > 0) {
    json_out(['matched' => true, 'recorded' => false, 'reason' => 'duplicate']);
}

$pdo->prepare('INSERT INTO visits (place_id, anon_hash, stay_seconds) VALUES (:place_id, :hash, :stay)')
    ->execute(['place_id' => $placeId, 'hash' => $hash, 'stay' => $stay]);
json_out(['matched' => true, 'recorded' => true, 'placeId' => $placeId]);

<?php
// 地図トレンドAPI
// GET  ?action=location : 旧形式のスポット一覧（互換維持）
// GET  (それ以外)       : GeoJSON FeatureCollection（Map.htmlのグラフ表示用）
// POST {name, lat, lng} : 投票を記録
require_once __DIR__ . '/map/common.php';
require_once __DIR__ . '/rate_limit.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        json_out(['error' => 'invalid_request'], 403);
    }
    // IP単位のレート制限: 投票は1分20回まで（Cookieを捨てても回避できないようにする）
    if (rate_limited($pdo, 'vote', 20, 60)) {
        json_out(['error' => 'rate_limited'], 429);
    }
    $input = json_decode(file_get_contents('php://input'), true);
    $name = trim((string) ($input['name'] ?? ''));
    $lat = $input['lat'] ?? null;
    $lng = $input['lng'] ?? null;
    if ($name === '' || mb_strlen($name) > 120 || !valid_coords($lat, $lng)) {
        json_out(['error' => 'invalid_data'], 400);
    }

    // サーバ側レート制限: 同一セッションから同一スポットへの投票は1分1回
    $throttleKey = 'vote_' . md5($name . round($lat, 4) . round($lng, 4));
    if (!empty($_SESSION[$throttleKey]) && time() - $_SESSION[$throttleKey] < 60) {
        json_out(['error' => 'rate_limited'], 429);
    }
    $_SESSION[$throttleKey] = time();
    rate_record($pdo, 'vote');

    // 新規スポット作成はより厳しく制限する（偽スポットの量産＝DBスパム対策）
    $exists = $pdo->prepare('SELECT 1 FROM places WHERE name = :name AND lat_r = :lat_r AND lng_r = :lng_r LIMIT 1');
    $exists->execute(['name' => $name, 'lat_r' => round($lat, 4), 'lng_r' => round($lng, 4)]);
    if (!$exists->fetchColumn()) {
        if (rate_limited($pdo, 'place_create', 5, 3600)) {
            json_out(['error' => 'rate_limited'], 429);
        }
        rate_record($pdo, 'place_create');
    }

    $placeId = find_or_create_place($pdo, $name, (float) $lat, (float) $lng);
    $pdo->prepare('UPDATE places SET vote_count = vote_count + 1 WHERE id = :id')->execute(['id' => $placeId]);
    json_out(['success' => true, 'placeId' => $placeId]);
}

$action = $_GET['action'] ?? 'trend';

if ($action === 'location') {// 旧API（互換のため維持）
    $stmt = $pdo->query('SELECT id, name, description, latitude, longitude FROM locations ORDER BY id DESC LIMIT 20');
    $locations = $stmt->fetchAll();
    if (empty($locations)) {
        $locations = [
            ['id' => 1, 'name' => 'QuickTrend Cafe', 'description' => 'みんなで集まる人気スポット', 'latitude' => 35.6895, 'longitude' => 139.6917],
            ['id' => 2, 'name' => 'リラックス公園', 'description' => '自然を楽しめる場所です', 'latitude' => 35.6890, 'longitude' => 139.6920],
        ];
    }
    json_out(['locations' => $locations]);
}

// GeoJSON形式でスポット+票数+人気度を返す（Map.htmlのfetchAndUpdateDataが期待する形式）
ensure_fresh_aggregates($pdo, $POPULARITY);
$rows = $pdo->query(
    'SELECT p.id, p.name, p.category, p.latitude, p.longitude, p.vote_count,
            COALESCE(rc.popularity_score, 0) AS score,
            COALESCE(rc.visit_count_7d, 0) AS visits7d,
            COALESCE(rc.avg_stay_seconds, 0) AS avg_stay
     FROM places p LEFT JOIN ranking_cache rc ON rc.place_id = p.id
     ORDER BY p.vote_count DESC LIMIT 500'
)->fetchAll();

json_out([
    'type' => 'FeatureCollection',
    'features' => array_map(fn($r) => [
        'type' => 'Feature',
        'geometry' => ['type' => 'Point', 'coordinates' => [(float) $r['longitude'], (float) $r['latitude']]],
        'properties' => [
            'name' => $r['name'],
            'count' => (int) $r['vote_count'],// フロントの既存契約: countは投票数
            'place_id' => (int) $r['id'],
            'category' => $r['category'],
            'score' => (float) $r['score'],
            'visits7d' => (int) $r['visits7d'],
            'avg_stay_seconds' => (int) $r['avg_stay'],
        ],
    ], $rows),
]);

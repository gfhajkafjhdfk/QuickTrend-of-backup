<?php
// スポット/エリア統計API（グラフ表示用データを返す）
// GET ?place_id=123                     … 特定スポットの統計
// GET ?lat=35.65&lng=139.7&radius=500   … 指定地点周辺（検索結果用）の統計
// 返却: 訪問人数推移(14日) / 時間帯別 / 曜日別 / 人気度推移 / 平均滞在時間 / 人気度スコア
require_once __DIR__ . '/common.php';

ensure_fresh_aggregates($pdo, $POPULARITY);

// 対象place_idの集合を決める
$placeIds = [];
$label = '';
if (isset($_GET['place_id'])) {
    $placeIds = [(int) $_GET['place_id']];
    $stmt = $pdo->prepare('SELECT name FROM places WHERE id = :id');
    $stmt->execute(['id' => $placeIds[0]]);
    $label = (string) $stmt->fetchColumn();
} elseif (valid_coords($_GET['lat'] ?? null, $_GET['lng'] ?? null)) {
    $lat = (float) $_GET['lat'];
    $lng = (float) $_GET['lng'];
    $radius = min(2000, max(100, (int) ($_GET['radius'] ?? 500)));
    $delta = $radius / 111000;
    $stmt = $pdo->prepare(
        'SELECT id FROM places
         WHERE lat_r BETWEEN :lat_min AND :lat_max AND lng_r BETWEEN :lng_min AND :lng_max LIMIT 200'
    );
    $stmt->execute([
        'lat_min' => $lat - $delta, 'lat_max' => $lat + $delta,
        'lng_min' => $lng - $delta, 'lng_max' => $lng + $delta,
    ]);
    $placeIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    $label = (string) ($_GET['label'] ?? '検索地点周辺');
} else {
    json_out(['error' => 'missing_target'], 400);
}

$empty = [
    'label' => $label, 'placeIds' => $placeIds, 'score' => null,
    'daily' => [], 'hourly' => [], 'weekday' => [], 'popularityTrend' => [],
    'totals' => ['visits7d' => 0, 'avgStaySeconds' => 0, 'revisitRate' => 0],
];
if (empty($placeIds)) {
    json_out($empty);
}
$in = implode(',', $placeIds);// intval済みのIDのみなので直接埋め込み可

// 訪問人数推移（直近14日、日別）
$daily = $pdo->query(
    "SELECT stat_date, SUM(visit_count) AS visits, SUM(unique_visitors) AS uniques,
            COALESCE(SUM(avg_stay_seconds * visit_count) / NULLIF(SUM(visit_count),0), 0) AS avg_stay
     FROM daily_statistics
     WHERE place_id IN ($in) AND stat_date >= (CURDATE() - INTERVAL 14 DAY)
     GROUP BY stat_date ORDER BY stat_date"
)->fetchAll();

// 時間帯別（直近14日の合計）
$hourly = $pdo->query(
    "SELECT stat_hour, SUM(visit_count) AS visits
     FROM hourly_statistics
     WHERE place_id IN ($in) AND stat_date >= (CURDATE() - INTERVAL 14 DAY)
     GROUP BY stat_hour ORDER BY stat_hour"
)->fetchAll();

// 曜日別（直近14日、DAYOFWEEK: 1=日曜）
$weekday = $pdo->query(
    "SELECT DAYOFWEEK(stat_date) AS dow, SUM(visit_count) AS visits
     FROM daily_statistics
     WHERE place_id IN ($in) AND stat_date >= (CURDATE() - INTERVAL 14 DAY)
     GROUP BY DAYOFWEEK(stat_date) ORDER BY dow"
)->fetchAll();

// 現在の人気度（対象スポットのスコア合成: 単一なら本人の値、エリアなら平均）
$score = $pdo->query(
    "SELECT AVG(popularity_score) AS score, SUM(visit_count_7d) AS visits7d,
            COALESCE(SUM(avg_stay_seconds * visit_count_7d) / NULLIF(SUM(visit_count_7d),0), 0) AS avg_stay,
            AVG(revisit_rate) AS revisit_rate
     FROM ranking_cache WHERE place_id IN ($in)"
)->fetch();

// 人気度推移: 日次集計からスコア式を日ごとに再適用（重み変更が過去にも反映される設計）
$w = $POPULARITY['weights'];
$n = $POPULARITY['norm'];
$popularityTrend = array_map(function ($d) use ($w, $n) {
    $visitors = min(100, ($d['uniques'] / max(1, $n['visitors_max'])) * 100);
    $stay = min(100, ($d['avg_stay'] / max(1, $n['stay_max_seconds'])) * 100);
    $revisit = $d['visits'] > 0 ? min(100, (1 - $d['uniques'] / $d['visits']) * 100) : 0;
    return [
        'date' => $d['stat_date'],
        'score' => round($visitors * $w['visitors'] + $stay * $w['stay'] + $revisit * $w['revisit'], 1),
    ];
}, $daily);

json_out([
    'label' => $label,
    'placeIds' => $placeIds,
    'score' => $score['score'] !== null ? round((float) $score['score'], 1) : null,
    'daily' => array_map(fn($d) => [
        'date' => $d['stat_date'], 'visits' => (int) $d['visits'],
        'uniques' => (int) $d['uniques'], 'avgStaySeconds' => (int) $d['avg_stay'],
    ], $daily),
    'hourly' => array_map(fn($h) => ['hour' => (int) $h['stat_hour'], 'visits' => (int) $h['visits']], $hourly),
    'weekday' => array_map(fn($d) => ['dow' => (int) $d['dow'], 'visits' => (int) $d['visits']], $weekday),
    'popularityTrend' => $popularityTrend,
    'totals' => [
        'visits7d' => (int) ($score['visits7d'] ?? 0),
        'avgStaySeconds' => (int) ($score['avg_stay'] ?? 0),
        'revisitRate' => round((float) ($score['revisit_rate'] ?? 0), 3),
    ],
]);

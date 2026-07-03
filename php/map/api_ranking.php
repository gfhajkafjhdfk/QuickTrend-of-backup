<?php
// ランキングAPI（ranking_cacheのみを読む＝高速）
// GET ?type=top|trending&limit=10
// top: 人気度順 / trending: 前回集計からのスコア上昇幅順（急上昇）
require_once __DIR__ . '/common.php';

ensure_fresh_aggregates($pdo, $POPULARITY);

$type = $_GET['type'] ?? 'top';
$limit = min(50, max(1, (int) ($_GET['limit'] ?? 10)));
$order = $type === 'trending'
    ? '(rc.popularity_score - rc.prev_score) DESC, rc.popularity_score DESC'
    : 'rc.rank_position ASC';

$rows = $pdo->query(
    "SELECT p.id, p.name, p.category, p.latitude, p.longitude, p.vote_count,
            rc.popularity_score, rc.prev_score, rc.visit_count_7d, rc.avg_stay_seconds,
            rc.revisit_rate, rc.rank_position
     FROM ranking_cache rc JOIN places p ON p.id = rc.place_id
     ORDER BY $order LIMIT $limit"
)->fetchAll();

json_out(['type' => $type, 'ranking' => array_map(fn($r) => [
    'placeId' => (int) $r['id'],
    'name' => $r['name'],
    'category' => $r['category'],
    'latitude' => (float) $r['latitude'],
    'longitude' => (float) $r['longitude'],
    'score' => (float) $r['popularity_score'],
    'scoreDelta' => round($r['popularity_score'] - $r['prev_score'], 2),
    'visits7d' => (int) $r['visit_count_7d'],
    'avgStaySeconds' => (int) $r['avg_stay_seconds'],
    'revisitRate' => (float) $r['revisit_rate'],
    'rank' => (int) $r['rank_position'],
    'votes' => (int) $r['vote_count'],
], $rows)]);

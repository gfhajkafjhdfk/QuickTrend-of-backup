<?php
// AIおすすめAPI（ニュースティッカー + あなたへのおすすめカード）
// GET ?lat=&lng=&cats=カフェ,公園&hist=渋谷駅,新宿
//   lat/lng: 現在地（任意） cats: クライアント側の検索カテゴリ履歴 hist: 検索/閲覧履歴の名称
// 個人情報は受け取らない。履歴はクライアント（localStorage）が要約して送る設計
require_once __DIR__ . '/common.php';

ensure_fresh_aggregates($pdo, $POPULARITY);

$lat = isset($_GET['lat']) && valid_coords($_GET['lat'], $_GET['lng'] ?? 0) ? (float) $_GET['lat'] : null;
$lng = $lat !== null ? (float) $_GET['lng'] : null;
$cats = array_filter(array_map('trim', explode(',', (string) ($_GET['cats'] ?? ''))));
$hist = array_filter(array_map('trim', explode(',', (string) ($_GET['hist'] ?? ''))));
$hour = (int) date('G');
$dow = (int) date('w');// 0=日曜

// 候補: ランキング上位50件（キャッシュのみ参照）
$rows = $pdo->query(
    'SELECT p.id, p.name, p.category, p.latitude, p.longitude,
            rc.popularity_score, rc.prev_score, rc.visit_count_7d, rc.avg_stay_seconds, rc.revisit_rate
     FROM ranking_cache rc JOIN places p ON p.id = rc.place_id
     ORDER BY rc.popularity_score DESC LIMIT 50'
)->fetchAll();

// この時間帯に人気のスポット（時間帯ボーナス用）
$hotNow = $pdo->prepare(
    'SELECT place_id, SUM(visit_count) AS visits FROM hourly_statistics
     WHERE stat_hour = :hour AND stat_date >= (CURDATE() - INTERVAL 14 DAY)
     GROUP BY place_id ORDER BY visits DESC LIMIT 20'
);
$hotNow->execute(['hour' => $hour]);
$hotNowIds = array_map('intval', array_column($hotNow->fetchAll(), 'place_id'));

// スコアリング: 人気度 + 急上昇 + 近さ + カテゴリ/履歴一致 + 時間帯
$scored = [];
foreach ($rows as $r) {
    $score = (float) $r['popularity_score'];
    $reasons = [];

    $delta = $r['popularity_score'] - $r['prev_score'];
    if ($delta > 3) {
        $score += 15;
        $reasons[] = '今人気急上昇中のため';
    }
    $distanceKm = null;
    if ($lat !== null) {
        $dLat = ($r['latitude'] - $lat) * 111;
        $dLng = ($r['longitude'] - $lng) * 111 * cos(deg2rad($lat));
        $distanceKm = sqrt($dLat * $dLat + $dLng * $dLng);
        if ($distanceKm < 1.5) {
            $score += (1.5 - $distanceKm) * 20;
            $reasons[] = '現在地から近いため';
        }
    }
    foreach ($cats as $cat) {
        if ($cat !== '' && (mb_stripos($r['name'], $cat) !== false || mb_stripos((string) $r['category'], $cat) !== false)) {
            $score += 20;
            $reasons[] = '最近「' . $cat . '」を多く検索しているため';
            break;
        }
    }
    foreach ($hist as $h) {
        if ($h !== '' && mb_stripos($r['name'], $h) !== false) {
            $score += 10;
            $reasons[] = '閲覧履歴に関連するため';
            break;
        }
    }
    if (in_array((int) $r['id'], $hotNowIds, true)) {
        $score += 10;
        $reasons[] = ($hour >= 11 && $hour <= 13) ? '昼食時間帯で人気のため' : 'この時間帯に人気のため';
    }
    if ($r['avg_stay_seconds'] >= 1800) {
        $score += 5;
        $reasons[] = '滞在時間が長く満足度が高いため';
    }
    if (empty($reasons)) {
        $reasons[] = '人気ランキング上位のため';
    }
    $scored[] = [
        'placeId' => (int) $r['id'],
        'name' => $r['name'],
        'category' => $r['category'],
        'latitude' => (float) $r['latitude'],
        'longitude' => (float) $r['longitude'],
        'popularity' => round((float) $r['popularity_score'], 1),
        'scoreDelta' => round($delta, 2),
        'avgStayMinutes' => (int) round($r['avg_stay_seconds'] / 60),
        'visits7d' => (int) $r['visit_count_7d'],
        'distanceKm' => $distanceKm !== null ? round($distanceKm, 2) : null,
        'recommendScore' => round($score, 1),
        'reasons' => array_slice(array_unique($reasons), 0, 3),
    ];
}
usort($scored, fn($a, $b) => $b['recommendScore'] <=> $a['recommendScore']);

// ティッカー項目を生成（種類の違うニュースを織り交ぜる）
$ticker = [];
$trending = $scored;
usort($trending, fn($a, $b) => $b['scoreDelta'] <=> $a['scoreDelta']);
if (!empty($trending) && $trending[0]['scoreDelta'] > 0) {
    $ticker[] = ['icon' => '🔥', 'text' => '今急上昇中「' . $trending[0]['name'] . '」'];
}
if (!empty($scored)) {
    $ticker[] = ['icon' => '✨', 'text' => 'あなたにおすすめ「' . $scored[0]['name'] . '」（' . $scored[0]['reasons'][0] . '）'];
}
$longestStay = $scored;
usort($longestStay, fn($a, $b) => $b['avgStayMinutes'] <=> $a['avgStayMinutes']);
if (!empty($longestStay) && $longestStay[0]['avgStayMinutes'] > 0) {
    $ticker[] = ['icon' => '⏰', 'text' => '滞在時間が最も長いスポット「' . $longestStay[0]['name'] . '」平均' . $longestStay[0]['avgStayMinutes'] . '分'];
}
$byVisits = $scored;
usort($byVisits, fn($a, $b) => $b['visits7d'] <=> $a['visits7d']);
if (!empty($byVisits) && $byVisits[0]['visits7d'] > 0) {
    $ticker[] = ['icon' => '📈', 'text' => '今週もっとも訪問が多いスポット「' . $byVisits[0]['name'] . '」'];
}
foreach (array_slice($scored, 1, 4) as $s) {
    $ticker[] = ['icon' => '⭐', 'text' => '人気度' . $s['popularity'] . '「' . $s['name'] . '」（' . $s['reasons'][0] . '）'];
}
if (empty($ticker)) {
    $ticker[] = ['icon' => '🗺️', 'text' => '地図のスポットに投票して、みんなのトレンドを作ろう！'];
}

json_out([
    'ticker' => $ticker,
    'recommendations' => array_slice($scored, 0, 5),
]);

<?php
// バッチ集計: visits(生ログ) → daily/hourly_statistics(日次集計) → ranking_cache(スコア)
//
// パフォーマンス設計:
// - 生ログ(visits)を読むのはこのバッチだけ。表示系APIは集計テーブルしか読まない
// - 日次集計は「前回集計日以降」だけ再計算する増分方式（数百万件でも対象は直近分のみ）
// - ランキングは日次集計から計算するため、visitsの件数に依存しない
//
// 実行方法: APIアクセス時に自動（cache_ttl超過時）/ CLI: php aggregate.php / cron(VPS移行後)

function run_aggregation(PDO $pdo, array $config): void
{
    // 多重実行防止（別リクエストが集計中なら何もしない）
    $lock = $pdo->query("SELECT GET_LOCK('qt_aggregate', 0)")->fetchColumn();
    if (!$lock) {
        return;
    }
    try {
        // 1) 増分の対象期間を決める（前回集計日の前日から。初回は全期間）
        $stmt = $pdo->prepare("SELECT meta_value FROM aggregate_meta WHERE meta_key = 'last_stat_date'");
        $stmt->execute();
        $lastDate = $stmt->fetchColumn();
        $fromDate = $lastDate ? date('Y-m-d', strtotime($lastDate . ' -1 day')) : '1970-01-01';

        // 2) 日次集計（再訪 = 同一匿名IDの2回目以降の訪問）
        $pdo->prepare(
            'REPLACE INTO daily_statistics (place_id, stat_date, visit_count, unique_visitors, avg_stay_seconds, revisit_count)
             SELECT place_id, DATE(visited_at), COUNT(*), COUNT(DISTINCT anon_hash),
                    COALESCE(AVG(stay_seconds), 0), COUNT(*) - COUNT(DISTINCT anon_hash)
             FROM visits WHERE visited_at >= :from_date
             GROUP BY place_id, DATE(visited_at)'
        )->execute(['from_date' => $fromDate]);

        // 3) 時間帯別集計
        $pdo->prepare(
            'REPLACE INTO hourly_statistics (place_id, stat_date, stat_hour, visit_count, avg_stay_seconds)
             SELECT place_id, DATE(visited_at), HOUR(visited_at), COUNT(*), COALESCE(AVG(stay_seconds), 0)
             FROM visits WHERE visited_at >= :from_date
             GROUP BY place_id, DATE(visited_at), HOUR(visited_at)'
        )->execute(['from_date' => $fromDate]);

        // 4) ランキングキャッシュ再計算（日次集計テーブルから。visitsは読まない）
        $w = $config['weights'];
        $n = $config['norm'];
        $windowFrom = date('Y-m-d', strtotime('-' . $config['window_days'] . ' days'));

        $rows = $pdo->prepare(
            'SELECT place_id, SUM(visit_count) AS visits, SUM(unique_visitors) AS uniques,
                    COALESCE(SUM(avg_stay_seconds * visit_count) / NULLIF(SUM(visit_count), 0), 0) AS avg_stay,
                    COALESCE(SUM(revisit_count) / NULLIF(SUM(visit_count), 0), 0) AS revisit_rate
             FROM daily_statistics WHERE stat_date >= :window_from GROUP BY place_id'
        );
        $rows->execute(['window_from' => $windowFrom]);

        $upsert = $pdo->prepare(
            'INSERT INTO ranking_cache
                (place_id, popularity_score, prev_score, visit_count_7d, unique_visitors_7d, avg_stay_seconds, revisit_rate, computed_at)
             VALUES (:place_id, :score, 0, :visits, :uniques, :avg_stay, :revisit_rate, NOW())
             ON DUPLICATE KEY UPDATE
                prev_score = popularity_score, popularity_score = VALUES(popularity_score),
                visit_count_7d = VALUES(visit_count_7d), unique_visitors_7d = VALUES(unique_visitors_7d),
                avg_stay_seconds = VALUES(avg_stay_seconds), revisit_rate = VALUES(revisit_rate), computed_at = NOW()'
        );

        foreach ($rows->fetchAll() as $r) {
            // Popularity Score = 訪問人数×0.6 + 平均滞在×0.3 + 再訪率×0.1（各要素を0〜100に正規化）
            $visitorsScore = min(100, ($r['uniques'] / max(1, $n['visitors_max'])) * 100);
            $stayScore = min(100, ($r['avg_stay'] / max(1, $n['stay_max_seconds'])) * 100);
            $revisitScore = min(100, $r['revisit_rate'] * 100);
            $score = round($visitorsScore * $w['visitors'] + $stayScore * $w['stay'] + $revisitScore * $w['revisit'], 2);
            $upsert->execute([
                'place_id' => $r['place_id'], 'score' => $score,
                'visits' => (int) $r['visits'], 'uniques' => (int) $r['uniques'],
                'avg_stay' => (int) $r['avg_stay'], 'revisit_rate' => round((float) $r['revisit_rate'], 4),
            ]);
        }

        // 5) 順位を採番
        $pdo->exec('SET @rank := 0');
        $pdo->exec('UPDATE ranking_cache SET rank_position = (@rank := @rank + 1) ORDER BY popularity_score DESC, visit_count_7d DESC');

        // 6) メタ情報を更新
        $meta = $pdo->prepare('REPLACE INTO aggregate_meta (meta_key, meta_value) VALUES (:k, :v)');
        $meta->execute(['k' => 'last_stat_date', 'v' => date('Y-m-d')]);
        $meta->execute(['k' => 'last_aggregated_at', 'v' => date('Y-m-d H:i:s')]);
    } finally {
        $pdo->query("SELECT RELEASE_LOCK('qt_aggregate')");
    }
}

// CLI実行（php aggregate.php）またはcron用トークン付きHTTP実行に対応
if (PHP_SAPI === 'cli' && realpath($argv[0] ?? '') === __FILE__) {
    require __DIR__ . '/../db_connect.php';
    run_aggregation($pdo, require __DIR__ . '/popularity_config.php');
    echo "aggregated\n";
}

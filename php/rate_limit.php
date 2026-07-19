<?php
// IPアドレス単位の汎用レート制限。
// 未認証API（投票・訪問・スポット作成）や登録試行の乱用を防ぐ。
// セッション単位の制限はCookieを捨てれば回避できるため、IPで補完する。

// クライアントIPを取得する。X-Forwarded-For等の詐称可能なヘッダは信用せずREMOTE_ADDRのみ使う
function client_ip(): string
{
    return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}

// 直近 $windowSeconds 秒間の同一(action, IP)の試行回数が $maxCount 以上なら true（＝制限に達した）
function rate_limited(PDO $pdo, string $action, int $maxCount, int $windowSeconds, ?string $ip = null): bool
{
    $ip = $ip ?? client_ip();
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM rate_events
         WHERE action = :action AND ip_address = :ip
           AND created_at > (NOW() - INTERVAL :win SECOND)'
    );
    // INTERVAL にプレースホルダを直接使えないMySQL向けに、秒数は整数として束縛→式で利用
    $stmt->bindValue(':action', $action, PDO::PARAM_STR);
    $stmt->bindValue(':ip', $ip, PDO::PARAM_STR);
    $stmt->bindValue(':win', $windowSeconds, PDO::PARAM_INT);
    $stmt->execute();
    return (int) $stmt->fetchColumn() >= $maxCount;
}

// 試行を1件記録する。副次的に、ごく低確率で古いレコードを掃除する（cron不要の自動GC）
function rate_record(PDO $pdo, string $action, ?string $ip = null): void
{
    $ip = $ip ?? client_ip();
    $pdo->prepare('INSERT INTO rate_events (action, ip_address) VALUES (:action, :ip)')
        ->execute(['action' => $action, 'ip' => $ip]);
    if (random_int(1, 100) === 1) {
        // 1日より古い記録は不要（最長ウィンドウでも1時間程度のため）
        $pdo->query('DELETE FROM rate_events WHERE created_at < (NOW() - INTERVAL 1 DAY)');
    }
}

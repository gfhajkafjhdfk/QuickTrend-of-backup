<?php
require_once __DIR__ . '/auth_check.php';
$name = htmlspecialchars($_SESSION['user_name'] ?? 'ゲスト', ENT_QUOTES, 'UTF-8');
$genre = htmlspecialchars($_SESSION['user_genre'] ?? 'unknown', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickTrend - マイページ</title>
    <link rel="stylesheet" href="../dashboard.css">
    <link rel="icon" type="image/x-icon" href="../image/favicon.ico">
</head>
<body class="dash-body">
    <div class="dash-page">
        <header class="dash-header">
            <div>
                <h1>QuickTrendへようこそ、<?php echo $name; ?> さん</h1>
                <p class="dash-genre">選択ジャンル: <?php echo $genre; ?></p>
            </div>
            <nav class="dash-actions">
                <a href="settings.php">アカウント設定</a>
                <a href="logout.php">ログアウト</a>
            </nav>
        </header>

        <nav class="dash-grid">
            <a class="dash-card" href="../matchAI.html">
                <div class="dash-thumb">
                    <img src="../image/matching.png" alt="AIマッチングのイメージ" loading="lazy">
                </div>
                <div class="dash-card-body">
                    <h2>AIマッチング</h2>
                    <p>進捗データと行動履歴に基づき候補ユーザーを提案します。</p>
                </div>
            </a>

            <a class="dash-card" href="ChatReal.php">
                <div class="dash-thumb thumb-chat">
                    <!-- チャット吹き出しアイコン（外部依存を増やさないためインラインSVG） -->
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
                    </svg>
                </div>
                <div class="dash-card-body">
                    <h2>リアルタイムチャット</h2>
                    <p>他のユーザーとチャットし、交流を深めましょう。</p>
                </div>
            </a>

            <a class="dash-card" href="../Map.html">
                <div class="dash-thumb">
                    <img src="../image/map.webp" alt="マップ機能のイメージ" loading="lazy">
                </div>
                <div class="dash-card-body">
                    <h2>マップ機能</h2>
                    <p>位置情報を使ったマップ表示とおすすめスポットを確認します。</p>
                </div>
            </a>

            <a class="dash-card" href="../QuickTrend.html">
                <div class="dash-thumb thumb-next">
                    <!-- コードアイコン -->
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m18 16 4-4-4-4"/>
                        <path d="m6 8-4 4 4 4"/>
                        <path d="m14.5 4-5 16"/>
                    </svg>
                </div>
                <div class="dash-card-body">
                    <h2>Next.js拡張アプリ</h2>
                    <p>Reactベースの独立アプリへアクセスできます。</p>
                </div>
            </a>
        </nav>
    </div>
</body>
</html>

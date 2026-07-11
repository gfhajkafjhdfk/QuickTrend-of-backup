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
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="page">
        <header class="dashboard-header">
            <div>
                <h1>QuickTrendへようこそ、<?php echo $name; ?> さん</h1>
                <p>選択ジャンル: <?php echo $genre; ?></p>
            </div>
            <div>
                <a class="dashboard-logout" href="settings.php">アカウント設定</a>
                <a class="dashboard-logout" href="logout.php">ログアウト</a>
            </div>
        </header>
        <nav class="card-grid">
            <a class="app-card" href="../matchAI.html">
                <h2>AIマッチング</h2>
                <p>進捗データと行動履歴に基づき候補ユーザーを提案します。</p>
            </a>
            <a class="app-card" href="ChatReal.php">
                <h2>リアルタイムチャット</h2>
                <p>他のユーザーとチャットし、交流を深めましょう。</p>
            </a>
            <a class="app-card" href="../Map.html">
                <h2>マップ機能</h2>
                <p>位置情報を使ったマップ表示とおすすめスポットを確認します。</p>
            </a>
            <a class="app-card" href="../QuickTrend.html">
                <h2>Next.js拡張アプリ</h2>
                <p>Reactベースの独立アプリへアクセスできます。</p>
            </a>
        </nav>
    </div>
</body>
</html>

<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: sighin.html');
    exit;
}
$name = htmlspecialchars($_SESSION['user_name'] ?? 'ゲスト', ENT_QUOTES, 'UTF-8');
$genre = htmlspecialchars($_SESSION['user_genre'] ?? 'unknown', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickTrend - マイページ</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 1.5rem; background: #f2f6fb; color: #28323b; }
        .header { display: flex; justify-content: space-between; align-items: center; }
        .cards { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit,minmax(220px,1fr)); margin-top: 1.5rem; }
        .card { background: white; border-radius: 14px; padding: 1.2rem; box-shadow: 0 16px 40px rgba(36,54,101,.08); text-decoration: none; color: inherit; }
        .card:hover { transform: translateY(-2px); }
        .card h2 { margin: 0 0 .8rem; font-size: 1.1rem; }
        .logout { color: #d53f3f; text-decoration: none; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>QuickTrendへようこそ、<?php echo $name; ?> さん</h1>
            <p>選択ジャンル: <?php echo $genre; ?></p>
        </div>
        <div><a class="logout" href="logout.php">ログアウト</a></div>
    </div>
    <div class="cards">
        <a class="card" href="matchAI.html">
            <h2>AIマッチング</h2>
            <p>進捗データと行動履歴に基づき候補ユーザーを提案します。</p>
        </a>
        <a class="card" href="ChatReal.php">
            <h2>リアルタイムチャット</h2>
            <p>他のユーザーとチャットし、交流を深めましょう。</p>
        </a>
        <a class="card" href="Map.html">
            <h2>マップ機能</h2>
            <p>位置情報を使ったマップ表示とおすすめスポットを確認します。</p>
        </a>
        <a class="card" href="QuickTrend.html">
            <h2>Next.js拡張アプリ</h2>
            <p>Reactベースの独立アプリへアクセスできます。(ai-matching-app/Separate React App
               として独立したNext.jsアプリケーションで構築されている。この構成により、既存PHPシステム
               と分離しながら高度なUIやリアクティブ処理を実装できるようになっている。*これを実現できるよう別ファイルを作成する必要がある*)</p>
        </a>
    </div>
</body>
</html>

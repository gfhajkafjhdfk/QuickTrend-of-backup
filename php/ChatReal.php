<?php
require_once __DIR__ . '/auth_check.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickTrend - チャット</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 1.5rem; background: #f1f5fb; }
        .panel { max-width: 960px; margin: 0 auto; background: white; border-radius: 16px; padding: 1.4rem; box-shadow: 0 15px 36px rgba(23,51,102,.08); }
        #messages { min-height: 320px; padding: 1rem; border: 1px solid #dfe6f0; border-radius: 12px; background: #fbfcff; overflow-y: auto; }
        .message { margin-bottom: .9rem; }
        .message strong { color: #26457f; }
        .message span { display: block; margin-top: .25rem; }
        .chat-form { margin-top: 1rem; display: flex; gap: .8rem; }
        .chat-form input { flex: 1; padding: .9rem; border: 1px solid #c8d2e0; border-radius: 10px; }
        .chat-form button { padding: .9rem 1.3rem; background: #2f71b2; color: white; border: none; border-radius: 10px; cursor: pointer; }
        nav a { margin-right: 1rem; color: #2f71b2; text-decoration: none; }
    </style>
</head>
<body>
    <div class="panel">
        <nav><a href="QuickTrend.php">戻る</a><a href="logout.php">ログアウト</a></nav>
        <h1>チャットルーム</h1>
        <div id="messages">メッセージを読み込み中...</div>
        <form id="chatForm" class="chat-form">
            <input type="text" id="message" placeholder="メッセージを入力" autocomplete="off" required>
            <button type="submit">送信</button>
        </form>
    </div>
    <script>
        const CSRF_TOKEN = '<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>';
        async function loadMessages() {
            const response = await fetch('chat_get.php');
            const data = await response.json();
            const container = document.getElementById('messages');
            container.innerHTML = data.messages.map(msg =>
                `<div class="message"><strong>${msg.user_name}</strong><span>${msg.message}</span><small>${msg.created_at}</small></div>`
            ).join('');
        }
        document.getElementById('chatForm').addEventListener('submit', async function(event) {
            event.preventDefault();
            const message = document.getElementById('message').value.trim();
            if (!message) return;
            await fetch('chat_post.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ message, csrf_token: CSRF_TOKEN })
            });
            document.getElementById('message').value = '';
            await loadMessages();
        });
        loadMessages();
        setInterval(loadMessages, 5000);
    </script>
</body>
</html>

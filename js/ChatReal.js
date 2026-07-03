const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;

// DBの値をtextContentで挿入することでXSSを防ぐ（innerHTMLは使わない）
function renderMessage(msg) {
    const div = document.createElement('div');
    div.className = 'message';
    const name = document.createElement('strong');
    name.textContent = msg.user_name;
    const body = document.createElement('span');
    body.textContent = msg.message;
    const time = document.createElement('small');
    time.textContent = msg.created_at;
    div.append(name, body, time);
    return div;
}

async function loadMessages() {
    try {
        const response = await fetch('chat_get.php');
        const data = await response.json();
        const container = document.getElementById('messages');
        container.replaceChildren(...data.messages.map(renderMessage));
    } catch (error) {
        console.error(error);
    }
}

document.getElementById('chatForm').addEventListener('submit', async function (event) {
    event.preventDefault();
    const message = document.getElementById('message').value.trim();
    if (!message) return;
    const response = await fetch('chat_post.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ message, csrf_token: CSRF_TOKEN })
    });
    if (!response.ok) {
        alert('メッセージの送信に失敗しました。再度ログインしてください。');
        return;
    }
    document.getElementById('message').value = '';
    await loadMessages();
});

loadMessages();
setInterval(loadMessages, 5000);

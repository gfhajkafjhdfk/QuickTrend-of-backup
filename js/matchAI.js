document.getElementById('runMatching').addEventListener('click', async function() {
    const status = document.getElementById('status');
    const candidates = document.getElementById('candidates');
    status.textContent = '候補を取得中です…';
    candidates.innerHTML = '';
    try {
        const progressResponse = await fetch('php/user_progress.php');
        const progressData = await progressResponse.json();
        const listResponse = await fetch('php/get_matching_candidates.php');
        const candidateData = await listResponse.json();
        const predictResponse = await fetch('php/ai/predict.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user: { genre: progressData.genre, progress: progressData.progress }, candidates: candidateData.candidates })
        });
        const result = await predictResponse.json();
        status.textContent = 'マッチング結果を表示します。';
        if (!result.matches || result.matches.length === 0) {
            candidates.textContent = '候補が見つかりませんでした。';
            return;
        }
        candidates.innerHTML = result.matches.map(item =>
            `<div class="candidate"><h3>${item.name}</h3><p>ジャンル: ${item.genre}</p><p>スコア: ${item.score}</p></div>`
        ).join('');
    } catch (error) {
        status.textContent = 'エラーが発生しました。リロードしてください。';
        console.error(error);
    }
});
//グローバル変数をなくすべき
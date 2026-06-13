document.getElementById('runMatching').addEventListener('click', async function () {
    var statusEl = document.getElementById('status');
    var candidatesEl = document.getElementById('candidates');

    statusEl.textContent = '候補を取得中です…';
    candidatesEl.innerHTML = '';

    try {
        var progressRes = await fetch('php/user_progress.php');
        var progressData = await progressRes.json();

        var listRes = await fetch('php/get_matching_candidates.php');
        var candidateData = await listRes.json();

        var predictRes = await fetch('php/ai/predict.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                user: { genre: progressData.genre, progress: progressData.progress },
                candidates: candidateData.candidates
            })
        });
        var result = await predictRes.json();

        statusEl.textContent = 'マッチング結果を表示します。';

        if (!result.matches || result.matches.length === 0) {
            candidatesEl.textContent = '候補が見つかりませんでした。';
            return;
        }

        candidatesEl.innerHTML = result.matches.map(function (item) {
            return (
                '<div class="candidate">' +
                '<h3>' + item.name + '</h3>' +
                '<p>ジャンル: ' + item.genre + '</p>' +
                '<p>スコア: ' + item.score + '</p>' +
                '</div>'
            );
        }).join('');
    } catch (error) {
        statusEl.textContent = 'エラーが発生しました。リロードしてください。';
        console.error(error);
    }
});

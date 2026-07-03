document.addEventListener('DOMContentLoaded', function() {
    fetch('php/session_status.php')
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.logged_in) {
                window.location.href = 'php/QuickTrend.php';
            }
        })
        .catch(function() {
            // セッション情報が取得できない場合はそのままホーム表示
        });

    // ローディング画面: 固定3秒待ちはUXが悪いので、読み込みが済み次第すぐ解除する
    // （演出として最低0.4秒だけ表示を保つ）
    var MIN_LOADING_MS = 400;
    var shownAt = Date.now();
    function hideLoading() {
        var wait = Math.max(0, MIN_LOADING_MS - (Date.now() - shownAt));
        setTimeout(function () {
            document.getElementById('loading-screen').classList.add('hidden');
            document.getElementById('termsPage').classList.remove('hidden');
        }, wait);
    }
    if (document.readyState === 'complete') {
        hideLoading();
    } else {
        window.addEventListener('load', hideLoading);
    }

    // 機能カードのフェードイン
    var cards = document.querySelectorAll('.feature-card');
    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
            }
        });
    }, { threshold: 0.1 });

    cards.forEach(function (card) {
        observer.observe(card);
    });
});

/* AIおすすめニュースティッカー + あなたへのおすすめカード + リアルタイム更新(SSE)
 * - ティッカー: api_recommend.php の項目を右から左に流し、数秒ごとに切り替え
 * - おすすめカード: 最上位のおすすめを理由付きで表示
 * - SSE(php/map/sse.php)の更新通知で、ティッカー/おすすめ/ランキング/スポットを再取得
 */
(function () {
    'use strict';

    const RECOMMEND_API = 'php/map/api_recommend.php';
    const TICKER_ROTATE_MS = 6000;// 項目の切り替え間隔

    /* ---------- DOM構築 ---------- */
    const ticker = document.createElement('div');
    ticker.id = 'news-ticker';
    ticker.className = 'news-ticker';
    ticker.setAttribute('role', 'marquee');
    ticker.setAttribute('aria-label', 'おすすめニュース');
    ticker.innerHTML = '<span class="news-ticker-badge">NEWS</span><div class="news-ticker-track"><span id="news-ticker-text" class="news-ticker-text"></span></div>';
    document.body.appendChild(ticker);

    const card = document.createElement('div');
    card.id = 'recommend-card';
    card.className = 'recommend-card';
    card.setAttribute('aria-label', 'あなたへのおすすめ');
    document.body.appendChild(card);

    /* ---------- データ取得 ---------- */
    let tickerItems = [];
    let tickerIndex = 0;

    async function refreshRecommendations() {
        const params = new URLSearchParams(window.QT_GET_HISTORY_PARAMS ? window.QT_GET_HISTORY_PARAMS() : {});
        if (window.QT_LAST_POSITION) {
            params.set('lat', window.QT_LAST_POSITION.lat);
            params.set('lng', window.QT_LAST_POSITION.lng);
        }
        try {
            const res = await fetch(RECOMMEND_API + '?' + params.toString());
            if (!res.ok) return;
            const data = await res.json();
            tickerItems = data.ticker || [];
            renderCard((data.recommendations || [])[0]);
        } catch (e) {
            console.debug('[recommend] 取得失敗:', e);
        }
    }

    /* ---------- ティッカー描画（右→左に流れ、終わると次の項目へ） ---------- */
    function playNextTickerItem() {
        if (!tickerItems.length) return;
        const item = tickerItems[tickerIndex % tickerItems.length];
        tickerIndex++;
        const text = document.getElementById('news-ticker-text');
        text.textContent = item.icon + ' ' + item.text;// textContentなのでXSS安全
        text.classList.remove('news-ticker-animate');
        void text.offsetWidth;// アニメーションをリスタートさせるためのreflow
        text.classList.add('news-ticker-animate');
    }
    setInterval(playNextTickerItem, TICKER_ROTATE_MS);

    /* ---------- おすすめカード描画 ---------- */
    function renderCard(rec) {
        if (!rec) {
            card.classList.remove('recommend-card-visible');
            return;
        }
        const stars = '★'.repeat(Math.max(1, Math.min(5, Math.round(rec.popularity / 20)))).padEnd(5, '☆');
        const crowd = rec.visits7d >= 100 ? '混雑しています' : rec.visits7d >= 30 ? '混雑は普通です' : '現在混雑は少なめです';
        const walk = rec.distanceKm !== null ? '現在地から徒歩約' + Math.max(1, Math.round(rec.distanceKm * 13)) + '分' : null;

        card.replaceChildren();
        const title = document.createElement('div');
        title.className = 'recommend-card-title';
        title.textContent = '✨ あなたへのおすすめ';
        const name = document.createElement('div');
        name.className = 'recommend-card-name';
        name.textContent = rec.name;
        const meta = document.createElement('div');
        meta.className = 'recommend-card-meta';
        meta.textContent = '⭐ 人気度' + rec.popularity + '　' + stars
            + (rec.avgStayMinutes ? '　平均滞在' + rec.avgStayMinutes + '分' : '');
        const crowdEl = document.createElement('div');
        crowdEl.className = 'recommend-card-crowd';
        crowdEl.textContent = crowd + (walk ? '　・　' + walk : '');
        const reasons = document.createElement('ul');
        reasons.className = 'recommend-card-reasons';
        rec.reasons.forEach(function (r) {
            const li = document.createElement('li');
            li.textContent = r;
            reasons.appendChild(li);
        });
        card.append(title, name, meta, crowdEl, reasons);
        card.classList.add('recommend-card-visible');
        card.style.cursor = 'pointer';
        card.onclick = function () {
            map.flyTo({ center: [rec.longitude, rec.latitude], zoom: 17, duration: 900 });
            if (window.QT_OPEN_STATS) window.QT_OPEN_STATS({ placeId: rec.placeId, label: rec.name });
        };
    }

    /* ---------- リアルタイム更新: SSE + フォールバックポーリング ---------- */
    function onDataChanged() {
        refreshRecommendations();
        if (window.updateRankingUI) window.updateRankingUI();
        if (window.QT_REFRESH_SPOTS) window.QT_REFRESH_SPOTS();
        if (typeof fetchAndUpdateData === 'function') fetchAndUpdateData(true);
    }

    if (typeof EventSource !== 'undefined') {
        const source = new EventSource('php/map/sse.php');
        source.addEventListener('update', onDataChanged);
        source.onerror = function () { /* 自動再接続に任せる */ };
    } else {
        setInterval(onDataChanged, 60000);// SSE非対応ブラウザ用フォールバック
    }

    /* ---------- 初期化 ---------- */
    refreshRecommendations().then(playNextTickerItem);
    setInterval(refreshRecommendations, 120000);// 定期更新（SSEが動かない環境の保険）
})();

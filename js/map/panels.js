/* 統計スライドパネル + 検索連携 + ランキング強化
 * - 検索（Mapbox Geocoder）結果 / マーカークリックの両方から同じパネル・同じグラフを開く
 * - グラフ: 訪問人数推移 / 時間帯別 / 曜日別 / 人気度推移（Chart.js）
 * - ランキングパネルを人気度スコアベース（api_ranking.php）に強化
 */
(function () {
    'use strict';

    const STATS_API = 'php/map/api_stats.php';
    const RANKING_API = 'php/map/api_ranking.php';
    const WEEKDAYS = ['日', '月', '火', '水', '木', '金', '土'];
    const charts = {};

    /* ---------- パネルDOM ---------- */
    const panel = document.createElement('aside');
    panel.id = 'stats-panel';
    panel.className = 'stats-panel';
    panel.setAttribute('aria-label', 'スポット統計');
    panel.innerHTML =
        '<div class="stats-panel-header">' +
        '  <h2 id="stats-panel-title">統計</h2>' +
        '  <button id="stats-panel-close" aria-label="統計パネルを閉じる">×</button>' +
        '</div>' +
        '<div class="stats-panel-summary" id="stats-panel-summary"></div>' +
        '<div class="stats-chart-block"><h3>訪問人数推移（14日）</h3><canvas id="chart-daily"></canvas></div>' +
        '<div class="stats-chart-block"><h3>時間帯別利用者数</h3><canvas id="chart-hourly"></canvas></div>' +
        '<div class="stats-chart-block"><h3>曜日別利用者数</h3><canvas id="chart-weekday"></canvas></div>' +
        '<div class="stats-chart-block"><h3>人気度推移</h3><canvas id="chart-popularity"></canvas></div>' +
        '<p class="stats-panel-note">※ Google口コミ・写真・営業時間の表示は、Google Places APIキー設定後に有効化できます</p>';
    document.body.appendChild(panel);
    document.getElementById('stats-panel-close').addEventListener('click', function () {
        panel.classList.remove('stats-panel-open');
    });

    function summaryItem(label, value) {
        const div = document.createElement('div');
        div.className = 'stats-summary-item';
        const v = document.createElement('strong');
        v.textContent = value;
        const l = document.createElement('span');
        l.textContent = label;
        div.append(v, l);
        return div;
    }

    function renderChart(id, type, labels, data, label) {
        if (charts[id]) charts[id].destroy();
        charts[id] = new Chart(document.getElementById(id), {
            type: type,
            data: {
                labels: labels,
                datasets: [{
                    label: label,
                    data: data,
                    backgroundColor: 'rgba(47, 113, 178, 0.35)',
                    borderColor: '#2f71b2',
                    borderWidth: 2,
                    fill: type === 'line',
                    tension: 0.3
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }

    /* ---------- 統計の取得と描画（検索・クリック共通の入口） ---------- */
    window.QT_OPEN_STATS = async function (params) {
        // params: { placeId } または { lat, lng, radius, label }
        const query = new URLSearchParams();
        if (params.placeId) {
            query.set('place_id', params.placeId);
        } else {
            query.set('lat', params.lat);
            query.set('lng', params.lng);
            query.set('radius', params.radius || 500);
            query.set('label', params.label || '');
        }
        try {
            const res = await fetch(STATS_API + '?' + query.toString());
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const stats = await res.json();

            document.getElementById('stats-panel-title').textContent = '📊 ' + (stats.label || params.label || '統計');
            const summary = document.getElementById('stats-panel-summary');
            summary.replaceChildren(
                summaryItem('人気度', stats.score !== null ? String(stats.score) : 'データなし'),
                summaryItem('週間訪問', String(stats.totals.visits7d) + '人'),
                summaryItem('平均滞在', Math.round(stats.totals.avgStaySeconds / 60) + '分'),
                summaryItem('再訪率', Math.round(stats.totals.revisitRate * 100) + '%')
            );

            renderChart('chart-daily', 'line',
                stats.daily.map(d => d.date.slice(5)), stats.daily.map(d => d.visits), '訪問人数');
            const hourly = new Array(24).fill(0);
            stats.hourly.forEach(h => { hourly[h.hour] = h.visits; });
            renderChart('chart-hourly', 'bar',
                hourly.map((_, i) => i + '時'), hourly, '利用者数');
            const weekday = new Array(7).fill(0);
            stats.weekday.forEach(d => { weekday[d.dow - 1] = d.visits; });// DAYOFWEEK: 1=日曜
            renderChart('chart-weekday', 'bar', WEEKDAYS, weekday, '利用者数');
            renderChart('chart-popularity', 'line',
                stats.popularityTrend.map(d => d.date.slice(5)), stats.popularityTrend.map(d => d.score), '人気度');

            panel.classList.add('stats-panel-open');
        } catch (e) {
            console.error('[stats] 取得失敗:', e);
        }
    };

    /* ---------- 検索連携: Geocoder結果で統計を開き、履歴を保存 ---------- */
    const CATEGORY_KEYWORDS = ['カフェ', '公園', 'ラーメン', '美術館', '駅', 'レストラン', '神社', '寺', 'ホテル', '温泉'];
    function saveSearchHistory(text) {
        try {
            const hist = JSON.parse(localStorage.getItem('qt_search_history') || '[]');
            hist.unshift({ text: text, at: Date.now() });
            localStorage.setItem('qt_search_history', JSON.stringify(hist.slice(0, 20)));
        } catch (e) { /* localStorage不可なら履歴なしで続行 */ }
    }
    if (typeof geocoder !== 'undefined') {
        geocoder.on('result', function (e) {
            const name = e.result.text || e.result.place_name;
            const [lng, lat] = e.result.center;
            saveSearchHistory(name);
            // 地図移動はGeocoderが行うので、統計パネルだけ開く
            window.QT_OPEN_STATS({ lat: lat, lng: lng, radius: 600, label: name + ' 周辺' });
        });
    }

    // ticker.js が履歴からカテゴリ・名称を組み立てるためのヘルパー
    window.QT_GET_HISTORY_PARAMS = function () {
        let hist = [];
        try {
            hist = JSON.parse(localStorage.getItem('qt_search_history') || '[]');
        } catch (e) { /* 履歴なし */ }
        const texts = hist.map(h => h.text);
        const cats = CATEGORY_KEYWORDS.filter(k => texts.some(t => t && t.includes(k)));
        return { cats: cats.slice(0, 5).join(','), hist: texts.slice(0, 5).join(',') };
    };

    /* ---------- マーカークリック連携: 既存ポップアップに加えて統計パネルを開く ---------- */
    const origShowDetailPopup = window.showDetailPopup;
    window.showDetailPopup = function (lngLat, name, lat, lng) {
        origShowDetailPopup(lngLat, name, lat, lng);
        window.QT_OPEN_STATS({ lat: lat, lng: lng, radius: 150, label: name });
        saveSearchHistory(name);// 閲覧履歴としても記録（おすすめ計算に使用）
    };

    /* ---------- ランキングパネルを人気度ベースに強化（既存関数を置き換え） ---------- */
    window.updateRankingUI = async function () {
        const rankingList = document.getElementById('ranking-list');
        if (!rankingList) return;
        try {
            const res = await fetch(RANKING_API + '?limit=5');
            if (!res.ok) return;
            const data = await res.json();
            if (!data.ranking.length) return;
            rankingList.replaceChildren(...data.ranking.map(function (item) {
                const li = document.createElement('li');
                li.className = 'ranking-item';
                li.setAttribute('role', 'listitem');
                li.style.cursor = 'pointer';
                const rank = document.createElement('span');
                rank.className = 'ranking-rank';
                rank.textContent = String(item.rank);
                const name = document.createElement('span');
                name.className = 'ranking-name';
                name.textContent = item.name;// textContentなのでXSS安全
                name.title = item.name;
                const count = document.createElement('span');
                count.className = 'ranking-count';
                count.textContent = '⭐' + Math.round(item.score);
                li.append(rank, name, count);
                li.addEventListener('click', function () {
                    map.flyTo({ center: [item.longitude, item.latitude], zoom: 17, duration: 800 });
                    window.QT_OPEN_STATS({ placeId: item.placeId, label: item.name });
                });
                return li;
            }));
        } catch (e) {
            console.debug('[ranking] 更新失敗:', e);
        }
    };
    // 初回表示（既存のfetchAndUpdateData到達前でも表示できるように）
    window.updateRankingUI();
})();

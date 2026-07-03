/* 匿名位置情報による訪問判定
 * - localStorageの匿名ID（ランダム値。ユーザーIDとは無関係）だけを使う
 * - 同じ場所に MIN_STAY_SECONDS 以上とどまったら「訪問」としてサーバに送信
 * - GPSの生ログは端末外に出さない（送るのは判定済みの1点と滞在秒数のみ）
 */
(function () {
    'use strict';

    const MIN_STAY_SECONDS = 300;   // 訪問と判定する最低滞在時間（5分）
    const MOVE_THRESHOLD_M = 80;    // これ以上動いたら「その場を離れた」とみなす
    const VISIT_API = 'php/map/api_visit.php';

    function getAnonId() {
        let id = localStorage.getItem('qt_anon_id');
        if (!id) {
            id = (crypto.randomUUID ? crypto.randomUUID() : String(Math.random()) + Date.now()).replace(/-/g, '');
            localStorage.setItem('qt_anon_id', id);
        }
        return id;
    }

    function distanceMeters(lat1, lng1, lat2, lng2) {
        const dLat = (lat2 - lat1) * 111000;
        const dLng = (lng2 - lng1) * 111000 * Math.cos(lat1 * Math.PI / 180);
        return Math.sqrt(dLat * dLat + dLng * dLng);
    }

    let anchor = null; // { lat, lng, since } 滞在判定の基準点

    async function sendVisit(lat, lng, staySeconds, useKeepalive) {
        try {
            await fetch(VISIT_API, {
                method: 'POST',
                keepalive: !!useKeepalive, // ページ離脱時でも送信を完了させる
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.QT_CSRF || '' },
                body: JSON.stringify({
                    anonId: getAnonId(),
                    latitude: lat,
                    longitude: lng,
                    staySeconds: Math.round(staySeconds)
                })
            });
        } catch (e) {
            console.debug('[visit] 送信スキップ:', e);
        }
    }

    function flushIfVisited(useKeepalive) {
        if (!anchor) return;
        const stay = (Date.now() - anchor.since) / 1000;
        if (stay >= MIN_STAY_SECONDS) {
            sendVisit(anchor.lat, anchor.lng, stay, useKeepalive);
        }
        anchor = null;
    }

    function onPosition(pos) {
        const { latitude: lat, longitude: lng } = pos.coords;
        window.QT_LAST_POSITION = { lat, lng }; // おすすめAPI用に共有（端末内のみ）
        if (!anchor) {
            anchor = { lat, lng, since: Date.now() };
            return;
        }
        if (distanceMeters(anchor.lat, anchor.lng, lat, lng) > MOVE_THRESHOLD_M) {
            flushIfVisited(false); // 離れたので滞在を確定
            anchor = { lat, lng, since: Date.now() };
        }
    }

    if ('geolocation' in navigator) {
        navigator.geolocation.watchPosition(onPosition, function () {
            // 位置情報が拒否されたら何もしない（訪問判定なしでも他機能は動く）
        }, { enableHighAccuracy: false, maximumAge: 30000, timeout: 20000 });

        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'hidden') flushIfVisited(true);
        });
    }
})();

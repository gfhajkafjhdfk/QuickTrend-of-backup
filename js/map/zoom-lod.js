/* ズームレベルによる表示切り替え（Mapbox版LOD）
 *   ズーム < 11        : ヒートマップ（人気の面的分布）
 *   11 <= ズーム < 15  : 注目エリア（クラスタ円）+ 人気スポット円
 *   ズーム >= 15       : 既存の3D棒グラフ表示（従来どおり）
 * Google Mapsのzoom_changed / MarkerClustererに相当する機能を、
 * 既存スタックのMapbox（map.on('zoom') + cluster GeoJSONソース）で実装している
 */
(function () {
    'use strict';

    const ZOOM_HEAT_MAX = 11;   // これ未満はヒートマップ
    const ZOOM_DETAIL_MIN = 15; // これ以上は既存の3Dグラフ（MAP_CONFIG.lod.highと一致）

    let spotsData = { type: 'FeatureCollection', features: [] };

    // api.php のGeoJSON（票数+人気度）をクラスタ用ソースとして取得
    async function refreshSpots() {
        try {
            const res = await fetch('php/api.php');
            if (!res.ok) return;
            const data = await res.json();
            spotsData = data;
            const src = map.getSource('spots-cluster');
            if (src) src.setData(spotsData);
        } catch (e) {
            console.debug('[lod] スポット取得失敗:', e);
        }
    }

    function addLodLayers() {
        if (map.getSource('spots-cluster')) return;

        map.addSource('spots-cluster', {
            type: 'geojson',
            data: spotsData,
            cluster: true,
            clusterRadius: 60,
            clusterMaxZoom: 14,
            // 注目エリアの集計値（クラスタ内の合計人気度・訪問数・票数）
            clusterProperties: {
                sum_score: ['+', ['coalesce', ['get', 'score'], 0]],
                sum_visits: ['+', ['coalesce', ['get', 'visits7d'], 0]],
                sum_count: ['+', ['coalesce', ['get', 'count'], 0]],
                sum_stay: ['+', ['coalesce', ['get', 'avg_stay_seconds'], 0]]
            }
        });

        // ヒートマップ（低ズーム）
        map.addLayer({
            id: 'trend-heat',
            type: 'heatmap',
            source: 'spots-cluster',
            maxzoom: ZOOM_HEAT_MAX + 1,
            paint: {
                'heatmap-weight': ['interpolate', ['linear'], ['coalesce', ['get', 'score'], ['get', 'sum_score'], 0], 0, 0.1, 50, 0.6, 100, 1],
                'heatmap-intensity': 1.2,
                'heatmap-radius': ['interpolate', ['linear'], ['zoom'], 5, 15, 11, 30],
                'heatmap-opacity': 0.75
            }
        });

        // 注目エリア（クラスタ円・中ズーム）
        map.addLayer({
            id: 'hot-areas',
            type: 'circle',
            source: 'spots-cluster',
            filter: ['has', 'point_count'],
            minzoom: ZOOM_HEAT_MAX,
            maxzoom: ZOOM_DETAIL_MIN,
            paint: {
                'circle-color': ['interpolate', ['linear'], ['get', 'sum_score'], 0, '#4FC3F7', 100, '#FFD54F', 250, '#FF5252'],
                'circle-radius': ['interpolate', ['linear'], ['get', 'point_count'], 2, 20, 10, 32, 30, 44],
                'circle-opacity': 0.75,
                'circle-stroke-width': 2,
                'circle-stroke-color': '#ffffff'
            }
        });
        map.addLayer({
            id: 'hot-areas-label',
            type: 'symbol',
            source: 'spots-cluster',
            filter: ['has', 'point_count'],
            minzoom: ZOOM_HEAT_MAX,
            maxzoom: ZOOM_DETAIL_MIN,
            layout: {
                'text-field': ['concat', '🔥 ', ['to-string', ['get', 'point_count']]],
                'text-size': 13,
                'text-font': ['Open Sans Semibold', 'Arial Unicode MS Bold']
            },
            paint: { 'text-color': '#333', 'text-halo-color': '#fff', 'text-halo-width': 1.5 }
        });

        // 人気スポットごとの円（中ズーム・クラスタ外の単独ポイント）
        map.addLayer({
            id: 'spot-circles',
            type: 'circle',
            source: 'spots-cluster',
            filter: ['!', ['has', 'point_count']],
            minzoom: ZOOM_HEAT_MAX,
            maxzoom: ZOOM_DETAIL_MIN,
            paint: {
                'circle-color': ['interpolate', ['linear'], ['coalesce', ['get', 'score'], 0], 0, '#4FC3F7', 50, '#FFD54F', 100, '#FF5252'],
                'circle-radius': ['interpolate', ['linear'], ['coalesce', ['get', 'score'], 0], 0, 5, 50, 10, 100, 16],
                'circle-opacity': 0.85,
                'circle-stroke-width': 1,
                'circle-stroke-color': '#fff'
            }
        });

        // 注目エリアクリック → エリア情報ポップアップ + ズームイン
        map.on('click', 'hot-areas', function (e) {
            const f = e.features[0];
            const p = f.properties;
            const content = document.createElement('div');
            const title = document.createElement('div');
            title.className = 'popup-title';
            title.textContent = '🔥 注目エリア';
            const body = document.createElement('div');
            body.className = 'popup-stat';
            body.textContent = 'スポット' + p.point_count + '件 / 合計人気度' + Math.round(p.sum_score)
                + ' / 週間訪問' + p.sum_count + '票+' + p.sum_visits + '人';
            const zoomBtn = document.createElement('button');
            zoomBtn.textContent = 'エリアを見る';
            zoomBtn.addEventListener('click', function () {
                map.getSource('spots-cluster').getClusterExpansionZoom(f.properties.cluster_id, function (err, zoom) {
                    if (!err) map.easeTo({ center: f.geometry.coordinates, zoom: Math.min(zoom + 1, 16) });
                });
            });
            content.append(title, body, zoomBtn);
            new mapboxgl.Popup({ offset: 12 }).setLngLat(f.geometry.coordinates).setDOMContent(content).addTo(map);
        });

        // 単独スポット円クリック → 既存の詳細ポップアップを再利用
        map.on('click', 'spot-circles', function (e) {
            const f = e.features[0];
            const [lng, lat] = f.geometry.coordinates;
            showDetailPopup(e.lngLat, f.properties.name, lat, lng);
        });
        ['hot-areas', 'spot-circles'].forEach(function (layer) {
            map.on('mouseenter', layer, function () { map.getCanvas().style.cursor = 'pointer'; });
            map.on('mouseleave', layer, function () { map.getCanvas().style.cursor = ''; });
        });

        applyLod();
    }

    // 既存のzoomハンドラ（trend-bars-2d/3dの切替）の後に上書き適用する
    function applyLod() {
        const z = map.getZoom();
        // 15未満の円表示はspot-circles/hot-areas/ヒートマップが担うため、旧2D円は常に隠す
        if (map.getLayer('trend-bars-2d')) {
            map.setLayoutProperty('trend-bars-2d', 'visibility', 'none');
        }
        if (map.getLayer('trend-tops')) {
            map.setLayoutProperty('trend-tops', 'visibility', z >= ZOOM_DETAIL_MIN ? 'visible' : 'none');
        }
    }

    map.on('load', function () {
        addLodLayers();
        refreshSpots();
        // 既存スクリプトのzoomハンドラより後に登録することで、表示の最終決定権を持つ
        map.on('zoom', applyLod);
    });
    // 昼/夜スタイル切替後にレイヤーを復元（既存addLayers()の後に走らせる）
    map.on('style.load', function () {
        map.once('idle', addLodLayers);
    });

    // 60秒ごと + SSE通知で更新（ticker.jsからも呼べるように公開）
    window.QT_REFRESH_SPOTS = refreshSpots;
    setInterval(refreshSpots, 60000);
})();

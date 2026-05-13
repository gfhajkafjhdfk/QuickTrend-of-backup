async function loadMapData() {
    const frame = document.getElementById('mapFrame');
    frame.textContent = '読み込み中…';
    try {
        const response = await fetch('php/api.php?action=location');
        const data = await response.json();
        frame.innerHTML = '<h2>スポット情報</h2>' + data.locations.map(location =>
            `<div><strong>${location.name}</strong><p>${location.description}</p><p>緯度: ${location.latitude}, 経度: ${location.longitude}</p></div>`
        ).join('');
    } catch (error) {
        frame.textContent = 'マップ情報を取得できませんでした。';
        console.error(error);
    }
}

window.addEventListener('DOMContentLoaded', function() {
    document.getElementById('refresh').addEventListener('click', loadMapData);
    loadMapData();
});

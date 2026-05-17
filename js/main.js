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
});

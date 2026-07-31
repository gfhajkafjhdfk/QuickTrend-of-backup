// QuickTrend フロントエンド設定の雛形（テンプレート）。
// このファイルを同じ js/ ディレクトリに config.local.js としてコピーし、
// 実際の値を設定してください。config.local.js は .gitignore 済みでコミットされません。
//
// 【Mapboxトークン】
//  - "pk." で始まる公開(publishable)トークン。ブラウザに配信されるため完全には隠せません。
//  - 必ず Mapbox ダッシュボード（https://account.mapbox.com）で URL(ドメイン)制限を設定し、
//    自分のドメイン以外では使えないようにしてください（万一漏れても悪用されにくくなります）。
//  - サーバー(/var/www/quicktrend/js/config.local.js)にも同ファイルを配置してください。
window.MAPBOX_TOKEN = 'pk.xxxxxxxx-set-your-domain-restricted-token-here';

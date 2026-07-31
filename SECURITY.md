# Security Policy / セキュリティポリシー

## Reporting a Vulnerability / 脆弱性の報告

Please **do not** open a public Issue for security problems.
セキュリティに関する問題は、**公開Issueではなく非公開で**報告してください。

- Preferred: GitHub の **Private vulnerability reporting**（Security タブ →
  "Report a vulnerability"）を利用してください。
  （リポジトリ管理者は Settings → Code security にて機能を有効化してください）
- 報告には、再現手順・影響範囲・想定される攻撃シナリオを含めていただけると助かります。

We aim to acknowledge reports within a few days.
報告にはできる限り数日以内に対応します。

## Supported Versions / 対象

`main` ブランチ（本番稼働中のコード）を対象とします。

## Notes / 補足

- クライアントに配信される API トークン（例: Mapbox の `pk.` トークン）は
  秘匿できないため、各サービス側で **ドメイン/URL 制限**を設定して悪用を防いでいます。
- 認証情報・DB 接続情報はリポジトリに含めず、`config.local.php` /
  `js/config.local.js`（いずれも git 管理外）から読み込みます。

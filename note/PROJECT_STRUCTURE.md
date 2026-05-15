# QuickTrend - プロジェクト構造整理ガイド

##  実装完了内容

このプロジェクトは、参考のGitHubページ（https://gfhajkafjhdfk.github.io/ChatReal/）のデザインと構造に合わせて整理されました。

### ✅ 完了した改善

#### 1. **統合スタイルシート作成** (`style.css`)
- **パス**: `style.css` （ルートディレクトリ）
- **特徴**:
  - ブルーグラデーション背景 (#0d1b3a → #1a3a52)
  - モダンでテック感のあるデザイン
  - レスポンシブ対応（モバイル/タブレット/デスクトップ）
  - ダークモード対応
  - ボタン、フォーム、リスト、ナビゲーションなど全要素をカバー
  
#### 2. **HTML統合**
- すべてのHTMLファイルに `style.css` 外部リンクを追加
  - `index.html` - ホームページ
  - `ChatReal.html` - チャット機能
  - `Map.html` - マップ表示
  - `matchAI.html` - AIマッチング
  - `sighin.html` - ログイン
  - `sighup.html` - 新規登録
  - `QuickTrend.html` - リダイレクト（変更なし）

#### 3. **Favicon対応**
- すべてのHTMLに `favicon.ico` 参照を追加
- ファイルは後から配置可能（`/favicon.ico`）

#### 4. **ディレクトリ作成**
- `images/` フォルダ作成（画像アセット管理用）

---

## 📁 推奨ファイル構造

```
QuickTrend-of-backup/
│
├── 📄 index.html              ⭐ メインホーム
├── 📄 ChatReal.html           ⭐ チャット機能
├── 📄 Map.html                ⭐ マップ機能
├── 📄 matchAI.html            ⭐ AIマッチング
├── 📄 sighin.html             ⭐ ログイン
├── 📄 sighup.html             ⭐ 新規登録
├── 📄 QuickTrend.html         ⭐ リダイレクト
│
├── 📄 style.css               ✨ 統合スタイルシート（新規）
├── 📄 favicon.ico             ✨ ファビコン参照（要配置）
├── 📄 README.md               📖 プロジェクト説明
├── 📄 database.sql            🗄️ データベース定義
│
├── 📁 js/                     🔧 JavaScriptファイル
│   ├── main.js
│   ├── Map.js
│   └── matchAI.js
│
├── 📁 php/                    🐘 PHPバックエンド
│   ├── api.php
│   ├── auth_check.php
│   ├── chat_get.php
│   ├── chat_post.php
│   ├── ChatReal.php
│   ├── config.php
│   ├── db_connect.php
│   ├── get_matching_candidates.php
│   ├── login.php
│   ├── logout.php
│   ├── QuickTrend.php
│   ├── register.php
│   ├── session_status.php
│   ├── user_progress.php
│   └── 📁 ai/
│       └── predict.php
│
├── 📁 ai/                     🤖 Python AI機能
│   └── predict.py
│
├── 📁 utils/                  🛠️ ユーティリティ
│   └── __init__.py
│
├── 📁 ai-matching-app/        📦 Node.js アプリケーション
│   ├── package.json
│   ├── README.md
│   └── 📁 pages/
│       └── index.js
│
├── 📁 note/                   📝 メモ・ドキュメント
│   ├── memo.tx
│   └── requirements.txt
│
└── 📁 images/                 🖼️ 画像アセット（新規）
    ├── backimage.jpg          （要配置）
    ├── chat.jpg               （要配置）
    └── （その他の画像）
```

---

## 🎨 デザイン特徴

### カラースキーム
- **プライマリブルー**: `#2f71b2`
- **ダークブルー**: `#1d4e89`
- **グレー**: `#5d728f`
- **背景グラデーション**: `#0d1b3a` → `#1a3a52`

### コンポーネント
- ✅ ボタン - グラデーション、ホバーエフェクト付き
- ✅ フォーム - 統一されたインプットデザイン
- ✅ ナビゲーション - スティッキー対応
- ✅ リスト - アイコン付き（チェックマーク）
- ✅ メッセージボックス - 成功/警告/情報表示

### レスポンシブ
- **デスクトップ**: フル表示
- **タブレット** (768px): 最適化レイアウト
- **モバイル** (480px): スタックレイアウト

---

## 🔗 リンク参照（相対パス）

すべての参照は相対パスで指定されており、ファイルの配置場所によって自動的に解決されます：

```
ホームページ (index.html)
  ├─ sighin.html            → ログインページ
  ├─ sighup.html            → 登録ページ
  └─ php/QuickTrend.php     → メインアプリ

ログイン (sighin.html)
  ├─ php/login.php          → ログイン処理
  └─ sighup.html            → 登録ページ

登録 (sighup.html)
  ├─ php/register.php       → 登録処理
  └─ sighin.html            → ログインページ

チャット (ChatReal.html)
  ├─ php/QuickTrend.php     → 戻る
  └─ php/logout.php         → ログアウト

AIマッチング (matchAI.html)
  ├─ js/matchAI.js          → スクリプト
  ├─ php/QuickTrend.php     → 戻る
  └─ php/logout.php         → ログアウト
```

---

## 📦 今後の追加予定

以下のファイルは後から配置してください：

1. **favicon.ico** - `favicon.ico` をルートに配置
2. **背景画像** - `images/backimage.jpg` を配置
3. **チャット画像** - `images/chat.jpg` を配置

---

## 🚀 デプロイ時のチェックリスト

- [ ] `style.css` が正しく読み込まれているか確認
- [ ] ファビコンが表示されるか確認
- [ ] すべてのリンクが正常に機能するか確認
- [ ] モバイル表示がレスポンシブか確認
- [ ] 画像ファイルが配置されているか確認

---

## 📝 注記

このプロジェクトは、以下の技術スタックで構成されています：
- **フロントエンド**: HTML5, CSS3, JavaScript
- **バックエンド**: PHP, MySQL
- **AI機能**: Python, Node.js (Next.js)

参考: https://gfhajkafjhdfk.github.io/ChatReal/

---

**最終更新**: 2026年5月13日

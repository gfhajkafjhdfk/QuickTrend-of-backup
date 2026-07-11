-- 開発環境専用のシードデータ。本番サーバーには投入しないこと
USE quicktrend;
INSERT INTO users (name, email, password_hash, genre, profile, created_at) VALUES
('テストユーザー', 'test@example.com', '$2y$10$e0NR7D1pC1MGfR12Q1oyJOv7i3Q9EbSMn1jVdFV0j3xl/7DHGQ7e6', 'music', 'テスト用のユーザーです。', NOW());

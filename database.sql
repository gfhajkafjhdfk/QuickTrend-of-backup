CREATE DATABASE IF NOT EXISTS quicktrend CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE quicktrend;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    genre VARCHAR(80) NOT NULL,
    profile TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    description TEXT,
    latitude DECIMAL(10,7) NOT NULL,
    longitude DECIMAL(10,7) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category VARCHAR(120) NOT NULL,
    progress_score INT NOT NULL,
    last_activity DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ログイン失敗の記録（ブルートフォース対策のレート制限用）
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email_time (email, attempted_at),
    INDEX idx_ip_time (ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ai_training (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    input_data TEXT NOT NULL,
    predicted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    output_data TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 地図トレンド機能
-- ============================================================

-- スポット（投票・訪問の対象）。lat_r/lng_r は小数4桁の丸め座標（フロントのfeature_idと同じ識別方式）
CREATE TABLE IF NOT EXISTS places (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    category VARCHAR(50) DEFAULT NULL,
    latitude DECIMAL(10,7) NOT NULL,
    longitude DECIMAL(10,7) NOT NULL,
    lat_r DECIMAL(8,4) NOT NULL,
    lng_r DECIMAL(9,4) NOT NULL,
    vote_count INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_place (name, lat_r, lng_r),
    INDEX idx_geo (lat_r, lng_r)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 匿名訪問ログ。anon_hash は「クライアント生成の匿名ID + サーバ側ソルト」のSHA-256で、個人と紐づかない
CREATE TABLE IF NOT EXISTS visits (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    place_id INT NOT NULL,
    anon_hash CHAR(64) NOT NULL,
    stay_seconds INT NOT NULL DEFAULT 0,
    visited_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_place_time (place_id, visited_at),
    INDEX idx_anon_place (anon_hash, place_id, visited_at),
    FOREIGN KEY (place_id) REFERENCES places(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 日次集計（バッチで更新。統計表示はここを読む＝生ログを毎回舐めない）
CREATE TABLE IF NOT EXISTS daily_statistics (
    place_id INT NOT NULL,
    stat_date DATE NOT NULL,
    visit_count INT NOT NULL DEFAULT 0,
    unique_visitors INT NOT NULL DEFAULT 0,
    avg_stay_seconds INT NOT NULL DEFAULT 0,
    revisit_count INT NOT NULL DEFAULT 0,
    PRIMARY KEY (place_id, stat_date),
    FOREIGN KEY (place_id) REFERENCES places(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 時間帯別集計（時間帯別利用者数グラフ用）
CREATE TABLE IF NOT EXISTS hourly_statistics (
    place_id INT NOT NULL,
    stat_date DATE NOT NULL,
    stat_hour TINYINT NOT NULL,
    visit_count INT NOT NULL DEFAULT 0,
    avg_stay_seconds INT NOT NULL DEFAULT 0,
    PRIMARY KEY (place_id, stat_date, stat_hour),
    FOREIGN KEY (place_id) REFERENCES places(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 人気度ランキングのキャッシュ（読み取り専用。バッチが再計算する）
CREATE TABLE IF NOT EXISTS ranking_cache (
    place_id INT PRIMARY KEY,
    popularity_score DOUBLE NOT NULL DEFAULT 0,
    prev_score DOUBLE NOT NULL DEFAULT 0,
    visit_count_7d INT NOT NULL DEFAULT 0,
    unique_visitors_7d INT NOT NULL DEFAULT 0,
    avg_stay_seconds INT NOT NULL DEFAULT 0,
    revisit_rate DOUBLE NOT NULL DEFAULT 0,
    rank_position INT NOT NULL DEFAULT 0,
    computed_at DATETIME NOT NULL,
    FOREIGN KEY (place_id) REFERENCES places(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- バッチ集計の状態管理
CREATE TABLE IF NOT EXISTS aggregate_meta (
    meta_key VARCHAR(50) PRIMARY KEY,
    meta_value VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 開発用のテストユーザーは database.seed.dev.sql に分離した（本番には投入しない）

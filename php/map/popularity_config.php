<?php
// 人気度スコアの設定。重みはここを書き換えるだけで全体（集計・ランキング・おすすめ）に反映される
// Popularity Score = 訪問人数(正規化) * visitors + 平均滞在時間(正規化) * stay + 再訪率 * revisit
return [
    'weights' => [
        'visitors' => 0.6,
        'stay' => 0.3,
        'revisit' => 0.1,
    ],
    // 正規化の上限（この値で0〜100に丸める）。利用者が増えたら引き上げる
    'norm' => [
        'visitors_max' => 200,   // 7日間の訪問人数がこの値でスコア100
        'stay_max_seconds' => 3600, // 平均滞在1時間でスコア100
    ],
    'window_days' => 7,          // スコア計算の対象期間
    'cache_ttl_seconds' => 300,  // ランキングキャッシュの鮮度（これを過ぎたら再集計）
    'visit_dedupe_minutes' => 30,// 同一匿名IDの同一スポット訪問はこの間隔以内なら重複扱い
];

<?php

return [
    // public/js/face-engine.js の MODEL_VERSION と必ず一致させる
    'model_version' => env('FACE_MODEL_VERSION', 'human-3.3.6-faceres'),

    // 判定しきい値（コサイン類似度）。初期値は仮なので、照合テスト画面で実測して調整する
    'threshold' => (float) env('FACE_MATCH_THRESHOLD', 0.6),

    // 1位と2位の人物の類似度の差がこれ未満なら「判定保留」にする
    'margin' => (float) env('FACE_MATCH_MARGIN', 0.05),

    'min_dimension' => 64,
    'max_dimension' => 2048,
    'max_samples_per_request' => 10,
];
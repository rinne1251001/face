<?php

use App\Models\Admin;
use App\Models\Teacher;

return [

    /*
    |--------------------------------------------------------------------------
    | 既定のガード
    |--------------------------------------------------------------------------
    |
    | auth:admin / auth:teacher のミドルウェアを通ったルートの中では、
    | そのガードが自動で既定になる（$request->user() がその人を返す）。
    | ここで決めているのは、ミドルウェアを通らない場面での既定。
    |
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'teacher'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'teachers'),
    ],

    /*
    |--------------------------------------------------------------------------
    | ガード（ログインの仕組み）
    |--------------------------------------------------------------------------
    |
    | admin   : システム管理者（/admin/...）
    | teacher : 教師（/teacher/...）
    | 同じブラウザで両方にログインしても、別々に管理される。
    |
    */

    'guards' => [
        'admin' => [
            'driver' => 'session',
            'provider' => 'admins',
        ],
        'teacher' => [
            'driver' => 'session',
            'provider' => 'teachers',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | プロバイダー（ログインする人をどのテーブルから探すか）
    |--------------------------------------------------------------------------
    */

    'providers' => [
        'admins' => [
            'driver' => 'eloquent',
            'model' => Admin::class,
        ],
        'teachers' => [
            'driver' => 'eloquent',
            'model' => Teacher::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | パスワードリセット（今は画面を作っていないので未使用）
    |--------------------------------------------------------------------------
    */

    'passwords' => [
        'teachers' => [
            'provider' => 'teachers',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
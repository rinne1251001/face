<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // 初回ログイン後、必ずパスワードを変更すること
        Admin::updateOrCreate(
            ['login_id' => env('ADMIN_LOGIN_ID', 'admin')],
            ['name' => '管理者', 'password' => env('ADMIN_PASSWORD', 'change-me-please')],
        );
    }
}
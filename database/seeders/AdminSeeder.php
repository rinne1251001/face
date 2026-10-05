<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Admin::updateOrCreate(
            ['login_id' => env('ADMIN_LOGIN_ID', 'admin')],
            ['password' => env('ADMIN_PASSWORD', 'change-me-please')],
        );
    }
}
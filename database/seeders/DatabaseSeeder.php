<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 順番が大事：クラス・授業 → 教師（授業を担当させる） → 生徒
        $this->call([
            AdminSeeder::class,
            SchoolSeeder::class,
            TeacherSeeder::class,
        ]);

        // 見本の生徒は開発環境でだけ作る
        if (app()->environment('local')) {
            $this->call(DemoStudentSeeder::class);
        }
    }
}

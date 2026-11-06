<?php

namespace Database\Seeders;

use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class SchoolSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['1年A組', '1年B組'] as $name) {
            SchoolClass::firstOrCreate(['name' => $name]);
        }

        // 数学：月曜1限と木曜2限（週2回でも1つの授業）
        $math = Subject::firstOrCreate(['name' => '数学'], ['start_date' => '2026-09-01', 'end_date' => '2027-01-31']);
        $math->schedules()->firstOrCreate(['day_of_week' => 1], ['period_start' => 1, 'period_end' => 1]);
        $math->schedules()->firstOrCreate(['day_of_week' => 4], ['period_start' => 2, 'period_end' => 2]);

        // 情報実習：水曜3〜5限
        $it = Subject::firstOrCreate(['name' => '情報実習']);
        $it->schedules()->firstOrCreate(['day_of_week' => 3], ['period_start' => 3, 'period_end' => 5]);
    }
}
<?php

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class SchoolSeeder extends Seeder
{
    public function run(): void
    {
        // 教室（クラス・授業より先に作る）
        $room301 = Classroom::firstOrCreate(['name' => '301教室'], ['floor' => 3]);
        $room302 = Classroom::firstOrCreate(['name' => '302教室'], ['floor' => 3]);
        $pcRoom = Classroom::firstOrCreate(['name' => 'PC室'], ['floor' => 4]);

        // クラス => ホームルームの教室
        foreach (['1年A組' => $room301, '1年B組' => $room302] as $name => $room) {
            SchoolClass::firstOrCreate(['name' => $name], ['classroom_id' => $room->id]);
        }

        // 数学：月曜1限と木曜2限（週2回でも1つの授業）
        $math = Subject::firstOrCreate(['name' => '数学'], ['start_date' => '2026-09-01', 'end_date' => '2027-01-31']);
        $math->schedules()->firstOrCreate(['day_of_week' => 1], ['period_start' => 1, 'period_end' => 1, 'classroom_id' => $room301->id]);
        $math->schedules()->firstOrCreate(['day_of_week' => 4], ['period_start' => 2, 'period_end' => 2, 'classroom_id' => $room301->id]);

        // 情報実習：水曜3〜5限
        $it = Subject::firstOrCreate(['name' => '情報実習']);
        $it->schedules()->firstOrCreate(['day_of_week' => 3], ['period_start' => 3, 'period_end' => 5, 'classroom_id' => $pcRoom->id]);
    }
}
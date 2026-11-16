<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Department;
use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class SchoolSeeder extends Seeder
{
    public function run(): void
    {
        // 今年度（今日を含む年度）。クラスと授業は年度に属するので、最初に作る
        $number = AcademicYear::yearOf(today());
        $year = AcademicYear::firstOrCreate(
            ['year' => $number],
            ['start_date' => "{$number}-04-01", 'end_date' => ($number + 1).'-03-31'],
        );

        // 学科（何年で卒業するかは学科ごとに違う）
        Department::firstOrCreate(['name' => '情報処理科'], ['years' => 2]);
        Department::firstOrCreate(['name' => '情報システム科'], ['years' => 4]);

        // 教室（クラス・授業より先に作る）
        $room301 = Classroom::firstOrCreate(['name' => '301教室'], ['floor' => 3]);
        $room302 = Classroom::firstOrCreate(['name' => '302教室'], ['floor' => 3]);
        $pcRoom = Classroom::firstOrCreate(['name' => 'PC室'], ['floor' => 4]);

        // クラス => ホームルームの教室
        $classes = [];
        foreach (['1T1' => $room301, '1T2' => $room302] as $name => $room) {
            $classes[] = SchoolClass::firstOrCreate(
                ['academic_year_id' => $year->id, 'name' => $name],
                ['grade' => 1, 'classroom_id' => $room->id],
            );
        }

        // 数学：月曜1限と木曜2限（週2回でも1つの授業）
        // クラスを選んだ授業の見本：1T1・1T2 が、それぞれのホームルームの教室で受ける
        $math = Subject::firstOrCreate(
            ['academic_year_id' => $year->id, 'name' => '数学'],
            ['grade' => 1, 'start_date' => "{$number}-09-01", 'end_date' => ($number + 1).'-01-31'],
        );
        $math->schoolClasses()->syncWithoutDetaching(array_map(fn ($class) => $class->id, $classes));
        foreach ([1 => 1, 4 => 2] as $day => $period) { // 曜日 => 時限
            $schedule = $math->schedules()->firstOrCreate(['day_of_week' => $day], ['period_start' => $period, 'period_end' => $period]);
            foreach ($classes as $class) {
                // クラスごとに1行（担当教師は未設定。授業の画面で選ぶ）
                $schedule->rooms()->firstOrCreate(['school_class_id' => $class->id], ['classroom_id' => $class->classroom_id]);
            }
        }

        // 情報実習：水曜3〜5限（期間は未登録 ＝ 年度の間ずっと）
        // クラスを選ばない授業の見本：教室（PC室）だけを決めてある
        $it = Subject::firstOrCreate(['academic_year_id' => $year->id, 'name' => '情報実習'], ['grade' => 1]);
        $it->schedules()
            ->firstOrCreate(['day_of_week' => 3], ['period_start' => 3, 'period_end' => 5])
            ->rooms()
            ->firstOrCreate(['school_class_id' => null, 'classroom_id' => $pcRoom->id]);
    }
}
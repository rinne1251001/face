<?php

namespace Database\Seeders;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Database\Seeder;

// 動作確認用の生徒（顔写真なし）。開発環境（APP_ENV=local）でだけ作る
// 手動での出席登録や、クラスの出席状況の画面を確かめるために使う
class DemoStudentSeeder extends Seeder
{
    public function run(): void
    {
        $classes = SchoolClass::orderBy('id')->get();
        $subjectIds = Subject::pluck('id');

        if ($classes->isEmpty() || $subjectIds->isEmpty()) {
            return;
        }

        $samples = [
            ['S0001', '見本 一郎', 0],
            ['S0002', '見本 花子', 0],
            ['S0003', '見本 次郎', 1],
        ];

        foreach ($samples as [$number, $name, $classIndex]) {
            $student = Student::updateOrCreate(
                ['student_number' => $number],
                ['name' => $name, 'school_class_id' => ($classes[$classIndex] ?? $classes[0])->id, 'is_active' => true],
            );
            $student->subjects()->syncWithoutDetaching($subjectIds);
        }
    }
}

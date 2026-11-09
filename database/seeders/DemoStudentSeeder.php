<?php

namespace Database\Seeders;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Database\Seeder;

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
            ['S0001', '見本 一郎', 'みほん いちろう', '1', 0],
            ['S0002', '見本 花子', 'みほん はなこ', '2', 0],
            ['S0003', '見本 次郎', 'みほん じろう', '3', 1],
        ];

        foreach ($samples as [$number, $name, $furigana, $attendNumber, $classIndex]) {
            $student = Student::updateOrCreate(
                ['student_number' => $number],
                [
                    'name' => $name,
                    'furigana' => $furigana,
                    'attend_number' => $attendNumber,
                    'school_class_id' => ($classes[$classIndex] ?? $classes[0])->id,
                    'is_active' => true,
                ],
            );
            $student->subjects()->syncWithoutDetaching($subjectIds);
        }
    }
}
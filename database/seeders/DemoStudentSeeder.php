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
            ['1250001', '見本一郎', 'みほんいちろう', '1', 0],
            ['1250002', '見本花子', 'みほんはなこ', '2', 0],
            ['1250003', '見本次郎', 'みほんじろう', '3', 1],
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
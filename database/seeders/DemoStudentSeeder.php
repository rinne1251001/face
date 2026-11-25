<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class DemoStudentSeeder extends Seeder
{
    public function run(): void
    {
        $year = AcademicYear::current();
        $department = Department::orderBy('id')->first();

        if ($year === null || $department === null) {
            return;
        }

        $classes = $year->schoolClasses()->orderBy('id')->get();
        $subjectIds = Subject::where('academic_year_id', $year->id)->pluck('id');

        // [学籍番号, 名前, フリガナ, 出席番号, クラス（何番目か。null なら未定）]
        $samples = [
            ['1250001', '見本一郎', 'みほんいちろう', '1', 0],
            ['1250002', '見本花子', 'みほんはなこ', '2', 0],
            ['1250003', '見本次郎', 'みほんじろう', '1', 1],
            ['1250004', '見本未定', 'みほんみてい', null, null], // クラスと出席番号がまだ決まっていない生徒
        ];

        foreach ($samples as [$number, $name, $furigana, $attendNumber, $classIndex]) {
            $student = Student::updateOrCreate(
                ['student_number' => $number],
                ['name' => $name, 'furigana' => $furigana, 'department_id' => $department->id, 'is_active' => true],
            );

            // 今年度の在籍（クラスと出席番号は、在籍のほうに持たせる）
            $student->enrollments()->updateOrCreate(
                ['academic_year_id' => $year->id],
                [
                    'grade' => 1,
                    'school_class_id' => $classIndex === null ? null : $classes->get($classIndex)?->id,
                    'attend_number' => $attendNumber,
                ],
            );

            $student->subjects()->syncWithoutDetaching($subjectIds);
        }
    }
}
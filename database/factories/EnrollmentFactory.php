<?php

namespace Database\Factories;

use App\Models\Enrollment;
use App\Models\SchoolClass;
use Illuminate\Database\Eloquent\Factories\Factory;

// 在籍（生徒 × 年度。その年度の学年・クラス・出席番号・年度末の結果）
/** @extends Factory<Enrollment> */
class EnrollmentFactory extends Factory
{
    protected $model = Enrollment::class;

    public function definition(): array
    {
        return [
            'student_id' => StudentFactory::new(),
            'academic_year_id' => AcademicYearFactory::new(),
            'grade' => 1,
            'school_class_id' => null, // クラス未定
            'attend_number' => null,   // 出席番号未定
            'result' => null,          // 年度末の結果（今年度はまだ null）
        ];
    }

    // クラスと出席番号を決める（年度と学年もクラスに合わせる）
    public function inClass(SchoolClass $class, ?string $attendNumber = null): static
    {
        return $this->state([
            'academic_year_id' => $class->academic_year_id,
            'grade' => $class->grade,
            'school_class_id' => $class->id,
            'attend_number' => $attendNumber,
        ]);
    }

    // 年度末の結果（promoted：進級 / repeated：留年 / graduated：卒業 / withdrawn：退学 / null：まだ決まっていない）
    public function result(?string $result): static
    {
        return $this->state(['result' => $result]);
    }
}
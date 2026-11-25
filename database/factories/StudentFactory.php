<?php

namespace Database\Factories;

use App\Models\Student;
use Database\Factories\Support\JapaneseName;
use Illuminate\Database\Eloquent\Factories\Factory;

// 生徒（クラス・出席番号・学年は年度ごとに違うので、EnrollmentFactory で作る）
/** @extends Factory<Student> */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        [$name, $furigana] = JapaneseName::make(); // 漢字と読みがなが合う組

        return [
            'name' => $name,
            'furigana' => $furigana,
            'student_number' => fake()->unique()->numerify('9######'),
            'department_id' => DepartmentFactory::new(),
            'is_active' => true,
        ];
    }

    // 卒業・退学した生徒（照合の対象から外れる）
    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
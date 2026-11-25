<?php

namespace Database\Factories;

use App\Models\SchoolClass;
use Illuminate\Database\Eloquent\Factories\Factory;

// クラス（年度ごとに別の行。「2T1」なら2年生）
/** @extends Factory<SchoolClass> */
class SchoolClassFactory extends Factory
{
    protected $model = SchoolClass::class;

    public function definition(): array
    {
        return [
            'academic_year_id' => AcademicYearFactory::new(), // 指定しなければ年度も作る
            'name' => fake()->unique()->numerify('#T#'),
            // 名前の最初の数字を学年にする（「2T1」→ 2）
            'grade' => fn (array $attributes) => preg_match('/^(\d)/', $attributes['name'], $m) ? max(1, (int) $m[1]) : 1,
            'classroom_id' => null,
        ];
    }
}
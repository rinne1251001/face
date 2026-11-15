<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\Factory;

// 年度（4月1日〜翌年3月31日）
/** @extends Factory<AcademicYear> */
class AcademicYearFactory extends Factory
{
    protected $model = AcademicYear::class;

    public function definition(): array
    {
        return [
            'year' => fake()->unique()->numberBetween(2000, 2099),
            // 開始日・終了日は、上の year から決める（fn の $attributes に year が入ってくる）
            'start_date' => fn (array $attributes) => "{$attributes['year']}-04-01",
            'end_date' => fn (array $attributes) => ($attributes['year'] + 1).'-03-31',
        ];
    }

    // 年度を指定する（例：AcademicYearFactory::new()->forYear(2026)->create()）
    public function forYear(int $year): static
    {
        return $this->state(['year' => $year]);
    }
}
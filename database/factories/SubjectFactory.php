<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

// 授業（年度ごとに別の行。期間が空なら「年度の間ずっと」）
/** @extends Factory<Subject> */
class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    public function definition(): array
    {
        return [
            'academic_year_id' => AcademicYearFactory::new(),
            'name' => fake()->randomElement(['プログラミング基礎', 'ネットワーク基礎', 'データベース基礎', 'ビジネスマナー', '情報処理演習']),
            'grade' => 1,
            'start_date' => null,
            'end_date' => null,
        ];
    }

    // 前期だけ（4月1日〜9月30日）
    // fn にしておくと、年度を作ったあと（academic_year_id が決まったあと）に日付を決められる
    public function firstHalf(): static
    {
        return $this->state([
            'start_date' => fn (array $attributes) => $this->yearOf($attributes).'-04-01',
            'end_date' => fn (array $attributes) => $this->yearOf($attributes).'-09-30',
        ]);
    }

    // 後期だけ（10月1日〜3月31日）
    public function secondHalf(): static
    {
        return $this->state([
            'start_date' => fn (array $attributes) => $this->yearOf($attributes).'-10-01',
            'end_date' => fn (array $attributes) => ($this->yearOf($attributes) + 1).'-03-31',
        ]);
    }

    // 授業の年度（2026年度なら 2026）
    /** @param array<string, mixed> $attributes */
    private function yearOf(array $attributes): int
    {
        return AcademicYear::findOrFail((int) $attributes['academic_year_id'])->year;
    }
}
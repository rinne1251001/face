<?php

namespace Database\Factories;

use App\Models\MatchLog;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

// 顔の照合ログ（照合するたびに1件。created_at だけ使う）
/** @extends Factory<MatchLog> */
class MatchLogFactory extends Factory
{
    protected $model = MatchLog::class;

    public function definition(): array
    {
        return [
            'student_id' => StudentFactory::new(),
            // 本人と一致したときの値（しきい値 0.75・差 0.05 より十分大きい）
            'similarity' => fake()->randomFloat(3, 0.78, 0.97),
            'margin' => fake()->randomFloat(3, 0.06, 0.35),
            'accepted' => true,
            'model_version' => config('face.model_version'),
            'teacher_id' => null, // 管理者の照合テストなら null
        ];
    }

    // 一致しなかった照合
    public function rejected(): static
    {
        return $this->state([
            'similarity' => fake()->randomFloat(3, 0.40, 0.74),
            'margin' => fake()->randomFloat(3, 0.00, 0.04),
            'accepted' => false,
        ]);
    }

    // 照合した時刻
    public function at(CarbonInterface $time): static
    {
        return $this->state(['created_at' => $time->format('Y-m-d H:i:s')]);
    }
}
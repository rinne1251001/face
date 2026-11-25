<?php

namespace Database\Factories;

use App\Models\Classroom;
use Illuminate\Database\Eloquent\Factories\Factory;

// 教室（「501教室」なら5階）
/** @extends Factory<Classroom> */
class ClassroomFactory extends Factory
{
    protected $model = Classroom::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->numerify('%0#教室'),
            // 名前の最初の数字を階にする（「501教室」→ 5）。数字で始まらない名前なら1階
            'floor' => fn (array $attributes) => preg_match('/^(\d)\d{2}/', $attributes['name'], $m) ? (int) $m[1] : 1,
        ];
    }
}
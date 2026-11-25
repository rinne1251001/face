<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

// 学科（years … 何年で卒業するか）
/** @extends Factory<Department> */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->numerify('テスト学科###'),
            'years' => fake()->numberBetween(1, 4),
        ];
    }
}
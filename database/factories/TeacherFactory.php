<?php

namespace Database\Factories;

use App\Models\Teacher;
use Database\Factories\Support\JapaneseName;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

// 教師（パスワードはすべて 12345678。テスト用）
/** @extends Factory<Teacher> */
class TeacherFactory extends Factory
{
    protected $model = Teacher::class;

    // ハッシュ化は時間がかかるので、1回だけ計算して使い回す
    private static ?string $password = null;

    public function definition(): array
    {
        [$name] = JapaneseName::make(' '); // 教師の名前は「山田 太郎」のように姓と名の間に空白を入れる

        return [
            'name' => $name,
            'login_id' => fake()->unique()->numerify('t###'),
            'password' => self::$password ??= Hash::make('12345678'),
            'can_login' => true,
        ];
    }

    // ログインできない教師（退職した教師など）
    public function cannotLogin(): static
    {
        return $this->state(['can_login' => false]);
    }
}
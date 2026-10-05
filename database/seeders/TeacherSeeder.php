<?php

namespace Database\Seeders;

use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Database\Seeder;

// 動作確認用の教師（SchoolSeeder の後に実行する）
class TeacherSeeder extends Seeder
{
    public function run(): void
    {
        // 初回ログイン後、必ずパスワードを変更すること
        $teacher = Teacher::updateOrCreate(
            ['login_id' => env('TEACHER_LOGIN_ID', 'teacher')],
            ['name' => 'テスト教師', 'password' => env('TEACHER_PASSWORD', 'change-me-please')],
        );

        // 教師は担当授業が1つ以上必要なので、見本の授業をすべて担当にする
        $teacher->subjects()->syncWithoutDetaching(Subject::pluck('id'));

        // 受け持ちクラス（なくてもよいが、画面の確認のため1つ設定する）
        $teacher->schoolClasses()->syncWithoutDetaching(SchoolClass::orderBy('id')->limit(1)->pluck('id'));
    }
}

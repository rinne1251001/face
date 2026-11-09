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
            ['name' => 'テスト教師', 'password' => env('TEACHER_PASSWORD', '12345678')],
        );

        // 画面の確認のため、見本の授業をすべて担当にする（担当授業は0個でもよい）
        $teacher->subjects()->syncWithoutDetaching(Subject::pluck('id'));

        // 受け持ちクラス（なくてもよいが、画面の確認のため1つ設定する）
        $teacher->schoolClasses()->syncWithoutDetaching(SchoolClass::orderBy('id')->limit(1)->pluck('id'));

        // ここから下の見本の教師は、開発環境でだけ作る
        // （本番に「だれでも知っているパスワード」の教師ができないようにするため）
        if (! app()->environment('local')) {
            return;
        }

        $demoTeachers = [
            [
                'name' => '山田 太郎',
                'login_id' => 'yamada',
                'subjects' => ['数学'],             // 担当授業（授業名で指定）
                'classes' => ['1T1'],            // 受け持ちクラス（クラス名で指定）
            ],
            [
                'name' => '佐藤 花子',
                'login_id' => 'sato',
                'subjects' => ['数学', '情報実習'], // 担当授業が2つ
                'classes' => ['1T2'],
            ],
            [
                'name' => '鈴木 一郎',
                'login_id' => 'suzuki',
                'subjects' => [],                   // 担当授業なし（0個でも登録できることの確認用）
                'classes' => [],
            ],
        ];

        foreach ($demoTeachers as $demo) {
            // login_id で探して、なければ作る（何度シーダーを実行しても同じ人が増えない）
            $demoTeacher = Teacher::updateOrCreate(
                ['login_id' => $demo['login_id']],
                ['name' => $demo['name'], 'password' => '12345678'], // パスワードは自動でハッシュ化される
            );

            // 名前から ID を探して設定する（syncWithoutDetaching：画面で追加した担当は消さない）
            $demoTeacher->subjects()->syncWithoutDetaching(Subject::whereIn('name', $demo['subjects'])->pluck('id'));
            $demoTeacher->schoolClasses()->syncWithoutDetaching(SchoolClass::whereIn('name', $demo['classes'])->pluck('id'));
        }
    }
}
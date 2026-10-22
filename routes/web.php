<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Teacher;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| 実験用
|--------------------------------------------------------------------------
*/
// http://127.0.0.1:8000/tests
Route::view('/test', 'tests/test');
Route::view('/toduka', 'tests/toduka');
Route::view('/design1', 'tests/design1');
Route::view('/design2', 'tests/design2');
Route::view('/design3', 'tests/design3');
Route::view('/design4', 'tests/design4');
Route::view('/design5', 'tests/design5');
Route::view('/design6', 'tests/design6');
Route::view('/aaabbbccc', 'tests/aaa');

// ドボンゲームのページ
Route::get('/dobon', function () {
    return view('tests.dobon');
});

// トップページは教師のホームへ（ログインしていなければ教師のログイン画面へ）
Route::redirect('/', '/teacher');

/*
|--------------------------------------------------------------------------
| 管理者（/admin/...）
|--------------------------------------------------------------------------
| ルート名はすべて admin. で始まる（例：admin.students.index）
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('login', [Admin\LoginController::class, 'show'])->name('login');
        Route::post('login', [Admin\LoginController::class, 'login'])->middleware('throttle:5,1');
    });

    Route::middleware('auth:admin')->group(function () {
        Route::redirect('/', '/admin/students');
        Route::post('logout', [Admin\LoginController::class, 'logout'])->name('logout');

        // 生徒
        Route::get('students/bulk', [Admin\StudentController::class, 'bulkCreate'])->name('students.bulk.create');
        Route::post('students/bulk', [Admin\StudentController::class, 'bulkStore'])->name('students.bulk.store');
        Route::resource('students', Admin\StudentController::class)->except('show');

        // 顔写真
        Route::get('students/{student}/faces', [Admin\FaceSampleController::class, 'create'])->name('faces.create');
        Route::post('students/{student}/faces', [Admin\FaceSampleController::class, 'store'])->name('faces.store');
        Route::get('face-samples/{faceSample}/image', [Admin\FaceSampleController::class, 'image'])->name('faces.image');
        Route::delete('face-samples/{faceSample}', [Admin\FaceSampleController::class, 'destroy'])->name('faces.destroy');

        // クラス・授業・教師
        Route::resource('classes', Admin\SchoolClassController::class)
            ->except('show')
            ->parameters(['classes' => 'schoolClass']);
        Route::resource('subjects', Admin\SubjectController::class)->except('show');
        Route::resource('teachers', Admin\TeacherController::class)->except('show');

        // 照合テスト（しきい値の調整用）
        Route::get('match', [Admin\FaceMatchController::class, 'show'])->name('match.show');
        Route::post('match', [Admin\FaceMatchController::class, 'match'])->name('match.run')->middleware('throttle:60,1');
        Route::get('match/logs', [Admin\FaceMatchController::class, 'logs'])->name('match.logs');      // 照合ログの表の中身
        Route::post('match/log', [Admin\FaceMatchController::class, 'toggleLog'])->name('match.log');  // ログを取る／取らないの切り替え
    });
});

/*
|--------------------------------------------------------------------------
| 教師（/teacher/...）
|--------------------------------------------------------------------------
| ルート名はすべて teacher. で始まる（例：teacher.dashboard）
*/
Route::prefix('teacher')->name('teacher.')->group(function () {
    Route::middleware('guest:teacher')->group(function () {
        Route::get('login', [Teacher\LoginController::class, 'show'])->name('login');
        Route::post('login', [Teacher\LoginController::class, 'login'])->middleware('throttle:5,1');
    });

    Route::middleware('auth:teacher')->group(function () {
        Route::get('/', [Teacher\DashboardController::class, 'index'])->name('dashboard');
        Route::post('logout', [Teacher\LoginController::class, 'logout'])->name('logout');

        // 担当授業の出席一覧と手動登録
        Route::get('subjects/{subject}', [Teacher\AttendanceController::class, 'show'])->name('subjects.show');
        Route::put('subjects/{subject}/students/{student}/attendance', [Teacher\AttendanceController::class, 'update'])
            ->name('attendances.update');

        // 顔認証での出席受付
        Route::get('subjects/{subject}/checkin', [Teacher\CheckinController::class, 'show'])->name('checkin.show');
        Route::post('subjects/{subject}/checkin', [Teacher\CheckinController::class, 'store'])
            ->name('checkin.store')
            ->middleware('throttle:120,1');

        // 受け持ちクラスの出席状況
        Route::get('classes/{schoolClass}', [Teacher\ClassController::class, 'show'])->name('classes.show');

        // 設定（パスワードの変更）
        Route::get('settings', [Teacher\SettingController::class, 'index'])->name('settings.index');
        Route::get('settings/password', [Teacher\SettingController::class, 'editPassword'])->name('settings.password.edit');
        Route::put('settings/password', [Teacher\SettingController::class, 'updatePassword'])
            ->name('settings.password.update')
            ->middleware('throttle:5,1'); // 今のパスワードを総当たりで試されないように、1分に5回まで
    });
});
<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Teacher;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| 実験用
|--------------------------------------------------------------------------
*/
// http://127.0.0.1:8000/admin/tests/experiment
if (app()->environment('local')) {
    require __DIR__.'/experiment.php';
}
// http://127.0.0.1:8000/tests
Route::view('/test', 'tests/test');

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
    });
});
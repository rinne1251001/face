<?php

use App\Models\MatchLog;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 照合の実験用ページ（開発中だけ使う）
|--------------------------------------------------------------------------
| 削除するとき：このファイル、resources/views/testsフォルダ、routes/web.phpの実験ルート を消す
*/
Route::prefix('admin/tests')->name('tests.')->middleware('auth:admin')->group(function () {
    // 実験ページ
    Route::view('experiment', 'tests.experiment')->name('experiment');

    // 照合ログの表の中身（新しい順に30件）
    Route::get('experiment/logs', function () {
        $logs = MatchLog::query()
            ->leftJoin('students', 'students.id', '=', 'match_logs.student_id')
            ->orderByDesc('match_logs.id')
            ->limit(30)
            ->get([
                'match_logs.id',
                'students.name as student_name',
                'match_logs.similarity',
                'match_logs.margin',
                'match_logs.accepted',
                'match_logs.created_at',
            ]);

        return view('tests.experiment-logs', compact('logs'));
    })->name('experiment.logs');
});
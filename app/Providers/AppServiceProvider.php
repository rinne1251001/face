<?php

namespace App\Providers;

use App\Services\SchoolCalendar;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // 1回のリクエストの中では同じ SchoolCalendar を使う（同じ日付を何度も調べないように）
        $this->app->scoped(SchoolCalendar::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 回数制限（ルートの ->middleware('throttle:名前') で使う）
        // 名前ごとに別々に数えるので、ほかの画面の回数や、ほかの人のログインの回数とは混ざらない

        // ログイン：「ログインID ＋ パソコン（IP）」ごとに1分に5回まで（管理者・教師で共通）
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)
            ->by(mb_strtolower((string) $request->input('login_id')).'|'.$request->ip()));

        // パスワード変更：教師ごとに1分に5回まで（今のパスワードを総当たりで試されないように）
        RateLimiter::for('password', fn (Request $request) => Limit::perMinute(5)
            ->by('teacher:'.$request->user('teacher')?->getAuthIdentifier()));

        // 顔認証の受付：教師ごとに1分に120回まで
        RateLimiter::for('checkin', fn (Request $request) => Limit::perMinute(120)
            ->by('teacher:'.$request->user('teacher')?->getAuthIdentifier()));

        // 照合テスト：管理者ごとに1分に60回まで
        RateLimiter::for('face-match', fn (Request $request) => Limit::perMinute(60)
            ->by('admin:'.$request->user('admin')?->getAuthIdentifier()));
    }
}
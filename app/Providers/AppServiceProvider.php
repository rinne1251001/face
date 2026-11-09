<?php

namespace App\Providers;

use App\Services\SchoolCalendar;
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
        //
    }
}

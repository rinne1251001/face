<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

// URL が /admin で始まるかどうか（管理者の画面か、教師の画面か）
$isAdminArea = fn (Request $request) => $request->is('admin', 'admin/*');

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) use ($isAdminArea): void {
        $middleware->redirectTo(
            // ログインしていない人が auth: のついたページを開いたときの行き先
            guests: fn (Request $request) => $isAdminArea($request)
                ? route('admin.login')
                : route('teacher.login'),
            // ログイン済みの人がログイン画面（guest: のついたページ）を開いたときの行き先
            users: fn (Request $request) => $isAdminArea($request)
                ? route('admin.students.index')
                : route('teacher.dashboard'),
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

// ログイン中の教師が「ログインできない」設定になったら、その場でログアウトさせる
// （ログインの画面では止めているが、すでにログインしている人や「ログインしたままにする」の人のため）
class EnsureTeacherCanLogin
{
    public function handle(Request $request, Closure $next): Response
    {
        $teacher = $request->user('teacher');

        if ($teacher !== null && ! $teacher->can_login) {
            // 教師のログインだけを終わらせる（同じブラウザで管理者としてログインしていても、そちらは消さない）
            Auth::guard('teacher')->logout();
            $request->session()->regenerateToken();

            return redirect()->route('teacher.login')
                ->withErrors(['login_id' => 'このアカウントは、ログインできない設定になりました。管理者に確認してください。']);
        }

        return $next($request);
    }
}
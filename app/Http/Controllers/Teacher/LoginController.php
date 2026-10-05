<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

// 教師のログイン・ログアウト
class LoginController extends Controller
{
    public function show(): View
    {
        return view('auth.login', [
            'title' => '教師ログイン',
            'action' => route('teacher.login'),
            'otherLink' => ['label' => '管理者の方はこちら', 'url' => route('admin.login')],
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login_id' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('teacher')->attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['login_id' => 'ログインIDまたはパスワードが違います。'])
                ->onlyInput('login_id');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('teacher.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('teacher')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('teacher.login');
    }
}

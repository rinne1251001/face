<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

// 管理者のログイン・ログアウト
class LoginController extends Controller
{
    public function show(): View
    {
        return view('auth.login', [
            'title' => '管理者ログイン',
            'action' => route('admin.login'),
            'otherLink' => ['label' => '教師の方はこちら', 'url' => route('teacher.login')],
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login_id' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['login_id' => 'ログインIDまたはパスワードが違います。'])
                ->onlyInput('login_id');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.students.index'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}

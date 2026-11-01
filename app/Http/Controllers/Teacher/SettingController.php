<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// 教師の設定
class SettingController extends Controller
{
    // 設定一覧画面
    public function index(): View
    {
        return view('teacher.settings.index');
    }

    // パスワード変更の画面
    public function editPassword(): View
    {
        return view('teacher.settings.password');
    }

    // パスワードを変更する処理
    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            // current_password:teacher … ログイン中の教師の、今のパスワードと一致するか
            'current_password' => ['required', 'string', 'current_password:teacher'],
            // confirmed … password_confirmation（確認用）と同じか
            // different … 今のパスワードと同じものは使えない
            'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed', 'different:current_password'],
        ], [
            'current_password.current_password' => '現在のパスワードが正しくありません。',
        ], [
            'password' => '新しいパスワード',
        ]);

        // Teacher モデルの casts で 'password' => 'hashed' にしているので、自動でハッシュ化して保存される
        $request->user('teacher')->update(['password' => $data['password']]);

        // 安全のため、セッションIDを作り直す（ログインはそのまま）
        $request->session()->regenerate();

        return redirect()->route('teacher.settings.index')->with('status', 'パスワードを変更しました。');
    }
}
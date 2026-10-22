<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// 教師の設定
class SettingController extends Controller
{
    // 設定一覧の画面
    public function index(): View
    {
        return view('teacher.settings.index');
    }

    // パスワード変更の画面
    public function editPassword(): View
    {
        //
    }

    // パスワードを変更する処理
    public function updatePassword(Request $request): RedirectResponse
    {
        //
    }
}

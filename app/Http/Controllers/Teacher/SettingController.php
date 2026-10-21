<?php

namespace App\Http\Controllers\Teacher;

use Illuminate\Http\Request;

// 教師の設定
class SettingController extends Controller
{
    // 設定一覧
    public function index(): View
    {
        return view('teacher.settings.index');
    }
}

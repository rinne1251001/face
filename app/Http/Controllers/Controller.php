<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

abstract class Controller
{
    // ログインしたあとの行き先を決める（管理者・教師のログインで共通）
    // ログイン前に開こうとしていたページ（url.intended）が $area の画面なら、そこへ戻す
    // ほかの画面だったとき（例：教師の画面を開こうとしてから、管理者でログインした）は $default へ
    protected function redirectAfterLogin(Request $request, string $area, string $default): RedirectResponse
    {
        $intended = (string) $request->session()->pull('url.intended'); // 取り出して、セッションからは消す
        $areaUrl = url($area); // 例：http://127.0.0.1:8000/admin

        // 「/admin」「/admin/...」「/admin?...」のときだけ戻す（「/administrator」などは対象外）
        if ($intended === $areaUrl || Str::startsWith($intended, [$areaUrl.'/', $areaUrl.'?'])) {
            return redirect()->to($intended);
        }

        return redirect()->to($default);
    }
}
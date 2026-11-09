<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AnalysisController extends Controller
{
    // 一覧画面
    public function index(): View
    {
        return view('teacher.analyses.index');
    }
}
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MatchLog;
use App\Services\FaceMatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// 照合テスト（管理者がしきい値を調整するための画面。出席は記録しない）
class FaceMatchController extends Controller
{
    public function show(): View
    {
        return view('admin.faces.match');
    }

    public function match(Request $request, FaceMatcher $matcher): JsonResponse
    {
        $request->validate([
            'model_version' => ['required', 'string', Rule::in([config('face.model_version')])],
            'embedding' => ['required', 'array'],
        ], ['model_version.in' => 'モデルのバージョンがサーバー設定と一致しません。']);

        $query = FaceMatcher::toUnitVector($request->input('embedding'));
        $result = $matcher->match($query, $request->input('model_version'));

        // 照合の記録（しきい値の調整に使う）。管理者のテストなので teacher_id は入れない
        MatchLog::create([
            'student_id' => $result['best_student_id'],
            'similarity' => $result['similarity'],
            'margin' => $result['margin'],
            'accepted' => $result['accepted'],
            'model_version' => $request->input('model_version'),
            'teacher_id' => null,
        ]);

        return response()->json([
            'accepted' => $result['accepted'],
            'student' => FaceMatcher::summary($result['student']),
            'similarity' => $result['similarity'],
            'margin' => $result['margin'],
            'threshold' => config('face.threshold'),
            'candidates' => array_map(fn ($c) => [
                'student' => FaceMatcher::summary($c['student']),
                'similarity' => $c['similarity'],
            ], $result['candidates']),
        ]);
    }
}

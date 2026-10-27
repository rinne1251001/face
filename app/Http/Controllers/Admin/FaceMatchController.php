<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MatchLog;
use App\Services\FaceMatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// 照合テスト（管理者がしきい値を調整するための画面。出席は記録しない）
class FaceMatchController extends Controller
{
    public function show(Request $request): View
    {
        return view('admin.faces.match', [
            'logEnabled' => $request->session()->get('match_log_enabled', true), // 最初は「ログを取る」
        ]);
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
        // 画面で「ログを取らない」にしているときは記録しない
        if ($request->session()->get('match_log_enabled', true)) {
            MatchLog::create([
                'student_id' => $result['best_student_id'],
                'similarity' => $result['similarity'],
                'margin' => $result['margin'],
                'accepted' => $result['accepted'],
                'model_version' => $request->input('model_version'),
                'teacher_id' => null,
            ]);
        }

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

    // 照合ログの表の中身（新しい順に30件）。画面の JavaScript から読み込む
    public function logs(): View
    {
        $logs = MatchLog::query()
            ->leftJoin('students', 'students.id', '=', 'match_logs.student_id')
            ->orderByDesc('match_logs.id')
            ->limit(30)
            ->get([
                'match_logs.id',
                'students.name as student_name',
                'match_logs.similarity',
                'match_logs.margin',
                'match_logs.accepted',
                'match_logs.created_at',
            ]);

        return view('admin.faces.match-logs', compact('logs'));
    }

    // 「ログを取る／取らない」を切り替える（ログインしている間だけ覚えておく）
    public function toggleLog(Request $request): RedirectResponse
    {
        $enabled = ! $request->session()->get('match_log_enabled', true);
        $request->session()->put('match_log_enabled', $enabled);

        return redirect()->route('admin.match.show')
            ->with('status', $enabled ? '照合ログを取るようにしました。' : '照合ログを取らないようにしました。');
    }
}
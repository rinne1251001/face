<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\MatchLog;
use App\Models\Subject;
use App\Services\FaceMatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// 顔認証での出席受付（記録するのは今日の日付だけ）
class CheckinController extends Controller
{
    // カメラの画面
    public function show(Request $request, Subject $subject): View
    {
        abort_unless($request->user('teacher')->teachesSubject($subject), 403, 'この授業の担当ではありません。');

        $today = today();

        return view('teacher.subjects.checkin', [
            'subject' => $subject,
            'today' => $today,
            'wrongDay' => $today->dayOfWeekIso !== $subject->day_of_week,
            'studentCount' => $subject->students()->count(),
            'recordedCount' => $subject->attendances()->where('date', $today->toDateString())->count(),
        ]);
    }

    // カメラの画面から特徴量を受け取り、照合して出席を記録する
    public function store(Request $request, Subject $subject, FaceMatcher $matcher): JsonResponse
    {
        $teacher = $request->user('teacher');
        abort_unless($teacher->teachesSubject($subject), 403, 'この授業の担当ではありません。');

        $request->validate([
            'model_version' => ['required', 'string', Rule::in([config('face.model_version')])],
            'embedding' => ['required', 'array'],
        ], ['model_version.in' => 'モデルのバージョンがサーバー設定と一致しません。']);

        // 照合は登録済みの全生徒を対象にする。
        // （受講者だけに絞ると、受講していない生徒が受講者のそっくりさんと間違えられることがあるため、
        //   まず「誰か」を特定してから、受講者かどうかを確認する）
        $query = FaceMatcher::toUnitVector($request->input('embedding'));
        $result = $matcher->match($query, $request->input('model_version'));

        $log = MatchLog::create([
            'student_id' => $result['best_student_id'],
            'similarity' => $result['similarity'],
            'margin' => $result['margin'],
            'accepted' => $result['accepted'],
            'model_version' => $request->input('model_version'),
            'teacher_id' => $teacher->id,
        ]);

        // 1. 登録済みの生徒と一致しなかった
        if (! $result['accepted'] || ! $result['student']) {
            return response()->json([
                'result' => 'unknown',
                'message' => '登録されている生徒と一致しませんでした。',
            ]);
        }

        $student = $result['student'];
        $summary = FaceMatcher::summary($student);

        // 2. 生徒は特定できたが、この授業の受講者ではない
        if (! $subject->students()->whereKey($student->id)->exists()) {
            return response()->json([
                'result' => 'not_enrolled',
                'student' => $summary,
                'message' => "{$student->name} さんはこの授業の受講者ではありません。",
            ]);
        }

        // 3. 出席を記録する。今日すでに記録があれば、上書きせずにそのまま返す
        //    （createOrFirst は、同時に2回送られて二重登録になりそうなときも1件にしてくれる）
        $attendance = Attendance::createOrFirst(
            ['student_id' => $student->id, 'subject_id' => $subject->id, 'date' => today()->toDateString()],
            ['status' => 'present', 'method' => 'face', 'match_log_id' => $log->id],
        );

        if (! $attendance->wasRecentlyCreated) {
            return response()->json([
                'result' => 'already',
                'student' => $summary,
                'status_label' => $attendance->statusLabel(),
                'message' => "{$student->name} さんは記録済みです（{$attendance->statusLabel()}）。",
            ]);
        }

        return response()->json([
            'result' => 'recorded',
            'student' => $summary,
            'status_label' => $attendance->statusLabel(),
            'message' => "{$student->name} さんの出席を記録しました。",
            'recorded_count' => $subject->attendances()->where('date', today()->toDateString())->count(),
        ]);
    }
}

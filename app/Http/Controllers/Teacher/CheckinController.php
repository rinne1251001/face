<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\MatchLog;
use App\Models\Student;
use App\Models\StudentCard;
use App\Models\Subject;
use App\Services\CheckinWindow;
use App\Services\FaceMatcher;
use App\Services\SchoolCalendar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// 出席受付（記録するのは今日の日付だけ）
// 流れ：入校証をかざす（card）→ 画面に持ち主の名前を出す → 顔認証で本人か確かめて記録する（store）
// 入校証を使わない受付（顔認証だけ）のときは、store だけが呼ばれる
class CheckinController extends Controller
{
    // IDm のバリデーションで出すメッセージ
    private const IDM_MESSAGES = [
        'idm.required' => '入校証を読み取れませんでした。もう一度かざしてください。',
        'idm.regex' => '入校証を正しく読み取れませんでした。もう一度かざしてください。',
    ];

    // 受付の画面（カメラと、入校証リーダー）
    public function show(Request $request, Subject $subject, SchoolCalendar $calendar): View
    {
        abort_unless($request->user('teacher')->teachesSubject($subject), 403, 'この授業の担当ではありません。');

        $today = today();
        $period = $subject->periodOrFirst($request->query('period'), $today); // ?period=数字 で何限の受付かを指定
        // この時限にこの授業を受けるクラス（クラスごとの授業変更を反映。今日この授業がなければ空）
        $classIds = $subject->periodsOn($today) !== [] ? $subject->classIdsOn($today, $period) : [];

        return view('teacher.subjects.checkin', [
            'subject' => $subject,
            'today' => $today,
            'period' => $period, // ← これがないと受付画面が「Undefined variable $period」になる
            'window' => CheckinWindow::for($today, $period), // 受付時間（出席・遅刻になる時刻）
            'periods' => $subject->periodsFor($today), // 切り替えられる時限
            'wrongDay' => $subject->periodsOn($today) === [], // 今日はこの授業がない（祝日・休校・曜日違い）
            'dayNote' => $calendar->dayNote($today),            // 例：祝日（文化の日）
            // 受講者の人数（クラスの授業変更で、この時限に受けないクラスの生徒は数えない。クラスが未定の生徒は数える）
            'studentCount' => $classIds !== []
                ? $subject->students()
                    ->whereHas('enrollments', fn ($e) => $e
                        ->where('academic_year_id', $subject->academic_year_id)
                        ->where(fn ($e) => $e->whereIn('school_class_id', $classIds)->orWhereNull('school_class_id')))
                    ->count()
                : $subject->students()->count(),
            'recordedCount' => $subject->attendances()
                ->where('date', $today->toDateString())
                ->where('period', $period)
                ->count(),
        ]);
    }

    // 入校証がかざされたとき：持ち主を調べて、顔認証に進めるかを返す（出席はまだ記録しない）
    public function card(Request $request, Subject $subject): JsonResponse
    {
        $teacher = $request->user('teacher');
        abort_unless($teacher->teachesSubject($subject), 403, 'この授業の担当ではありません。');

        $validated = $request->validate([
            'idm' => ['required', 'string', 'regex:'.StudentCard::IDM_PATTERN],
            // 何限の出席か（受付画面の URL の ?period= で送られてくる）
            'period' => ['required', 'integer', Rule::in($subject->periodsFor(today()))],
        ], self::IDM_MESSAGES);

        $period = $request->integer('period');

        // 0. 受付時間か（受付時間の外は、顔認証に進ませない）
        $window = CheckinWindow::for(today(), $period);
        if ($window->statusAt(now()) === null) {
            return response()->json(['result' => 'closed', 'message' => $this->closedMessage($window, $period)]);
        }

        // 1. 登録されている入校証か
        //    （はじめて読み取った入校証を、ここで登録することはしない。登録は管理者の「入校証の照合・登録」でする）
        $card = StudentCard::with('student.currentEnrollment.schoolClass')
            ->where('idm', StudentCard::normalizeIdm($validated['idm']))
            ->first();
        $student = $card?->student;

        if ($card === null || $student === null) {
            return response()->json([
                'result' => 'unknown_card',
                'message' => '登録されていない入校証です。管理者に登録を依頼してください。',
            ]);
        }

        // タッチの記録を残す（だれの入校証が、いつかざされたか）
        $card->reads()->create(['student_id' => $student->id]);

        if (! $card->is_active || ! $student->is_active) {
            return response()->json([
                'result' => 'card_inactive',
                'message' => $card->is_active ? "{$student->name} さんは停止中のため、受け付けできません。" : '使用停止中の入校証です。',
            ]);
        }

        $summary = FaceMatcher::summary($student);

        // 2. この授業（この時限）の受講者か
        $notEnrolled = $this->notEnrolledMessage($subject, $student, $period);
        if ($notEnrolled !== null) {
            return response()->json(['result' => 'not_enrolled', 'student' => $summary, 'message' => $notEnrolled]);
        }

        // 3. 今日のこの時限にすでに記録があれば、顔認証はしない
        $attendance = Attendance::query()
            ->where('student_id', $student->id)
            ->where('subject_id', $subject->id)
            ->where('date', today()->toDateString())
            ->where('period', $period)
            ->first();
        if ($attendance !== null) {
            return response()->json([
                'result' => 'already',
                'student' => $summary,
                'status_label' => $attendance->statusLabel(),
                'message' => "{$student->name} さんは{$period}限の記録済みです（{$attendance->statusLabel()}）。",
            ]);
        }

        // 4. 顔認証へ進む
        return response()->json([
            'result' => 'ready',
            'student' => $summary,
            'message' => "{$student->name} さんの入校証です。カメラの正面に立ってください。",
        ]);
    }

    // カメラの画面から特徴量を受け取り、照合して出席を記録する
    // 入校証をかざしたあとの顔認証では、その入校証の IDm も一緒に送られてくる（顔が入校証の持ち主と同じときだけ記録する）
    public function store(Request $request, Subject $subject, FaceMatcher $matcher): JsonResponse
    {
        $teacher = $request->user('teacher');
        abort_unless($teacher->teachesSubject($subject), 403, 'この授業の担当ではありません。');

        $request->validate([
            'model_version' => ['required', 'string', Rule::in([config('face.model_version')])],
            'embedding' => ['required', 'array'],
            // 何限の出席か（受付画面の URL の ?period= で送られてくる）
            'period' => ['required', 'integer', Rule::in($subject->periodsFor(today()))],
            // かざした入校証の IDm（入校証を使わない受付のときは送られてこない）
            'idm' => ['nullable', 'string', 'regex:'.StudentCard::IDM_PATTERN],
        ], ['model_version.in' => 'モデルのバージョンがサーバー設定と一致しません。'] + self::IDM_MESSAGES);

        $period = $request->integer('period');

        // 0. 受付時間か（開始30分前〜開始時刻は出席、開始時刻を過ぎたら終了時刻まで遅刻。それ以外は受け付けない）
        //    受付時間の外の出欠は、教師が出席一覧から手動で登録する
        $window = CheckinWindow::for(today(), $period);
        $status = $window->statusAt(now());
        if ($status === null) {
            return response()->json(['result' => 'closed', 'message' => $this->closedMessage($window, $period)]);
        }

        // 入校証の持ち主（入校証を使わない受付のときは null のまま）
        // かざしたときに card() で確かめているが、顔認証までの間に変わっているかもしれないので、もう一度確かめる
        $cardStudent = null;
        if ($request->filled('idm')) {
            $cardStudent = StudentCard::usable()
                ->where('idm', StudentCard::normalizeIdm($request->input('idm')))
                ->first()
                ?->student;
            if ($cardStudent === null || ! $cardStudent->is_active) {
                return response()->json([
                    'result' => 'unknown_card',
                    'message' => '登録されていない（または使えない）入校証です。もう一度かざしてください。',
                ]);
            }
        }

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

        $student = $result['accepted'] ? $result['student'] : null;

        // 1. 入校証をかざしたとき：顔が、入校証の持ち主と同じ生徒か
        //    （顔が誰とも一致しなかったときも、別の生徒と一致したときも、同じ返事にする。別の生徒の名前は出さない）
        if ($cardStudent !== null && $student?->id !== $cardStudent->id) {
            return response()->json([
                'result' => 'mismatch',
                'student' => FaceMatcher::summary($cardStudent),
                'message' => "入校証の持ち主（{$cardStudent->name} さん）の顔と一致しませんでした。",
            ]);
        }

        // 1-2. 入校証なしのとき：登録済みの生徒と一致しなかった
        if ($student === null) {
            return response()->json([
                'result' => 'unknown',
                'message' => '登録されている生徒と一致しませんでした。',
            ]);
        }

        $summary = FaceMatcher::summary($student);

        // 2. 生徒は特定できたが、この授業（この時限）の受講者ではない
        $notEnrolled = $this->notEnrolledMessage($subject, $student, $period);
        if ($notEnrolled !== null) {
            return response()->json(['result' => 'not_enrolled', 'student' => $summary, 'message' => $notEnrolled]);
        }

        // 3. 出席（開始時刻まで）か遅刻（開始時刻を過ぎてから）を記録する
        //    今日のこの時限にすでに記録があれば（教師が手動で公欠にした場合なども）、上書きせずにそのまま返す
        //    （createOrFirst は、同時に2回送られて二重登録になりそうなときも1件にしてくれる）
        $attendance = Attendance::createOrFirst(
            ['student_id' => $student->id, 'subject_id' => $subject->id, 'date' => today()->toDateString(), 'period' => $period],
            // 記録の方法：入校証をかざしてから顔認証をしたら card_face、顔認証だけなら face
            ['status' => $status, 'method' => $cardStudent !== null ? 'card_face' : 'face', 'match_log_id' => $log->id],
        );

        if (! $attendance->wasRecentlyCreated) {
            return response()->json([
                'result' => 'already',
                'student' => $summary,
                'status_label' => $attendance->statusLabel(),
                'message' => "{$student->name} さんは{$period}限の記録済みです（{$attendance->statusLabel()}）。",
            ]);
        }

        return response()->json([
            'result' => 'recorded',
            'student' => $summary,
            'status_label' => $attendance->statusLabel(),
            'message' => "{$student->name} さんの{$period}限を「{$attendance->statusLabel()}」で記録しました。",
            'recorded_count' => $subject->attendances()
                ->where('date', today()->toDateString())
                ->where('period', $period)
                ->count(),
        ]);
    }

    // 受付時間の外のときに、画面に出す文
    private function closedMessage(CheckinWindow $window, int $period): string
    {
        return $window->hasOpened(now())
            ? "{$period}限の受付は終わりました（{$window->closesAt->format('G:i')}まで）。出席一覧から手動で登録してください。"
            : "{$period}限の受付は {$window->opensAt->format('G:i')} からです。";
    }

    // この生徒が、この授業のこの時限を受けないときは、その理由を返す（受けるなら null）
    private function notEnrolledMessage(Subject $subject, Student $student, int $period): ?string
    {
        // この授業の受講者ではない
        if (! $subject->students()->whereKey($student->id)->exists()) {
            return "{$student->name} さんはこの授業の受講者ではありません。";
        }

        // 受講者だが、クラスの授業変更で、この時限はこの授業を受けないクラスの生徒
        // （生徒のクラスは、この授業の年度の在籍で決まる。クラスが未定の生徒は止めない）
        $classIds = $subject->classIdsOn(today(), $period);
        $classId = $student->enrollmentFor($subject->academicYear)?->school_class_id;
        if ($subject->periodsOn(today()) !== [] && $classIds !== [] && $classId !== null && ! in_array($classId, $classIds, true)) {
            return "{$student->name} さんのクラスは、授業変更のため、この時限はこの授業がありません。";
        }

        return null;
    }
}
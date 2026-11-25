<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\UsesAcademicYear;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\Subject;
use App\Services\CheckinWindow;
use App\Services\SchoolCalendar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// 担当授業の出席一覧と、教師による手動での出席登録
class AttendanceController extends Controller
{
    use UsesAcademicYear; // ensureEditable()（読み取り専用の年度の出席は変更させない）

    // 授業の出席一覧（?date=2026-10-05 で日付を指定。省略すると今日）
    public function show(Request $request, Subject $subject, SchoolCalendar $calendar): View
    {
        $teacher = $request->user('teacher');
        abort_unless($teacher->teachesSubject($subject), 403, 'この授業の担当ではありません。');

        $request->validate(['date' => ['nullable', 'date_format:Y-m-d']]);
        $date = Carbon::parse($request->query('date', today()->toDateString()));
        $period = $subject->periodOrFirst($request->query('period'), $date);

        $year = $subject->academicYear; // 生徒のクラスは、この授業の年度の在籍で決まる

        $students = $subject->students()
            // この授業の年度の在籍（クラス）も一緒に読み込む。$student->enrollmentFor($year) で使う
            ->with(['enrollments' => fn ($e) => $e->where('academic_year_id', $year->id)->with('schoolClass')])
            ->get()
            // クラス名の順 → 学籍番号の順（クラスが未定の生徒は最後）
            ->sortBy(fn (Student $student) => [
                $student->enrollmentFor($year)?->schoolClass?->name ?? "\u{FFFF}",
                $student->student_number,
            ])
            ->values();

        // クラスの授業変更で、この時限にこの授業を受けないクラスの生徒は出さない
        // （この日に授業がないときは、手動で記録できるように全員を出す。クラスが未定の生徒も出す）
        $classIds = $subject->classIdsOn($date, $period);
        if ($subject->periodsOn($date) !== [] && $classIds !== []) {
            $students = $students->filter(function (Student $student) use ($year, $classIds) {
                $classId = $student->enrollmentFor($year)?->school_class_id;

                return $classId === null || in_array($classId, $classIds, true);
            })->values();
        }

        // 生徒ID => 出席記録
        $attendances = $subject->attendances()
            ->with('teacher')
            ->where('date', $date->toDateString())
            ->where('period', $period)
            ->get()
            ->keyBy('student_id');

        // 記録がない生徒を「欠席」として扱うか
        //   受付が終わったコマで、顔認証をしなかった生徒は欠席。ただし記録が1件もないコマは、
        //   受付をしていない（休講・まだ出欠を取っていない）とみなして「未記録」のままにする（分析の数え方と同じ）
        $absentIfMissing = $attendances->isNotEmpty() && CheckinWindow::for($date, $period)->hasClosed(now());

        // 状態ごとの人数（表に出している生徒で数える。記録がない生徒は、欠席か未記録）
        $missing = $students->reject(fn (Student $student) => $attendances->has($student->id))->count();
        $counts = collect(Attendance::STATUSES)->map(fn ($label, $status) => $attendances->where('status', $status)->count())->all();
        $counts['absent'] += $absentIfMissing ? $missing : 0;
        $counts['none'] = $absentIfMissing ? 0 : $missing;

        return view('teacher.subjects.show', [
            'subject' => $subject,
            'date' => $date,
            'period' => $period,
            'periods' => $subject->periodsFor($date), // 切り替えられる時限
            'isToday' => $date->isToday(),
            'isFuture' => $date->isFuture(),
            // 読み取り専用の年度（過去の年度）の授業は、表示だけにする
            'readOnly' => ! $year->isEditable(),
            'year' => $year,
            // その日にこの授業がない（祝日・休校・曜日違い）とき、画面に注意を出す
            'wrongDay' => $subject->periodsOn($date) === [],
            'dayNote' => $calendar->dayNote($date),
            'dayLabel' => Subject::DAYS[$date->dayOfWeekIso],
            'students' => $students,
            'attendances' => $attendances,
            'counts' => $counts,
            'absentIfMissing' => $absentIfMissing,
            'window' => CheckinWindow::for($date, $period), // 受付時間（画面に表示する）
        ]);
    }

    // 1人分の出席を手動で登録・変更・取り消しする
    public function update(Request $request, Subject $subject, Student $student): RedirectResponse
    {
        $teacher = $request->user('teacher');
        abort_unless($teacher->teachesSubject($subject), 403, 'この授業の担当ではありません。');
        abort_unless($subject->students()->whereKey($student->id)->exists(), 404, 'この授業の受講者ではありません。');
        $this->ensureEditable($subject->academicYear); // 読み取り専用の年度の出席は変更させない

        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            // none は「未記録に戻す」
            'status' => ['required', Rule::in([...array_keys(Attendance::STATUSES), 'none'])],
        ]);

        // 時限は、日付が決まってから「その日の時限か」をチェックする
        $data += $request->validate([
            'period' => ['required', 'integer', Rule::in($subject->periodsFor(Carbon::parse($data['date'])))],
        ]);

        $keys = ['student_id' => $student->id, 'subject_id' => $subject->id, 'date' => $data['date'], 'period' => (int) $data['period']];

        if ($data['status'] === 'none') {
            Attendance::where($keys)->delete();
            $message = "{$student->name} さんの{$data['period']}限の記録を取り消しました。";
        } else {
            // あれば更新、なければ作成。顔認証の記録を変更した場合も「手動」になる（照合の記録 match_log_id は残る）
            Attendance::updateOrCreate($keys, [
                'status' => $data['status'],
                'method' => 'manual',
                'teacher_id' => $teacher->id,
            ]);
            $message = "{$student->name} さんの{$data['period']}限を「".Attendance::STATUSES[$data['status']].'」にしました。';
        }

        return redirect()
            ->route('teacher.subjects.show', ['subject' => $subject, 'date' => $data['date'], 'period' => $data['period']])
            ->with('status', $message);
    }
}
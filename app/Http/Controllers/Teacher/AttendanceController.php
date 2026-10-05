<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// 担当授業の出席一覧と、教師による手動での出席登録
class AttendanceController extends Controller
{
    // 授業の出席一覧（?date=2026-10-05 で日付を指定。省略すると今日）
    public function show(Request $request, Subject $subject): View
    {
        $teacher = $request->user('teacher');
        abort_unless($teacher->teachesSubject($subject), 403, 'この授業の担当ではありません。');

        $request->validate(['date' => ['nullable', 'date_format:Y-m-d']]);
        $date = Carbon::parse($request->query('date', today()->toDateString()));

        $students = $subject->students()
            ->with('schoolClass')
            ->orderBy('school_class_id')
            ->orderBy('student_number')
            ->get();

        // 生徒ID => 出席記録
        $attendances = $subject->attendances()
            ->with('teacher')
            ->where('date', $date->toDateString())
            ->get()
            ->keyBy('student_id');

        // 状態ごとの人数（未記録は全員から記録済みを引く）
        $counts = collect(Attendance::STATUSES)->map(fn ($label, $status) => $attendances->where('status', $status)->count());
        $counts['none'] = $students->count() - $attendances->count();

        return view('teacher.subjects.show', [
            'subject' => $subject,
            'date' => $date,
            'isToday' => $date->isToday(),
            'isFuture' => $date->isFuture(),
            // 指定した日がこの授業の曜日と違うとき、画面に注意を出す
            'wrongDay' => $date->dayOfWeekIso !== $subject->day_of_week,
            'dayLabel' => Subject::DAYS[$date->dayOfWeekIso],
            'students' => $students,
            'attendances' => $attendances,
            'counts' => $counts,
        ]);
    }

    // 1人分の出席を手動で登録・変更・取り消しする
    public function update(Request $request, Subject $subject, Student $student): RedirectResponse
    {
        $teacher = $request->user('teacher');
        abort_unless($teacher->teachesSubject($subject), 403, 'この授業の担当ではありません。');
        abort_unless($subject->students()->whereKey($student->id)->exists(), 404, 'この授業の受講者ではありません。');

        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            // none は「未記録に戻す」
            'status' => ['required', Rule::in([...array_keys(Attendance::STATUSES), 'none'])],
        ]);

        $keys = ['student_id' => $student->id, 'subject_id' => $subject->id, 'date' => $data['date']];

        if ($data['status'] === 'none') {
            Attendance::where($keys)->delete();
            $message = "{$student->name} さんの記録を取り消しました。";
        } else {
            // あれば更新、なければ作成。顔認証の記録を変更した場合も「手動」になる（照合の記録 match_log_id は残る）
            Attendance::updateOrCreate($keys, [
                'status' => $data['status'],
                'method' => 'manual',
                'teacher_id' => $teacher->id,
            ]);
            $message = "{$student->name} さんを「".Attendance::STATUSES[$data['status']].'」にしました。';
        }

        return redirect()
            ->route('teacher.subjects.show', ['subject' => $subject, 'date' => $data['date']])
            ->with('status', $message);
    }
}

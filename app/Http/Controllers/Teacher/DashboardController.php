<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Subject;
use App\Services\SchoolCalendar;
use Illuminate\Http\Request;
use Illuminate\View\View;

// 教師のホーム：今日の授業・担当授業・受け持ちクラスの一覧
class DashboardController extends Controller
{
    public function index(Request $request, SchoolCalendar $calendar): View
    {
        $teacher = $request->user('teacher');
        $today = today();

        $subjects = $teacher->subjects()
            ->with('schedules')
            ->withCount('students')
            ->orderBySchedule()
            ->get();

        // 今日の授業を時限ごとの行にする（今日の曜日に時間割がある授業だけ。3〜5限なら3行）
        $todayRows = $subjects
            ->flatMap(fn ($subject) => collect($subject->periodsOn($today))
                ->map(fn ($period) => ['subject' => $subject, 'period' => $period]))
            ->sortBy('period')
            ->values();
        $todaySubjectIds = $todayRows->pluck('subject.id')->unique();

        // 「授業ID-時限」 => 出席＋遅刻の人数
        $presentCounts = Attendance::query()
            ->where('date', $today->toDateString())
            ->whereIn('subject_id', $todaySubjectIds)
            ->whereIn('status', ['present', 'late'])
            ->selectRaw('subject_id, period, count(*) as total')
            ->groupBy('subject_id', 'period')
            ->get()
            ->mapWithKeys(fn ($row) => ["{$row->subject_id}-{$row->period}" => $row->total]);

        $classes = $teacher->schoolClasses()->withCount('students')->orderBy('name')->get();

        return view('teacher.dashboard', [
            'today' => $today,
            'todayLabel' => Subject::DAYS[$today->dayOfWeekIso],
            'todayNote' => $calendar->dayNote($today),
            'todayRows' => $todayRows,
            'presentCounts' => $presentCounts,
            'subjects' => $subjects,
            'classes' => $classes,
        ]);
    }
}

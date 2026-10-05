<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\View\View;

// 教師のホーム：今日の授業・担当授業・受け持ちクラスの一覧
class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $teacher = $request->user('teacher');
        $today = today();

        $subjects = $teacher->subjects()
            ->withCount('students')
            ->orderBy('day_of_week')
            ->orderBy('period_start')
            ->orderBy('name')
            ->get();

        // 今日の曜日の授業（Carbon の dayOfWeekIso は 1=月 … 7=日 で、subjects.day_of_week と同じ）
        $todaySubjects = $subjects->where('day_of_week', $today->dayOfWeekIso)->values();

        // 今日の授業ごとの「出席＋遅刻」の人数
        $presentCounts = Attendance::query()
            ->where('date', $today->toDateString())
            ->whereIn('subject_id', $todaySubjects->pluck('id'))
            ->whereIn('status', ['present', 'late'])
            ->selectRaw('subject_id, count(*) as total')
            ->groupBy('subject_id')
            ->pluck('total', 'subject_id');

        $classes = $teacher->schoolClasses()->withCount('students')->orderBy('name')->get();

        return view('teacher.dashboard', [
            'today' => $today,
            'todayLabel' => Subject::DAYS[$today->dayOfWeekIso],
            'todaySubjects' => $todaySubjects,
            'presentCounts' => $presentCounts,
            'subjects' => $subjects,
            'classes' => $classes,
        ]);
    }
}

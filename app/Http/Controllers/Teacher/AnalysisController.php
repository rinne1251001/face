<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Services\AttendanceStats;
use Illuminate\Http\Request;
use Illuminate\View\View;

// 受け持ちクラス・担当授業の分析（出席率）
// 出席率の数え方は AttendanceStats にまとめてある
// AttendanceStats は、画面ごと（アクションの引数）で受け取る。コンストラクタで受け取ると、
// コントローラーが使い回されたとき（Octane など）に、前のリクエストの集計結果が残ってしまうため
class AnalysisController extends Controller
{
    // 一覧画面：受け持ちクラスごとの月別の出席率と今日の出欠、担当授業ごとの月別の出席率
    public function index(Request $request, AttendanceStats $stats): View
    {
        $teacher = $request->user('teacher');
        $year = AcademicYear::currentOrNewest(); // 今年度の受け持ちクラス・担当授業だけ
        $classes = $teacher->schoolClasses()->where('school_classes.academic_year_id', $year?->id)->orderBy('name')->get();
        $subjects = $teacher->subjects()->where('subjects.academic_year_id', $year?->id)->with('schedules')->orderBySchedule()->get();
        $months = $year ? AttendanceStats::yearMonths($year) : [];

        // クラスID => ['points' => 折れ線グラフの点, 'today' => 今日の出欠の数]
        $classCharts = [];
        foreach ($classes as $class) {
            $records = $stats->forClass($class);
            $classCharts[$class->id] = [
                'points' => AttendanceStats::monthlyPoints($records, $months),
                'today' => AttendanceStats::summarize($records->where('date', today()->toDateString())),
            ];
        }

        // 授業ID => 折れ線グラフの点
        $subjectCharts = [];
        foreach ($subjects as $subject) {
            $subjectCharts[$subject->id] = AttendanceStats::monthlyPoints($stats->forSubject($subject), $months);
        }

        return view('teacher.analyses.index', compact('classes', 'subjects', 'classCharts', 'subjectCharts'));
    }

    // クラスの詳細：生徒ごと・時限ごとの、月別の出席率
    public function showClass(Request $request, SchoolClass $class, AttendanceStats $stats): View
    {
        $teacher = $request->user('teacher');
        abort_unless($teacher->hasSchoolClass($class), 403, 'このクラスの受け持ちではありません。');

        $students = $class->students()
            ->orderBy('attend_number')
            ->orderBy('student_number')
            ->get();
        $records = $stats->forClass($class);
        $months = AttendanceStats::recentMonths($class->academicYear);

        return view('teacher.analyses.class', [
            'class' => $class,
            'students' => $students,
            'months' => $months,
            // 生徒ID => 表の1行分
            'studentRows' => $students->mapWithKeys(fn (Student $student) => [
                $student->id => AttendanceStats::row($records->where('student_id', $student->id), $months),
            ])->all(),
            'periodRows' => $this->periodRows($records, $months),
        ]);
    }

    // 生徒の詳細：全体の出席率、時限ごと・授業ごとの月別の出席率
    public function showStudent(Request $request, Student $student, AttendanceStats $stats): View
    {
        $teacher = $request->user('teacher');
        abort_unless($this->canSeeStudent($teacher, $student), 403, 'この生徒の受け持ち・担当ではありません。');

        $year = AcademicYear::currentOrNewest();
        abort_if($year === null, 404);

        $enrollment = $student->enrollments()->where('academic_year_id', $year->id)->with('schoolClass.teachers')->first();
        $subjects = $student->subjects()->where('subjects.academic_year_id', $year->id)->with('schedules')->orderBySchedule()->get();
        $records = $stats->forStudent($student, $year);
        $months = AttendanceStats::recentMonths($year);

        return view('teacher.analyses.student', [
            'student' => $student,
            'enrollment' => $enrollment, // 今年度の在籍（在籍していなければ null）
            'subjects' => $subjects,
            'months' => $months,
            'overall' => AttendanceStats::rate($records),
            'periodRows' => $this->periodRows($records, $months),
            // 授業ID => 表の1行分
            'subjectRows' => $subjects->mapWithKeys(fn (Subject $subject) => [
                $subject->id => AttendanceStats::row($records->where('subject_id', $subject->id), $months),
            ])->all(),
        ]);
    }

    // 授業の詳細：時限ごと・曜日×時限ごと・生徒ごとの出席率
    public function showSubject(Request $request, Subject $subject, AttendanceStats $stats): View
    {
        $teacher = $request->user('teacher');
        abort_unless($teacher->teachesSubject($subject), 403, 'この授業の担当ではありません。');

        $students = $subject->students()->orderBy('student_number')->get();
        $records = $stats->forSubject($subject);
        $months = AttendanceStats::recentMonths($subject->academicYear);

        // 曜日×時限：時限 => [曜日 => 出席率]。曜日は月〜金と、記録がある土日
        $weekdays = [1 => '月', 2 => '火', 3 => '水', 4 => '木', 5 => '金'];
        foreach ([6 => '土', 7 => '日'] as $day => $label) {
            if ($records->contains('weekday', $day)) {
                $weekdays[$day] = $label;
            }
        }
        $grid = [];
        foreach ($records->groupBy('period')->sortKeys() as $period => $periodRecords) {
            $byDay = AttendanceStats::ratesBy($periodRecords, 'weekday');
            $days = [];
            foreach (array_keys($weekdays) as $day) {
                $days[$day] = $byDay[$day] ?? null; // その曜日のその時限に授業がなければ null
            }
            $grid[$period] = ['days' => $days, 'overall' => AttendanceStats::rate($periodRecords)];
        }

        return view('teacher.analyses.subject', [
            'subject' => $subject,
            'students' => $students,
            'months' => $months,
            'periodRows' => $this->periodRows($records, $months),
            'weekdays' => $weekdays,
            'grid' => $grid,
            // 生徒ID => 表の1行分
            'studentRows' => $students->mapWithKeys(fn (Student $student) => [
                $student->id => AttendanceStats::row($records->where('student_id', $student->id), $months),
            ])->all(),
        ]);
    }

    // 時限ごとの表：時限 => 表の1行分（記録がある時限だけ、時限の順）
    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $records
     * @param  array<string, string>  $months
     * @return array<int, array{overall: array{rate: int|null, warning: bool}, months: array<string, array{rate: int|null, warning: bool}>}>
     */
    private function periodRows($records, array $months): array
    {
        return $records->groupBy('period')
            ->sortKeys()
            ->map(fn ($periodRecords) => AttendanceStats::row($periodRecords, $months))
            ->all();
    }

    // この生徒の分析を見てよいか：生徒のクラスを受け持っている、または生徒が受けている授業を担当している
    private function canSeeStudent(Teacher $teacher, Student $student): bool
    {
        return $teacher->schoolClasses()->whereHas('enrollments', fn ($e) => $e->where('student_id', $student->id))->exists()
            || $teacher->subjects()->whereHas('students', fn ($s) => $s->whereKey($student->id))->exists();
    }
}
<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Services\SchoolCalendar;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

// 受け持ちクラスの出席状況（その日の授業ごと。見るだけで、変更は授業の画面から）
class ClassController extends Controller
{
    public function index(Request $request): View
    {
        $teacher = $request->user('teacher');
        $classes = $teacher->schoolClasses()->withCount('students')->orderBy('name')->get();

        return view('teacher.classes.index', compact('classes'));
    }

    public function show(Request $request, SchoolClass $schoolClass, SchoolCalendar $calendar): View
    {
        $teacher = $request->user('teacher');
        abort_unless($teacher->hasSchoolClass($schoolClass), 403, 'このクラスの受け持ちではありません。');

        $request->validate(['date' => ['nullable', 'date_format:Y-m-d']]);
        $date = Carbon::parse($request->query('date', today()->toDateString()));
        $dayOfWeek = $date->dayOfWeekIso;

        // クラスの生徒と、それぞれがその曜日に受講している授業
        $students = $schoolClass->students()
            ->with('subjects.schedules')
            ->orderBy('attend_number')
            ->get();

        // このクラスの誰かが受講している、その曜日の授業
        // （flatMap->subjects と同じ意味。こう書くと、中身が Subject だと PHPStan に伝わる）
        $subjects = $students->flatMap(fn (Student $student) => $student->subjects)
            ->unique('id')
            ->filter(fn ($subject) => $subject->periodsOn($date) !== []) // その日に授業があるものに絞る
            ->values();

        // 表の列：「授業 × 時限」（3〜5限の授業なら3列）。時限の順に並べる
        // （クラスの授業変更で、このクラスが受けない時限は出さない）
        $columns = $subjects
            ->flatMap(fn ($subject) => collect($subject->periodsOn($date))
            ->filter(fn ($period) => in_array($schoolClass->id, $subject->classIdsOn($date, $period), true))
            ->map(fn ($period) => ['subject' => $subject, 'period' => $period]))
            // 時限の順 → 同じ時限なら授業名の順（2つ目は2つの列を比べる関数。[...] で囲まずにそのまま渡す）
            ->sortBy([['period', 'asc'], fn (array $a, array $b) => $a['subject']->name <=> $b['subject']->name])
            ->values();

        // 「生徒ID-授業ID-時限」 => 出席記録
        $attendances = Attendance::query()
            ->where('date', $date->toDateString())
            ->whereIn('student_id', $students->pluck('id'))
            ->whereIn('subject_id', $subjects->pluck('id'))
            ->get()
            ->keyBy(fn ($a) => "{$a->student_id}-{$a->subject_id}-{$a->period}");

        return view('teacher.classes.show', [
            'schoolClass' => $schoolClass,
            'date' => $date,
            'dayLabel' => Subject::DAYS[$dayOfWeek],
            'dayNote' => $calendar->dayNote($date),
            'students' => $students,
            'subjects' => $subjects,
            'columns' => $columns, // ← これがなかったので $columns が未定義になっていた
            'attendances' => $attendances,
            // 自分の担当授業なら、列の見出しから授業の画面へ移動できるようにする
            'mySubjectIds' => array_map('intval', $teacher->subjects()->pluck('subjects.id')->all()),
        ]);
    }
}
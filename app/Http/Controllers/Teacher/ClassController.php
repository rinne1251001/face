<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

// 受け持ちクラスの出席状況（その日の授業ごと。見るだけで、変更は授業の画面から）
class ClassController extends Controller
{
    public function show(Request $request, SchoolClass $schoolClass): View
    {
        $teacher = $request->user('teacher');
        abort_unless($teacher->hasSchoolClass($schoolClass), 403, 'このクラスの受け持ちではありません。');

        $request->validate(['date' => ['nullable', 'date_format:Y-m-d']]);
        $date = Carbon::parse($request->query('date', today()->toDateString()));
        $dayOfWeek = $date->dayOfWeekIso;

        // クラスの生徒と、それぞれがその曜日に受講している授業
        $students = $schoolClass->students()
            ->with(['subjects' => fn ($q) => $q->where('day_of_week', $dayOfWeek)])
            ->orderBy('student_number')
            ->get();

        // 表の列：このクラスの誰かが受講している、その曜日の授業
        $subjects = $students->flatMap->subjects
            ->unique('id')
            ->sortBy([['period_start', 'asc'], ['name', 'asc']])
            ->values();

        // 「生徒ID-授業ID」 => 出席記録
        $attendances = Attendance::query()
            ->where('date', $date->toDateString())
            ->whereIn('student_id', $students->pluck('id'))
            ->whereIn('subject_id', $subjects->pluck('id'))
            ->get()
            ->keyBy(fn ($a) => $a->student_id.'-'.$a->subject_id);

        return view('teacher.classes.show', [
            'schoolClass' => $schoolClass,
            'date' => $date,
            'dayLabel' => Subject::DAYS[$dayOfWeek],
            'students' => $students,
            'subjects' => $subjects,
            'attendances' => $attendances,
            // 自分の担当授業なら、列の見出しから授業の画面へ移動できるようにする
            'mySubjectIds' => array_map('intval', $teacher->subjects()->pluck('subjects.id')->all()),
        ]);
    }
}

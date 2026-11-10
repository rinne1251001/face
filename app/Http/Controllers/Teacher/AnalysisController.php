<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\View\View;

// 受け持ちクラスの分析
class AnalysisController extends Controller
{
    // 一覧画面
    public function index(Request $request): View
    {
        $teacher = $request->user('teacher');
        $classes = $teacher->schoolClasses()->orderBy('name')->get();
        $subjects = $teacher->subjects()->get();

        return view('teacher.analyses.index', compact('classes', 'subjects'));
    }

    // 詳細画面
    public function showClass(Request $request, SchoolClass $class): View
    {
        $teacher = $request->user('teacher');
        abort_unless($teacher->hasSchoolClass($class), 403, 'このクラスの受け持ちではありません。');

        $students = $class->students()
            ->orderBy('attend_number')
            ->get();

        return view('teacher.analyses.class', compact('class', 'students'));
    }

    public function showStudent(Student $student): View
    {
        $subjects = $student->subjects()->get();

        return view('teacher.analyses.student', compact('student', 'subjects'));
    }

    public function showSubject(Subject $subject): View
    {
        $students = $subject->students()->get();

        return view('teacher.analyses.subject', compact('subject', 'students'));
    }
}
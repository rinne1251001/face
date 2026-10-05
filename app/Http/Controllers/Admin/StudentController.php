<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Services\FaceMatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// 生徒の一覧・追加・編集・削除（管理者のみ）
class StudentController extends Controller
{
    public function __construct(private FaceMatcher $matcher) {}

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $students = Student::query()
            ->with('schoolClass')
            ->withCount('faceSamples')
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('name', 'like', "%{$q}%")
                ->orWhere('student_number', 'like', "%{$q}%")
                ->orWhereHas('schoolClass', fn ($c) => $c->where('name', 'like', "%{$q}%"))))
            ->orderBy('school_class_id')
            ->orderBy('student_number')
            ->paginate(20)
            ->withQueryString();

        return view('admin.students.index', compact('students', 'q'));
    }

    public function create(): View
    {
        return view('admin.students.form', $this->formData(new Student(['is_active' => true])));
    }

    public function store(Request $request): RedirectResponse
    {
        [$data, $subjectIds] = $this->validated($request);

        $student = DB::transaction(function () use ($data, $subjectIds) {
            $student = Student::create($data);
            $student->subjects()->sync($subjectIds);

            return $student;
        });

        // 「保存して顔写真を登録する」ボタンなら顔の登録画面へ、「保存のみ」なら一覧へ
        if ($request->input('next') === 'faces') {
            return redirect()->route('admin.faces.create', $student)
                ->with('status', '生徒を登録しました。続けて顔写真を登録してください。');
        }

        return redirect()->route('admin.students.index')
            ->with('status', "{$student->name} さんを登録しました。顔写真は一覧の「顔を登録」から後で登録できます。");
    }

    public function edit(Student $student): View
    {
        $student->load([
            'faceSamples' => fn ($q) => $q->select(['id', 'student_id', 'created_at']),
            'subjects',
        ]);

        return view('admin.students.form', $this->formData($student));
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        [$data, $subjectIds] = $this->validated($request, $student);

        DB::transaction(function () use ($student, $data, $subjectIds) {
            $student->update($data);
            $student->subjects()->sync($subjectIds);
        });
        $this->matcher->flush(); // 照合の対象（有効/停止）が変わることがあるため

        return redirect()->route('admin.students.edit', $student)->with('status', '保存しました。');
    }

    public function destroy(Student $student): RedirectResponse
    {
        Storage::disk('local')->deleteDirectory("faces/{$student->id}");
        $student->delete(); // 顔データ・受講授業・出席記録は外部キー制約で一緒に削除される
        $this->matcher->flush();

        return redirect()->route('admin.students.index')->with('status', '削除しました。');
    }

    // フォーム画面に渡すデータ（クラスと授業の選択肢）
    private function formData(Student $student): array
    {
        // 入力エラーで戻ってきたときは、さっき選んでいたチェック状態を使う
        $hasOld = session()->hasOldInput();

        return [
            'student' => $student,
            'classes' => SchoolClass::orderBy('name')->get(),
            'subjects' => Subject::orderBy('day_of_week')->orderBy('period_start')->orderBy('name')->get(),
            'selectedSubjectIds' => array_map('intval', $hasOld
                ? old('subject_ids', [])
                : ($student->exists ? $student->subjects->pluck('id')->all() : [])),
        ];
    }

    // [生徒の項目, 授業IDの配列] を返す
    private function validated(Request $request, ?Student $student = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'student_number' => ['required', 'string', 'max:20', Rule::unique('students')->ignore($student)],
            'school_class_id' => ['required', 'integer', 'exists:school_classes,id'],
            'subject_ids' => ['required', 'array', 'min:1'], // 授業は1つ以上
            'subject_ids.*' => ['integer', 'distinct', 'exists:subjects,id'],
        ]);

        $subjectIds = $data['subject_ids'];
        unset($data['subject_ids']);
        $data['is_active'] = $request->boolean('is_active');

        return [$data, $subjectIds];
    }
}

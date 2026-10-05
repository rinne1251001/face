<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// 教師の一覧・追加・編集・削除と、受け持ちクラス・担当授業の設定（管理者のみ）
class TeacherController extends Controller
{
    public function index(): View
    {
        $teachers = Teacher::query()
            ->with([
                'schoolClasses' => fn ($q) => $q->orderBy('name'),
                'subjects' => fn ($q) => $q->orderBy('day_of_week')->orderBy('period_start'),
            ])
            ->orderBy('name')
            ->get();

        return view('admin.teachers.index', compact('teachers'));
    }

    public function create(): View
    {
        return view('admin.teachers.form', $this->formData(new Teacher));
    }

    public function store(Request $request): RedirectResponse
    {
        [$data, $classIds, $subjectIds] = $this->validated($request);

        $teacher = DB::transaction(function () use ($data, $classIds, $subjectIds) {
            $teacher = Teacher::create($data);
            $teacher->schoolClasses()->sync($classIds);
            $teacher->subjects()->sync($subjectIds);

            return $teacher;
        });

        return redirect()->route('admin.teachers.index')->with('status', "{$teacher->name} 先生を登録しました。");
    }

    public function edit(Teacher $teacher): View
    {
        $teacher->load(['schoolClasses', 'subjects']);

        return view('admin.teachers.form', $this->formData($teacher));
    }

    public function update(Request $request, Teacher $teacher): RedirectResponse
    {
        [$data, $classIds, $subjectIds] = $this->validated($request, $teacher);

        DB::transaction(function () use ($teacher, $data, $classIds, $subjectIds) {
            $teacher->update($data);
            $teacher->schoolClasses()->sync($classIds);
            $teacher->subjects()->sync($subjectIds);
        });

        return redirect()->route('admin.teachers.index')->with('status', '保存しました。');
    }

    public function destroy(Teacher $teacher): RedirectResponse
    {
        // 受け持ち・担当の設定は一緒に削除される。
        // この教師が手動で登録した出席記録は残り、「登録した教師」だけが空になる
        $teacher->delete();

        return redirect()->route('admin.teachers.index')->with('status', '削除しました。');
    }

    private function formData(Teacher $teacher): array
    {
        // 入力エラーで戻ってきたときは、さっき選んでいたチェック状態を使う
        $hasOld = session()->hasOldInput();

        return [
            'teacher' => $teacher,
            'classes' => SchoolClass::orderBy('name')->get(),
            'subjects' => Subject::orderBy('day_of_week')->orderBy('period_start')->orderBy('name')->get(),
            'selectedClassIds' => array_map('intval', $hasOld
                ? old('class_ids', [])
                : ($teacher->exists ? $teacher->schoolClasses->pluck('id')->all() : [])),
            'selectedSubjectIds' => array_map('intval', $hasOld
                ? old('subject_ids', [])
                : ($teacher->exists ? $teacher->subjects->pluck('id')->all() : [])),
        ];
    }

    // [教師の項目, クラスIDの配列, 授業IDの配列] を返す
    private function validated(Request $request, ?Teacher $teacher = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'login_id' => ['required', 'string', 'max:50', Rule::unique('teachers')->ignore($teacher)],
            // 新規登録では必須。編集では空欄なら今のパスワードのまま
            'password' => [$teacher ? 'nullable' : 'required', 'string', 'min:8', 'max:100'],
            'class_ids' => ['nullable', 'array'],             // 受け持ちクラスは0個でもよい
            'class_ids.*' => ['integer', 'distinct', 'exists:school_classes,id'],
            'subject_ids' => ['required', 'array', 'min:1'],  // 担当授業は1つ以上
            'subject_ids.*' => ['integer', 'distinct', 'exists:subjects,id'],
        ]);

        $classIds = $data['class_ids'] ?? [];
        $subjectIds = $data['subject_ids'];
        unset($data['class_ids'], $data['subject_ids']);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        return [$data, $classIds, $subjectIds];
    }
}

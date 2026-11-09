<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\SchoolClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// クラスの一覧・追加・編集・削除（管理者のみ）
// 受け持ちの教師は「教師」の画面で設定する
class SchoolClassController extends Controller
{
    public function index(): View
    {
        $classes = SchoolClass::query()
            ->with(['classroom', 'teachers' => fn ($q) => $q->orderBy('name')]) // 教室も一緒に読み込む
            ->withCount('students')
            ->orderBy('name')
            ->get();

        return view('admin.classes.index', compact('classes'));
    }

    public function create(): View
    {
        return view('admin.classes.form', $this->formData(new SchoolClass));
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolClass = SchoolClass::create($this->validated($request));

        return redirect()->route('admin.classes.index')->with('status', "「{$schoolClass->name}」を追加しました。");
    }

    public function edit(SchoolClass $schoolClass): View
    {
        return view('admin.classes.form', $this->formData($schoolClass));
    }

    public function update(Request $request, SchoolClass $schoolClass): RedirectResponse
    {
        $schoolClass->update($this->validated($request, $schoolClass));

        return redirect()->route('admin.classes.index')->with('status', '保存しました。');
    }

    public function destroy(SchoolClass $schoolClass): RedirectResponse
    {
        // 生徒は必ずどこかのクラスに所属するので、生徒がいるクラスは削除できない
        if ($schoolClass->students()->exists()) {
            return back()->with('error', "「{$schoolClass->name}」には生徒が所属しているため削除できません。先に生徒のクラスを変更してください。");
        }

        $schoolClass->delete(); // 教師の受け持ち設定は外部キー制約で一緒に削除される

        return redirect()->route('admin.classes.index')->with('status', '削除しました。');
    }

    // フォーム画面に渡すデータ（クラスと、教室の選択肢）
    /** @return array<string, mixed> */
    private function formData(SchoolClass $schoolClass): array
    {
        return [
            'schoolClass' => $schoolClass,
            'classrooms' => Classroom::orderBy('floor')->orderBy('name')->get(), // 階 → 教室名の順
        ];
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?SchoolClass $schoolClass = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('school_classes')->ignore($schoolClass)],
            // ホームルームの教室（「未設定」を選ぶと空 → null で保存される）
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
        ], [], [
            'classroom_id' => 'ホームルームの教室',
        ]);
    }
}
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
    public function index(Request $request): View
    {
        // 検索の文字（空のときや、URL をいじって文字以外が送られてきたときは絞り込まない）
        $input = $request->query('q');
        $q = is_string($input) ? trim($input) : '';

        // 空白（全角の空白も）で区切って、キーワードごとに分ける
        // 例：「山田 数学」→ ['山田', '数学']（両方に当てはまる教師だけを出す）
        $keywords = preg_split('/[\s　]+/u', $q, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        // リレーションごとに with() を分ける（1つの配列にまとめると、引数がどちらのリレーションか PHPStan が区別できない）
        $query = Teacher::query()
            ->with('schoolClasses', fn ($c) => $c->orderBy('name'))
            ->with('subjects', fn ($s) => $s->with('schedules')->orderBySchedule());

        // キーワードが、名前・ログインID・受け持ちクラス名・担当授業名のどれかに含まれる教師
        foreach ($keywords as $keyword) {
            $like = "%{$keyword}%";
            $query->where(fn ($w) => $w
                ->where('name', 'like', $like)
                ->orWhere('login_id', 'like', $like)
                ->orWhereHas('schoolClasses', fn ($c) => $c->where('name', 'like', $like))
                ->orWhereHas('subjects', fn ($s) => $s->where('name', 'like', $like)));
        }

        return view('admin.teachers.index', [
            'teachers' => $query->orderBy('name')->get(),
            'q' => $q,
            'total' => Teacher::count(), // 検索に関係なく、登録されている教師の人数
        ]);
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

    /** @return array<string, mixed> */
    private function formData(Teacher $teacher): array
    {
        // 入力エラーで戻ってきたときは、さっき選んでいたチェック状態を使う
        // （前回の入力は文字列や null のこともあるので、(array) で必ず配列にする）
        $hasOld = session()->hasOldInput();

        return [
            'teacher' => $teacher,
            'classes' => SchoolClass::orderBy('name')->get(),
            'subjects' => Subject::with('schedules')->orderBySchedule()->get(),
            'selectedClassIds' => array_map('intval', $hasOld
                ? (array) old('class_ids', [])
                : ($teacher->exists ? $teacher->schoolClasses->pluck('id')->all() : [])),
            'selectedSubjectIds' => array_map('intval', $hasOld
                ? (array) old('subject_ids', [])
                : ($teacher->exists ? $teacher->subjects->pluck('id')->all() : [])),
        ];
    }

    // [教師の項目, クラスIDの配列, 授業IDの配列] を返す
    /** @return array{array<string, mixed>, array<mixed>, array<mixed>} */
    private function validated(Request $request, ?Teacher $teacher = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'login_id' => ['required', 'string', 'max:50', Rule::unique('teachers')->ignore($teacher)],
            // 新規登録では必須。編集では空欄なら今のパスワードのまま
            'password' => [$teacher ? 'nullable' : 'required', 'string', 'min:8', 'max:100'],
            'class_ids' => ['nullable', 'array'],             // 受け持ちクラスは0個でもよい
            'class_ids.*' => ['integer', 'distinct', 'exists:school_classes,id'],
            'subject_ids' => ['nullable', 'array'],           // 担当授業も0個でもよい（あとから授業の画面でも設定できる）
            'subject_ids.*' => ['integer', 'distinct', 'exists:subjects,id'],
        ]);

        $classIds = $data['class_ids'] ?? [];
        $subjectIds = $data['subject_ids'] ?? []; // 1つもチェックしていなければ送られてこないので、空の配列にする
        unset($data['class_ids'], $data['subject_ids']);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        return [$data, $classIds, $subjectIds];
    }
}
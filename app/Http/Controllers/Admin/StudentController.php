<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\SortsList;
use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Services\FaceMatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// 生徒の一覧・追加・編集・削除（管理者のみ）
class StudentController extends Controller
{
    use SortsList;

    public function __construct(private FaceMatcher $matcher) {}

    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));
        $classId = $request->integer('class_id');   // 選ばれていなければ 0
        $faces = $request->query('faces');           // 'registered'（登録済）か 'unregistered'（未登録）
        if (! in_array($faces, ['registered', 'unregistered'], true)) {
            $faces = '';                             // それ以外は「すべて」
        }

        $query = Student::query()
            ->with('schoolClass')
            ->withCount('faceSamples')
            // キーワード（空なら絞り込まない）
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('name', 'like', "%{$q}%")
                ->orWhere('student_number', 'like', "%{$q}%")
                ->orWhereHas('schoolClass', fn ($c) => $c->where('name', 'like', "%{$q}%"))))
            // クラス（「すべてのクラス」なら絞り込まない）
            ->when($classId > 0, fn ($query) => $query->where('school_class_id', $classId))
            // 顔写真
            ->when($faces === 'registered', fn ($query) => $query->has('faceSamples'))
            ->when($faces === 'unregistered', fn ($query) => $query->doesntHave('faceSamples'));

        // 並び替え（何も指定がなければクラス順）
        [$sort, $direction] = $this->applySort($query, $request, $this->sortColumns(), 'class');

        $students = $query
            ->orderBy('student_number') // 同じ値どうしは学籍番号順（同じクラスの中など）
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();       // ページ送りのリンクにも、検索・並び替えの条件を付ける

        // Ajax（並び替え・ページ送り）のときは、一覧の表の部分だけを返す
        $view = $request->ajax() ? 'admin.students.partials.list' : 'admin.students.index';

        $classes = SchoolClass::orderBy('name')->get(); // クラスの絞り込みの選択肢

        return response()
            ->view($view, compact('students', 'q', 'classId', 'faces', 'classes', 'sort', 'direction'))
            // 同じ URL で「ページ全体」と「表だけ」を返し分けるので、ブラウザのキャッシュで混ざらないようにする
            ->header('Vary', 'X-Requested-With');
    }

    // 一覧で並び替えできる列（URL の ?sort= に使うキー => 並び替えに使う列）
    // 並び替えを増やすときは、ここに1行足して、ビューの見出しを partials.sort-header にするだけ
    private function sortColumns(): array
    {
        return [
            'student_number' => 'student_number',
            'name' => 'name',
            // クラス名は別のテーブルにあるので、クラス名を取り出すサブクエリで並び替える
            'class' => SchoolClass::select('name')->whereColumn('school_classes.id', 'students.school_class_id'),
        ];
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

    // 一斉追加の画面
    public function bulkCreate(): View
    {
        return view('admin.students.bulk', [
            'classes' => SchoolClass::orderBy('name')->get(),
            'subjects' => Subject::orderBy('day_of_week')->orderBy('period_start')->orderBy('name')->get(),
        ]);
    }

    // 一斉追加の保存（1人でもエラーがあれば、誰も登録しない）
    public function bulkStore(Request $request): RedirectResponse
    {
        // 名前も学籍番号も空の行は無視する（クラスだけ選ばれている行も無視）
        $rows = collect($request->input('students', []))
            ->filter(fn ($row) => is_array($row) && (filled($row['name'] ?? null) || filled($row['student_number'] ?? null)))
            ->values()
            ->all();
        $request->merge(['students' => $rows]);

        $data = $request->validate([
            'students' => ['required', 'array', 'max:200'],
            'students.*.name' => ['required', 'string', 'max:100'],
            // distinct:strict … 表の中での重複チェック（strict がないと "001" と "1" が同じ扱いになる）
            'students.*.student_number' => ['required', 'string', 'max:20', 'distinct:strict', 'unique:students,student_number'],
            'students.*.school_class_id' => ['required', 'integer', 'exists:school_classes,id'],
            'subject_ids' => ['nullable', 'array'], // 受講する授業は空でも可
            'subject_ids.*' => ['integer', 'distinct', 'exists:subjects,id'],
        ], [
            'students.required' => '名前と学籍番号を1人分以上入力してください。',
            'students.*.student_number.distinct' => ':position行目の学籍番号「:input」が表の中で重複しています。',
            'students.*.student_number.unique' => ':position行目の学籍番号「:input」は既に登録されています。',
        ], [
            // :position は「何行目か」（1から数える）に置き換わる
            'students' => '生徒',
            'students.*.name' => ':position行目の名前',
            'students.*.student_number' => ':position行目の学籍番号',
            'students.*.school_class_id' => ':position行目のクラス',
            'subject_ids' => '受講する授業',
            'subject_ids.*' => '受講する授業',
        ]);

        $subjectIds = $data['subject_ids'] ?? [];
        $isActive = $request->boolean('is_active');

        DB::transaction(function () use ($data, $subjectIds, $isActive) {
            foreach ($data['students'] as $row) {
                $student = Student::create($row + ['is_active' => $isActive]);
                $student->subjects()->sync($subjectIds);
            }
        });

        $count = count($data['students']);

        return redirect()->route('admin.students.index')
            ->with('status', "{$count}人の生徒を登録しました。顔写真は一覧の「顔を登録」から登録してください。");
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

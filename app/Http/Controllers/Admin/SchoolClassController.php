<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\SortsList;
use App\Models\Classroom;
use App\Models\SchoolClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// クラスの一覧・追加・編集・削除（管理者のみ）
// 受け持ちの教師は「教師」の画面で設定する
class SchoolClassController extends Controller
{
    use SortsList; // 並び替えの共通処理（$this->applySort() が使えるようになる）

    // 生徒数の絞り込みの選択肢（URL の ?students= に使うキー => [画面の文字, 最小, 最大]）
    // 最大が null のものは「〜人以上」。選択肢を増やしたいときは、ここに1行足すだけ
    private const STUDENT_RANGES = [
        '0' => ['label' => '0人', 'min' => 0, 'max' => 0],
        '1-10' => ['label' => '1〜10人', 'min' => 1, 'max' => 10],
        '11-20' => ['label' => '11〜20人', 'min' => 11, 'max' => 20],
        '21-30' => ['label' => '21〜30人', 'min' => 21, 'max' => 30],
        '31-' => ['label' => '31人以上', 'min' => 31, 'max' => null],
    ];

    // 一覧画面（検索・生徒数と受け持ちの教師での絞り込み・並び替え）
    public function index(Request $request): Response
    {
        // 検索の文字（空のときや、URL をいじって文字以外が送られてきたときは絞り込まない）
        $input = $request->query('q');
        $q = is_string($input) ? trim($input) : '';

        // 空白（全角の空白も）で区切って、キーワードごとに分ける
        // 例：「1年 山田」→ ['1年', '山田']（両方に当てはまるクラスだけを出す）
        $keywords = preg_split('/[\s　]+/u', $q, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        // 生徒数（STUDENT_RANGES のキーだけ受け付ける。それ以外は「すべて」）
        $students = $request->query('students');
        if (! is_string($students) || ! array_key_exists($students, self::STUDENT_RANGES)) {
            $students = '';
        }

        // 受け持ちの教師：'with'（担任あり）か 'without'（担任なし）。それ以外は「すべて」
        $teachers = $request->query('teachers');
        if (! in_array($teachers, ['with', 'without'], true)) {
            $teachers = '';
        }

        $query = SchoolClass::query()
            ->with(['classroom', 'teachers' => fn ($q) => $q->orderBy('name')]) // 教室も一緒に読み込む
            ->withCount('students') // 生徒数（$schoolClass->students_count で使える。並び替えにも使う）
            // has()：受け持ちの教師が1人以上いるクラス ／ doesntHave()：1人もいないクラス
            ->when($teachers === 'with', fn ($w) => $w->has('teachers'))
            ->when($teachers === 'without', fn ($w) => $w->doesntHave('teachers'));

        // 生徒数で絞り込む（has('students', '>=', 11) …「生徒が11人以上いるクラス」）
        if ($students !== '') {
            $range = self::STUDENT_RANGES[$students];
            $query->has('students', '>=', $range['min']);
            if ($range['max'] !== null) {
                $query->has('students', '<=', $range['max']);
            }
        }

        // キーワードが、クラス名・受け持ちの教師の名前のどちらかに含まれるクラス
        foreach ($keywords as $keyword) {
            $like = "%{$keyword}%";
            $query->where(fn ($w) => $w
                ->where('name', 'like', $like)
                ->orWhereHas('teachers', fn ($t) => $t->where('name', 'like', $like)));
        }

        // 並び替え（何も指定がなければクラス名の順）
        [$sort, $direction] = $this->applySort($query, $request, $this->sortColumns(), 'name');

        $classes = $query
            ->orderBy('name') // 同じ値どうしはクラス名の順（生徒数が同じクラスなど）
            ->orderBy('id')
            ->get();

        // 何か1つでも絞り込んでいるか（「クリア」の表示や、0件のときの文に使う）
        $filtering = $q !== '' || $students !== '' || $teachers !== '';
        $studentRanges = self::STUDENT_RANGES;

        // Ajax（見出しを押した並び替え）のときは、一覧の表の部分だけを返す
        $view = $request->ajax() ? 'admin.classes.partials.list' : 'admin.classes.index';

        return response()
            ->view($view, compact('classes', 'q', 'students', 'teachers', 'studentRanges', 'filtering', 'sort', 'direction'))
            // 同じ URL で「ページ全体」と「表だけ」を返し分けるので、ブラウザのキャッシュで混ざらないようにする
            ->header('Vary', 'X-Requested-With');
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

    // 一覧で並び替えできる列（URL の ?sort= に使うキー => 並び替えに使う列）
    /** @return array<string, mixed> */
    private function sortColumns(): array
    {
        return [
            'name' => 'name',
            'students' => 'students_count', // withCount('students') で作られる「生徒数」の列
        ];
    }
}
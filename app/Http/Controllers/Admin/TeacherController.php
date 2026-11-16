<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\UsesAcademicYear;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\SubjectScheduleRoom;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// 教師の一覧・追加・編集・削除と、受け持ちクラス・担当授業の設定（管理者のみ）
// 教師そのもの（名前・ログインID・ログインできるか）は年度に関係ない
// 受け持ちクラス・担当授業は年度ごとに違うので、?year=2026 の年度（なければ今年度）の分だけを表示・保存する
class TeacherController extends Controller
{
    use UsesAcademicYear; // findYear()（年度がまだ1つもなくても、教師は登録できるようにする）

    public function index(Request $request): View
    {
        $year = $this->findYear($request); // 受け持ち・担当を表示する年度（年度がまだなければ null）
        $yearId = $year?->id;

        // 検索の文字（空のときや、URL をいじって文字以外が送られてきたときは絞り込まない）
        $input = $request->query('q');
        $q = is_string($input) ? trim($input) : '';

        // 空白（全角の空白も）で区切って、キーワードごとに分ける
        // 例：「山田 数学」→ ['山田', '数学']（両方に当てはまる教師だけを出す）
        $keywords = preg_split('/[\s　]+/u', $q, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        // リレーションごとに with() を分ける（1つの配列にまとめると、引数がどちらのリレーションか PHPStan が区別できない）
        // 受け持ちクラス・担当授業は、その年度の分だけ
        $query = Teacher::query()
            ->with('schoolClasses', fn ($c) => $c->where('school_classes.academic_year_id', $yearId)->orderBy('name'))
            ->with('subjects', fn ($s) => $s->where('subjects.academic_year_id', $yearId)->with('schedules')->orderBySchedule());

        // キーワードが、名前・ログインID・受け持ちクラス名・担当授業名のどれかに含まれる教師
        foreach ($keywords as $keyword) {
            $like = "%{$keyword}%";
            $query->where(fn ($w) => $w
                ->where('name', 'like', $like)
                ->orWhere('login_id', 'like', $like)
                ->orWhereHas('schoolClasses', fn ($c) => $c->where('school_classes.academic_year_id', $yearId)->where('name', 'like', $like))
                ->orWhereHas('subjects', fn ($s) => $s->where('subjects.academic_year_id', $yearId)->where('name', 'like', $like)));
        }

        return view('admin.teachers.index', [
            'year' => $year,
            'teachers' => $query->orderBy('name')->get(),
            'q' => $q,
            'total' => Teacher::count(), // 検索に関係なく、登録されている教師の人数
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.teachers.form', $this->formData($this->findYear($request), new Teacher(['can_login' => true])));
    }

    public function store(Request $request): RedirectResponse
    {
        $year = $this->findYear($request);
        [$data, $classIds, $subjectIds] = $this->validated($request, $year);

        $teacher = DB::transaction(function () use ($year, $data, $classIds, $subjectIds) {
            $teacher = Teacher::create($data);
            $this->syncAssignments($teacher, $year, $classIds, $subjectIds);

            return $teacher;
        });

        return redirect()->route('admin.teachers.index', ['year' => $year?->year])->with('status', "{$teacher->name} 先生を登録しました。");
    }

    public function edit(Request $request, Teacher $teacher): View
    {
        $teacher->load(['schoolClasses', 'subjects']);

        return view('admin.teachers.form', $this->formData($this->findYear($request), $teacher));
    }

    public function update(Request $request, Teacher $teacher): RedirectResponse
    {
        $year = $this->findYear($request);
        [$data, $classIds, $subjectIds] = $this->validated($request, $year, $teacher);

        DB::transaction(function () use ($teacher, $year, $data, $classIds, $subjectIds) {
            $teacher->update($data);
            $this->syncAssignments($teacher, $year, $classIds, $subjectIds);
        });

        return redirect()->route('admin.teachers.index', ['year' => $year?->year])->with('status', '保存しました。');
    }

    public function destroy(Teacher $teacher): RedirectResponse
    {
        // 受け持ち・担当の設定は一緒に削除される。
        // この教師が手動で登録した出席記録は残り、「登録した教師」だけが空になる
        // （過去の受け持ちの記録も消えるので、退職した教師は削除せずに「ログインできる」を外すのがおすすめ）
        $teacher->delete();

        return redirect()->route('admin.teachers.index')->with('status', '削除しました。');
    }

    // 受け持ちクラス・担当授業を、その年度の分だけ入れ替える（ほかの年度の分はそのまま残す）
    // 年度がない、または読み取り専用の年度なら、何も変えない（画面でもチェックボックスを押せなくしている）
    /**
     * @param  array<int, int>  $classIds
     * @param  array<int, int>  $subjectIds
     */
    private function syncAssignments(Teacher $teacher, ?AcademicYear $year, array $classIds, array $subjectIds): void
    {
        if ($year === null || ! $year->isEditable()) {
            return;
        }

        $this->syncInYear($teacher->schoolClasses(), $year->schoolClasses()->pluck('id'), $classIds);
        $this->syncInYear($teacher->subjects(), $year->subjects()->pluck('id'), $subjectIds);

        // 担当から外した授業では、曜日ごとの「教室と担当教師」からも外す（その欄は「未設定」に戻る）
        // （授業の画面では、担当教師に選んだ教師しか選べないので、合わせておく）
        SubjectScheduleRoom::query()
            ->where('teacher_id', $teacher->id)
            ->whereHas('schedule.subject', fn ($q) => $q
                ->where('academic_year_id', $year->id)
                ->whereNotIn('subjects.id', $subjectIds))
            ->update(['teacher_id' => null]);
    }

    // $relation のうち、$yearIds（その年度のクラスや授業）の分だけを $selectedIds に入れ替える
    // （sync() だと、ほかの年度の受け持ちまで外れて、去年の記録が消えてしまう）
    /**
     * @param  BelongsToMany<SchoolClass, Teacher>|BelongsToMany<Subject, Teacher>  $relation
     * @param  Collection<int, int>  $yearIds
     * @param  array<int, int>  $selectedIds
     */
    private function syncInYear(BelongsToMany $relation, Collection $yearIds, array $selectedIds): void
    {
        $relation->detach($yearIds->diff($selectedIds)->values()->all());
        $relation->syncWithoutDetaching($selectedIds);
    }

    /** @return array<string, mixed> */
    private function formData(?AcademicYear $year, Teacher $teacher): array
    {
        // 入力エラーで戻ってきたときは、さっき選んでいたチェック状態を使う
        // （前回の入力は文字列や null のこともあるので、(array) で必ず配列にする）
        $hasOld = session()->hasOldInput();
        $yearId = $year?->id;

        return [
            'year' => $year,
            'teacher' => $teacher,
            // 選べるのは、その年度のクラス・授業だけ
            'classes' => SchoolClass::where('academic_year_id', $yearId)->orderBy('grade')->orderBy('name')->get(),
            'subjects' => Subject::with('schedules')->where('subjects.academic_year_id', $yearId)->orderBySchedule()->get(),
            'selectedClassIds' => array_map('intval', $hasOld
                ? (array) old('class_ids', [])
                : ($teacher->exists ? $teacher->schoolClasses->where('academic_year_id', $yearId)->pluck('id')->all() : [])),
            'selectedSubjectIds' => array_map('intval', $hasOld
                ? (array) old('subject_ids', [])
                : ($teacher->exists ? $teacher->subjects->where('academic_year_id', $yearId)->pluck('id')->all() : [])),
        ];
    }

    // [教師の項目, クラスIDの配列, 授業IDの配列] を返す
    /** @return array{array<string, mixed>, array<int, int>, array<int, int>} */
    private function validated(Request $request, ?AcademicYear $year, ?Teacher $teacher = null): array
    {
        $yearId = $year?->id;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'login_id' => ['required', 'string', 'max:50', Rule::unique('teachers')->ignore($teacher)],
            // 新規登録では必須。編集では空欄なら今のパスワードのまま
            'password' => [$teacher ? 'nullable' : 'required', 'string', 'min:8', 'max:100'],
            'class_ids' => ['nullable', 'array'],             // 受け持ちクラスは0個でもよい
            // その年度のクラス・授業だけ
            'class_ids.*' => ['integer', 'distinct', Rule::exists('school_classes', 'id')->where('academic_year_id', $yearId)],
            'subject_ids' => ['nullable', 'array'],           // 担当授業も0個でもよい（あとから授業の画面でも設定できる）
            'subject_ids.*' => ['integer', 'distinct', Rule::exists('subjects', 'id')->where('academic_year_id', $yearId)],
        ], [], [
            'class_ids.*' => '受け持ちクラス',
            'subject_ids.*' => '担当授業',
        ]);

        $classIds = array_map('intval', $data['class_ids'] ?? []);
        $subjectIds = array_map('intval', $data['subject_ids'] ?? []); // 1つもチェックしていなければ送られてこないので、空の配列にする
        unset($data['class_ids'], $data['subject_ids']);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        // ログインできるか（チェックを外すと、ログイン中の教師も次の操作でログアウトになる）
        $data['can_login'] = $request->boolean('can_login');

        return [$data, $classIds, $subjectIds];
    }
}
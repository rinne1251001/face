<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\SortsList;
use App\Http\Controllers\Concerns\UsesAcademicYear;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Services\FaceMatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// 生徒の一覧・追加・編集・削除（管理者のみ）
// クラス・出席番号・学年・受講する授業は年度ごとに違うので、どの画面も「どの年度か」を ?year=2026 で受け取る
//   （フォームでは hidden の year。指定がなければ今年度）
// 過去の年度は読み取り専用。年度の画面で「編集を許可」したときだけ変更できる
class StudentController extends Controller
{
    use SortsList, UsesAcademicYear;

    public function __construct(private FaceMatcher $matcher) {}

    // 一覧：選んだ年度に在籍している生徒だけを出す（卒業生は、過去の年度を選ぶと見える）
    public function index(Request $request): Response|RedirectResponse
    {
        if (! AcademicYear::query()->exists()) {
            return redirect()->route('admin.years.index')->with('status', '先に「年度更新」から年度を登録してください。');
        }

        $year = $this->yearOrFail($request);

        $input = $request->query('q');
        $q = is_string($input) ? trim($input) : '';
        // クラス：'none' ならクラス未定の生徒だけ。数字ならそのクラス。どちらでもなければ 0（すべて）
        $classId = $request->query('class_id') === 'none' ? 'none' : $request->integer('class_id');
        $faces = $request->query('faces');           // 'registered'（登録済）か 'unregistered'（未登録）
        if (! in_array($faces, ['registered', 'unregistered'], true)) {
            $faces = '';                             // それ以外は「すべて」
        }

        $query = Student::query()
            // その年度の在籍（クラス・出席番号）も一緒に読み込む。ビューでは $student->enrollmentFor($year) で使う
            ->with(['enrollments' => fn ($e) => $e->where('academic_year_id', $year->id)->with('schoolClass')])
            ->withCount('faceSamples')
            // その年度に在籍している生徒だけ（クラスを選んだときは、その年度のそのクラスの生徒だけ）
            ->whereHas('enrollments', fn ($e) => $e
                ->where('academic_year_id', $year->id)
                ->when($classId === 'none', fn ($e) => $e->whereNull('school_class_id'))
                ->when(is_int($classId) && $classId > 0, fn ($e) => $e->where('school_class_id', $classId)))
            // キーワード（空なら絞り込まない：名前・フリガナ・学籍番号・出席番号・クラス名）
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('name', 'like', "%{$q}%")
                ->orWhere('furigana', 'like', "%{$q}%")
                ->orWhere('student_number', 'like', "%{$q}%")
                // 出席番号とクラス名は、その年度の在籍で探す
                ->orWhereHas('enrollments', fn ($e) => $e
                    ->where('academic_year_id', $year->id)
                    ->where(fn ($e) => $e
                        ->where('attend_number', 'like', "%{$q}%")
                        ->orWhereHas('schoolClass', fn ($c) => $c->where('name', 'like', "%{$q}%"))))))
            // 顔写真
            ->when($faces === 'registered', fn ($query) => $query->has('faceSamples'))
            ->when($faces === 'unregistered', fn ($query) => $query->doesntHave('faceSamples'));

        // 並び替え（何も指定がなければクラス順）
        [$sort, $direction] = $this->applySort($query, $request, $this->sortColumns($year), 'class');

        $students = $query
            ->orderBy('student_number') // 同じ値どうしは学籍番号順（同じクラスの中など）
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();       // ページ送りのリンクにも、年度・検索・並び替えの条件を付ける

        // Ajax（並び替え・ページ送り）のときは、一覧の表の部分だけを返す
        $view = $request->ajax() ? 'admin.students.partials.list' : 'admin.students.index';

        return response()
            ->view($view, [
                'year' => $year,
                'years' => AcademicYear::query()->orderByDesc('year')->get(), // 年度の切り替えの選択肢
                'classes' => $this->classesOf($year),                          // クラスの絞り込みの選択肢（その年度のクラスだけ）
                ...compact('students', 'q', 'classId', 'faces', 'sort', 'direction'),
            ])
            // 同じ URL で「ページ全体」と「表だけ」を返し分けるので、ブラウザのキャッシュで混ざらないようにする
            ->header('Vary', 'X-Requested-With');
    }

    // 一覧で並び替えできる列（URL の ?sort= に使うキー => 並び替えに使う列）
    // 並び替えを増やすときは、ここに1行足して、ビューの見出しを partials.sort-header にするだけ
    /** @return array<string, mixed> */
    private function sortColumns(AcademicYear $year): array
    {
        return [
            'student_number' => 'student_number',
            'name'           => 'furigana',
            // 出席番号とクラス名は別のテーブル（その年度の在籍）にあるので、サブクエリで取り出して並び替える
            // 出席番号は文字列なので、数値に変換してから並べる（9 → 11 の順になるように）
            'attend_number'  => Enrollment::selectRaw(Enrollment::ATTEND_NUMBER_AS_NUMBER)
                ->whereColumn('enrollments.student_id', 'students.id')
                ->where('enrollments.academic_year_id', $year->id),
            'class'          => SchoolClass::select('school_classes.name')
                ->join('enrollments', 'enrollments.school_class_id', '=', 'school_classes.id')
                ->whereColumn('enrollments.student_id', 'students.id')
                ->where('enrollments.academic_year_id', $year->id),
        ];
    }

    public function create(Request $request): View
    {
        $year = $this->yearOrFail($request);
        $this->ensureEditable($year);

        return view('admin.students.form', $this->formData($year, new Student(['is_active' => true]), new Enrollment(['grade' => 1])));
    }

    public function store(Request $request): RedirectResponse
    {
        $year = $this->yearOrFail($request);
        $this->ensureEditable($year);
        [$studentData, $enrollmentData, $subjectIds] = $this->validated($request, $year);

        $student = DB::transaction(function () use ($year, $studentData, $enrollmentData, $subjectIds) {
            $student = Student::create($studentData);
            $student->enrollments()->create($enrollmentData + ['academic_year_id' => $year->id]);
            $this->syncSubjects($student, $year, $subjectIds);

            return $student;
        });

        // 「保存して顔写真を登録する」ボタンなら顔の登録画面へ、「保存のみ」なら一覧へ
        if ($request->input('next') === 'faces') {
            return redirect()->route('admin.faces.create', $student)
                ->with('status', '生徒を登録しました。続けて顔写真を登録してください。');
        }

        return redirect()->route('admin.students.index', ['year' => $year->year])
            ->with('status', "{$student->name} さんを登録しました。顔写真は一覧の「顔を登録」から後で登録できます。");
    }

    // 一斉追加の画面
    public function bulkCreate(Request $request): View
    {
        $year = $this->yearOrFail($request);
        $this->ensureEditable($year);

        return view('admin.students.bulk', [
            'year'        => $year,
            'departments' => Department::orderBy('id')->get(),
            'maxGrade'    => Department::maxYears(),
            'classes'     => $this->classesOf($year),
            'subjects'    => $this->subjectsOf($year),
        ]);
    }

    // 一斉追加の保存（1人でもエラーがあれば、誰も登録しない）
    // 学科と学年は全員共通。クラスと出席番号は、行ごとに入れても空（未定）のままでもよい
    public function bulkStore(Request $request): RedirectResponse
    {
        $year = $this->yearOrFail($request);
        $this->ensureEditable($year);

        // 名前も学籍番号も空の行は無視する（クラスだけ選ばれている行も無視）
        $rows = $request->collect('students')
            ->filter(fn ($row) => is_array($row) && (filled($row['name'] ?? null) || filled($row['student_number'] ?? null)))
            ->values()
            ->all();
        $request->merge(['students' => $rows]);

        $maxGrade = Department::maxYears($request->integer('department_id'));

        $rules = [
            'department_id'              => ['required', 'integer', 'exists:departments,id'],
            'grade'                      => ['required', 'integer', "between:1,{$maxGrade}"],
            'students'                   => ['required', 'array', 'max:200'],
            'students.*.name'            => ['required', 'string', 'max:100'],
            'students.*.furigana'        => ['required', 'string', 'max:300'],
            // distinct:strict … 表の中での重複チェック（strict がないと "001" と "1" が同じ扱いになる）
            'students.*.student_number'  => ['required', 'string', 'max:20', 'distinct:strict,ignore_case', 'unique:students,student_number'],
            // クラスは未定（空）でもよい。選ぶなら、この年度のクラスだけ
            'students.*.school_class_id' => ['nullable', 'integer', Rule::exists('school_classes', 'id')->where('academic_year_id', $year->id)],
            'subject_ids'                => ['nullable', 'array'], // 受講する授業は空でも可
            'subject_ids.*'              => ['integer', 'distinct', Rule::exists('subjects', 'id')->where('academic_year_id', $year->id)],
        ];
        $messages = [
            'students.required'                  => '名前と学籍番号を1人分以上入力してください。',
            'students.*.student_number.distinct' => ':position行目の学籍番号「:input」が表の中で重複しています。',
            'students.*.student_number.unique'   => ':position行目の学籍番号「:input」は既に登録されています。',
            'grade.between'                      => '学年は、学科の修業年限（:max年）までで選んでください。',
        ];
        $attributes = [
            // :position は「何行目か」（1から数える）に置き換わる
            'department_id'              => '学科',
            'grade'                      => '学年',
            'students'                   => '生徒',
            'students.*.name'            => ':position行目の名前',
            'students.*.furigana'        => ':position行目のフリガナ',
            'students.*.student_number'  => ':position行目の学籍番号',
            'students.*.school_class_id' => ':position行目のクラス',
            'subject_ids'                => '受講する授業',
            'subject_ids.*'              => '受講する授業',
        ];

        // 出席番号は「その行のクラスの中で」重複しないかを見るので、行ごとにルールを作る
        foreach ($rows as $i => $row) {
            $classId = (int) ($row['school_class_id'] ?? 0);
            $line = $i + 1;
            $rules["students.{$i}.attend_number"] = [
                'nullable', 'string', 'max:20',
                Rule::prohibitedIf($classId === 0), // クラスが未定なら、出席番号も入れられない
                Rule::unique('enrollments', 'attend_number')->where('school_class_id', $classId),
            ];
            $messages["students.{$i}.attend_number.prohibited"] = "{$line}行目の出席番号は、クラスを選んでから入力してください。";
            $messages["students.{$i}.attend_number.unique"] = "{$line}行目の出席番号「:input」の生徒は、そのクラスに既にいます。";
            $attributes["students.{$i}.attend_number"] = "{$line}行目の出席番号";
        }

        $validator = Validator::make($request->all(), $rules, $messages, $attributes);

        // 表の中で、同じクラス・同じ出席番号の行が2つないか
        $validator->after(function ($validator) use ($rows) {
            $seen = []; // 「クラスID|出席番号」 => 最初に出てきた行の番号
            foreach ($rows as $i => $row) {
                if (blank($row['school_class_id'] ?? null) || blank($row['attend_number'] ?? null)) {
                    continue;
                }
                $key = $row['school_class_id'].'|'.$row['attend_number'];
                if (isset($seen[$key])) {
                    $validator->errors()->add("students.{$i}.attend_number",
                        ($i + 1)."行目の出席番号「{$row['attend_number']}」が、".($seen[$key] + 1).'行目と同じクラスで重複しています。');
                } else {
                    $seen[$key] = $i;
                }
            }
        });

        $data = $validator->validate();
        $subjectIds = array_map('intval', $data['subject_ids'] ?? []);
        $isActive = $request->boolean('is_active');

        DB::transaction(function () use ($year, $data, $subjectIds, $isActive) {
            foreach ($data['students'] as $row) {
                $student = Student::create(Arr::only($row, ['name', 'furigana', 'student_number']) + [
                    'department_id' => $data['department_id'],
                    'is_active'     => $isActive,
                ]);
                $student->enrollments()->create([
                    'academic_year_id' => $year->id,
                    'grade'            => $data['grade'],
                    'school_class_id'  => $row['school_class_id'] ?? null,
                    'attend_number'    => $row['attend_number'] ?? null,
                ]);
                $student->subjects()->sync($subjectIds); // 新しい生徒なので、ほかの年度の授業は付いていない
            }
        });

        $count = count($data['students']);

        return redirect()->route('admin.students.index', ['year' => $year->year])
            ->with('status', "{$count}人の生徒を登録しました。顔写真は一覧の「顔を登録」から登録してください。");
    }

    // 編集画面（読み取り専用の年度なら、見るだけの画面になる）
    public function edit(Request $request, Student $student): View
    {
        $year = $this->yearOfStudent($request, $student);
        $enrollment = $this->enrollmentOrFail($student, $year);

        $student->load([
            'faceSamples' => fn ($q) => $q->select(['id', 'student_id', 'created_at']),
            'subjects',
        ]);

        return view('admin.students.form', $this->formData($year, $student, $enrollment));
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $year = $this->yearOfStudent($request, $student);
        $this->ensureEditable($year);
        $enrollment = $this->enrollmentOrFail($student, $year);
        [$studentData, $enrollmentData, $subjectIds] = $this->validated($request, $year, $student, $enrollment);

        DB::transaction(function () use ($student, $enrollment, $year, $studentData, $enrollmentData, $subjectIds) {
            $student->update($studentData);
            $enrollment->update($enrollmentData);
            $this->syncSubjects($student, $year, $subjectIds);
        });
        $this->matcher->flush(); // 照合の対象（有効/停止）が変わることがあるため

        return redirect()->route('admin.students.edit', [$student, 'year' => $year->year])->with('status', '保存しました。');
    }

    public function destroy(Request $request, Student $student): RedirectResponse
    {
        $year = $this->yearOfStudent($request, $student); // 削除したあとに戻る一覧の年度

        // 読み取り専用の年度に記録がある生徒は削除しない（削除すると、過去の年度の在籍や出席記録まで消えてしまうため）
        $lockedYears = AcademicYear::locked()
            ->whereHas('enrollments', fn ($e) => $e->where('student_id', $student->id))
            ->orderBy('year')
            ->get();
        if ($lockedYears->isNotEmpty()) {
            return back()->with('error', $lockedYears->pluck('label')->join('・')
                .'の記録があるため、削除できません。照合から外すだけなら「照合の対象にする」のチェックを外してください。');
        }

        Storage::disk('local')->deleteDirectory("faces/{$student->id}");
        $student->delete(); // 顔データ・在籍・受講授業・出席記録は外部キー制約で一緒に削除される
        $this->matcher->flush();

        return redirect()->route('admin.students.index', ['year' => $year->year])->with('status', '削除しました。');
    }

    // 生徒の画面の年度。指定がなければ、今年度に在籍していれば今年度、いなければその生徒が在籍していた最後の年度
    // （顔写真の登録画面などから、年度を付けずに編集画面へ来たときのため）
    private function yearOfStudent(Request $request, Student $student): AcademicYear
    {
        if ($request->filled('year')) {
            return $this->yearOrFail($request);
        }

        $current = AcademicYear::current();
        if ($current && $student->enrollments()->where('academic_year_id', $current->id)->exists()) {
            return $current;
        }

        $latest = AcademicYear::query()
            ->whereHas('enrollments', fn ($e) => $e->where('student_id', $student->id))
            ->orderByDesc('year')
            ->first();
        abort_if($latest === null, 404, 'この生徒はどの年度にも在籍していません。');

        return $latest;
    }

    // その年度の在籍（その年度に在籍していない生徒なら 404）
    private function enrollmentOrFail(Student $student, AcademicYear $year): Enrollment
    {
        return $student->enrollments()->where('academic_year_id', $year->id)->firstOrFail();
    }

    // その年度のクラス（学年順 → 名前順）
    /** @return \Illuminate\Database\Eloquent\Collection<int, SchoolClass> */
    private function classesOf(AcademicYear $year)
    {
        return $year->schoolClasses()->orderBy('grade')->orderBy('name')->get();
    }

    // その年度の授業（時間割の順）
    /** @return \Illuminate\Database\Eloquent\Collection<int, Subject> */
    private function subjectsOf(AcademicYear $year)
    {
        return Subject::with('schedules')->where('subjects.academic_year_id', $year->id)->orderBySchedule()->get();
    }

    // 受講する授業を、この年度の分だけ入れ替える
    // （$student->subjects()->sync() だと、ほかの年度の授業まで外れて、去年の受講の記録が消えてしまう）
    /** @param  array<int, int>  $subjectIds */
    private function syncSubjects(Student $student, AcademicYear $year, array $subjectIds): void
    {
        $student->subjects()->detach($year->subjects()->whereNotIn('id', $subjectIds)->pluck('id'));
        $student->subjects()->syncWithoutDetaching($subjectIds);
    }

    // フォーム画面に渡すデータ
    /** @return array<string, mixed> */
    private function formData(AcademicYear $year, Student $student, Enrollment $enrollment): array
    {
        // 入力エラーで戻ってきたときは、さっき選んでいたチェック状態を使う
        $hasOld = session()->hasOldInput();

        return [
            'year'               => $year,
            'student'            => $student,
            'enrollment'         => $enrollment,
            'departments'        => Department::orderBy('id')->get(),
            'maxGrade'           => Department::maxYears(),
            'classes'            => $this->classesOf($year),
            'subjects'           => $this->subjectsOf($year),
            'selectedSubjectIds' => array_map('intval', $hasOld
                ? (array) old('subject_ids', []) // 前回の入力は文字列や null のこともあるので、必ず配列にする
                : ($student->exists ? $student->subjects->where('academic_year_id', $year->id)->pluck('id')->all() : [])),
        ];
    }

    // [生徒の項目, 在籍の項目, 授業IDの配列] を返す
    /** @return array{0: array<string, mixed>, 1: array<string, mixed>, 2: array<int, int>} */
    private function validated(Request $request, AcademicYear $year, ?Student $student = null, ?Enrollment $enrollment = null): array
    {
        $maxGrade = Department::maxYears($request->integer('department_id'));
        $classId = $request->integer('school_class_id'); // 未定なら 0

        $data = $request->validate([
            'name'            => ['required', 'string', 'max:100'],
            'furigana'        => ['required', 'string', 'max:300'],
            'student_number'  => ['required', 'string', 'max:20', Rule::unique('students')->ignore($student)],
            'department_id'   => ['required', 'integer', 'exists:departments,id'],
            'grade'           => ['required', 'integer', "between:1,{$maxGrade}"],
            // クラスは未定でもよい。選ぶなら、この年度のクラスだけ
            'school_class_id' => ['nullable', 'integer', Rule::exists('school_classes', 'id')->where('academic_year_id', $year->id)],
            // 出席番号も未定でもよい。入れるなら、同じクラスの中で重複しない（クラスが未定なら入れられない）
            'attend_number'   => [
                'nullable', 'string', 'max:20',
                Rule::prohibitedIf($classId === 0),
                Rule::unique('enrollments')->where('school_class_id', $classId)->ignore($enrollment),
            ],
            'subject_ids'     => ['nullable', 'array'], // 授業がまだ決まっていない時期もあるので、空でも可
            'subject_ids.*'   => ['integer', 'distinct', Rule::exists('subjects', 'id')->where('academic_year_id', $year->id)],
        ], [
            'grade.between'            => '学年は、学科の修業年限（:max年）までで選んでください。',
            'attend_number.prohibited' => '出席番号は、クラスを選んでから入力してください。',
            'attend_number.unique'     => 'このクラスには、出席番号「:input」の生徒が既にいます。',
        ], [
            'department_id'   => '学科',
            'grade'           => '学年',
            'school_class_id' => 'クラス',
            'attend_number'   => '出席番号',
            'subject_ids'     => '受講する授業',
            'subject_ids.*'   => '受講する授業',
        ]);

        $studentData = Arr::only($data, ['name', 'furigana', 'student_number', 'department_id'])
            + ['is_active' => $request->boolean('is_active')];
        $enrollmentData = [
            'grade'           => $data['grade'],
            'school_class_id' => $data['school_class_id'] ?? null,
            'attend_number'   => $data['attend_number'] ?? null,
        ];

        return [$studentData, $enrollmentData, array_map('intval', $data['subject_ids'] ?? [])];
    }
}
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\StudentCard;
use App\Models\Teacher;
use App\Services\FaceMatcher;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// 年度の一覧と、年度更新（管理者のみ）
// 年度更新：新しい年度を作り、在校生の進級と、教師の引き継ぎ、クラス・授業の名前のコピーをまとめて行う
//   ・クラスと出席番号は、決まっていないので空のまま登録する（あとで生徒ごとに設定する）
//   ・新入生は、ここでは登録しない（あとで生徒の画面から登録する）
//   ・教師は年度ごとに作り直さない。「教師コピー」のチェックを外した教師を、ログインできない設定にするだけ
//   ・クラスと授業は、名前と学年だけを新しい行としてコピーする（教室・担任・時間割・期間はあとで登録する）
class AcademicYearController extends Controller
{
    // 年度の画面（?year=2026 で表示する年度を選ぶ。指定がなければ今年度）
    public function index(Request $request): View
    {
        $years = AcademicYear::query()->orderByDesc('year')->get();

        $selected = $years->firstWhere('year', $request->integer('year'))
            ?? $years->firstWhere('id', AcademicYear::current()?->id)
            ?? $years->first(); // 今年度がまだないときは、いちばん新しい年度

        // その年度のクラス・授業・在籍の件数（$selected->school_classes_count などで使える）
        $selected?->loadCount(['schoolClasses', 'subjects', 'enrollments']);

        return view('admin.years.index', compact('years', 'selected'));
    }

    // 年度更新の画面
    public function create(): View
    {
        $from = AcademicYear::newest(); // コピー元の年度（最初の年度を作るときは null）

        return view('admin.years.create', [
            'from' => $from,
            // 新年度の初期値：コピー元の次の年度。最初の年度なら、今日を含む年度
            'newYear' => $from ? $from->year + 1 : AcademicYear::yearOf(today()),
            'maxGrade' => Department::maxYears(),
            // 進級の対象の生徒（画面の検索と人数の集計に使う。必要な項目だけ渡す）
            'students' => $this->promotionTargets($from)
                ->map(fn (Enrollment $enrollment) => [
                    'id' => $enrollment->student_id,
                    'name' => $enrollment->student->name,
                    'furigana' => $enrollment->student->furigana,
                    'number' => $enrollment->student->student_number,
                    'class' => $enrollment->schoolClass?->name ?? '未定', // クラスがまだ決まっていない生徒もいる
                    'grade' => $enrollment->grade,
                    'years' => $enrollment->student->department->years,
                ])
                ->sortBy('number')
                ->values(),
            // 教師コピーの表に出す教師（今ログインできる教師だけ。すでにログインできない教師は出さない）
            'teachers' => Teacher::query()->where('can_login', true)->orderBy('name')->orderBy('id')->get(['id', 'name', 'login_id']),
            // コピー元のクラスと授業（名前と学年だけ使う）
            'classes' => $from ? $from->schoolClasses()->orderBy('grade')->orderBy('name')->get(['name', 'grade']) : collect(),
            'subjects' => $from ? $from->subjects()->orderBy('grade')->orderBy('name')->get(['name', 'grade']) : collect(),
        ]);
    }

    // 年度更新の登録（途中で1つでも失敗したら、何も登録しない）
    public function store(Request $request, FaceMatcher $matcher): RedirectResponse
    {
        $from = AcademicYear::newest();
        $targets = $this->promotionTargets($from);
        $data = $this->validated($request, $from, $targets);

        [$year, $disabled] = DB::transaction(function () use ($data, $targets) {
            // 1. 年度を作る
            $year = AcademicYear::create(Arr::only($data, ['year', 'start_date', 'end_date']));

            // 2. 在校生の進級（生徒ID => 個別調整）
            $adjustments = collect($data['adjustments'] ?? [])->keyBy('student_id');
            $results = [];  // 年度末の結果 => 在籍IDの一覧（コピー元の年度の在籍に書き込む）
            $next = [];     // 新しい年度の在籍
            $leaving = [];  // 卒業・退学する生徒のID（照合の対象から外す）
            $now = now();

            foreach ($targets as $enrollment) {
                $adjustment = $adjustments->get($enrollment->student_id);

                [$result, $grade] = Enrollment::decideNext(
                    $enrollment->grade,
                    $enrollment->student->department->years, // 何年で卒業かは学科で決まる
                    $adjustment['action'] ?? null,
                    isset($adjustment['grade']) ? (int) $adjustment['grade'] : null,
                );

                $results[$result][] = $enrollment->id;

                // 卒業・退学の生徒は、新しい年度には在籍を作らない
                if ($grade === null) {
                    $leaving[] = $enrollment->student_id;
                } else {
                    $next[] = [
                        'student_id' => $enrollment->student_id,
                        'academic_year_id' => $year->id,
                        'grade' => $grade,
                        // school_class_id と attend_number は入れない（＝ null。まだ決まっていない）
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            foreach ($results as $result => $ids) {
                Enrollment::query()->whereKey($ids)->update(['result' => $result]);
            }
            foreach (array_chunk($next, 500) as $chunk) { // 人数が多くても1回のSQLが長くなりすぎないように分ける
                Enrollment::query()->insert($chunk);
            }
            // 卒業・退学した生徒は、顔照合の対象から外す（記録は残す。生徒の画面でいつでも戻せる）
            // その生徒の入校証も、使用停止にする（入校証そのものや、タッチの記録は消さない）
            foreach (array_chunk($leaving, 500) as $chunk) {
                Student::query()->whereKey($chunk)->update(['is_active' => false]);
                StudentCard::query()->whereIn('student_id', $chunk)->update(['is_active' => false]);
            }

            // 3. 教師：「教師コピー」のチェックを外した教師を、ログインできない設定にする
            //    （教師の行や、過去の受け持ち・担当の記録は消さない。教師の画面の「ログイン可」でいつでも戻せる）
            //    update() は変更した行の数を返す。もともとログインできない教師は数えない
            $disabled = Teacher::query()
                ->whereKey($data['teachers_off'] ?? [])
                ->where('can_login', true)
                ->update(['can_login' => false]);

            // 4. クラスと授業（名前と学年だけの、新しい行として作る。コピー元とは別のクラス・授業になる）
            $year->schoolClasses()->createMany(array_values($data['classes'] ?? []));
            $year->subjects()->createMany(array_values($data['subjects'] ?? []));

            return [$year, $disabled];
        });

        $matcher->flush(); // 照合の対象が変わったので、照合用のキャッシュを作り直す

        return redirect()
            ->route('admin.years.index', ['year' => $year->year])
            ->with('status', "{$year->label}を登録しました。在校生のクラスと出席番号は未定の状態です。"
                .($disabled > 0 ? "教師{$disabled}人をログインできない設定にしました。" : ''));
    }

    // 進級の対象：コピー元の年度の在籍のうち、年度末の結果がまだ決まっていないもの
    /** @return Collection<int, Enrollment> */
    private function promotionTargets(?AcademicYear $from): Collection
    {
        if ($from === null) {
            return new Collection;
        }

        return $from->enrollments()
            ->whereNull('result')
            ->with(['student.department', 'schoolClass'])
            ->get();
    }

    // 過去の年度の編集を許可する（読み取り専用を解除する）
    public function unlock(AcademicYear $academicYear): RedirectResponse
    {
        // 今年度・これからの年度は、もともと編集できる（ここで許可すると、年度が終わっても読み取り専用にならなくなる）
        abort_unless($academicYear->isPast(), 404);

        $academicYear->forceFill(['unlocked_at' => now()])->save();

        return redirect()
            ->route('admin.years.index', ['year' => $academicYear->year])
            ->with('status', "{$academicYear->label}の編集を許可しました。修正が終わったら「読み取り専用に戻す」を押してください。");
    }

    // 読み取り専用に戻す
    public function lock(AcademicYear $academicYear): RedirectResponse
    {
        $academicYear->forceFill(['unlocked_at' => null])->save();

        return redirect()
            ->route('admin.years.index', ['year' => $academicYear->year])
            ->with('status', "{$academicYear->label}を読み取り専用に戻しました。");
    }

    // 入力のチェック。チェック済みの値だけを返す
    /**
     * @param  Collection<int, Enrollment>  $targets
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?AcademicYear $from, Collection $targets): array
    {
        $maxGrade = Department::maxYears();

        // 表の行のうち、登録する行だけを残す（「追加」を押していない予備の行と、コピーのチェックを外した行は除く）
        // 行の番号（キー）は変えない。エラーの「◯行目」が画面の行と合うようにするため
        $rows = fn (string $key) => array_filter(
            (array) $request->input($key, []),
            fn ($row) => is_array($row) && ! empty($row['added']) && ! empty($row['copy']),
        );

        $input = [
            'year' => $request->input('year'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            // 生徒を選んでいない行は無視する
            'adjustments' => array_filter(
                (array) $request->input('adjustments', []),
                fn ($row) => is_array($row) && filled($row['student_id'] ?? null),
            ),
            // 教師コピー：画面に出ていた教師（listed）のうち、チェック（copy）を外した教師のID
            // （画面に出ていなかった教師は、ここに入らないので何も変わらない）
            'teachers_off' => collect((array) $request->input('teachers', []))
                ->filter(fn ($row) => is_array($row) && ! empty($row['listed']) && empty($row['copy']))
                ->keys()
                ->filter(fn ($id) => is_numeric($id))
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all(),
            'classes' => $rows('classes'),
            'subjects' => $rows('subjects'),
        ];

        $rules = [
            'year' => ['required', 'integer', 'between:2000,2100', 'unique:academic_years,year'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'adjustments' => ['array'],
            // 対象の生徒だけ。同じ生徒を2行に書けない
            'adjustments.*.student_id' => ['required', 'integer', 'distinct', Rule::in($targets->pluck('student_id')->all())],
            'adjustments.*.action' => ['required', Rule::in(['repeat', 'withdraw'])],
            'adjustments.*.grade' => ['nullable', 'integer', "between:1,{$maxGrade}"], // 退学のときは送られてこない
            'teachers_off' => ['array', 'max:5000'],
            'teachers_off.*' => ['integer'],
            'classes' => ['array', 'max:200'],
            'classes.*.name' => ['required', 'string', 'max:50', 'distinct:strict,ignore_case'], // 同じ年度に同じ名前のクラスは作れない
            'classes.*.grade' => ['required', 'integer', "between:1,{$maxGrade}"],
            'subjects' => ['array', 'max:500'],
            'subjects.*.name' => ['required', 'string', 'max:100'],
            'subjects.*.grade' => ['required', 'integer', "between:1,{$maxGrade}"],
        ];

        // 期間は、前の年度と重ならないようにする
        if ($from !== null) {
            $rules['start_date'][] = 'after:'.$from->end_date->toDateString();
        }

        return Validator::make($input, $rules, [
            'year.unique' => ':input年度は既に登録されています。',
            'start_date.after' => '開始日は、前の年度の終了日より後にしてください。',
            'end_date.after' => '終了日は、開始日より後にしてください。',
            'adjustments.*.student_id.distinct' => '個別調整に同じ生徒が2回入っています。',
            'adjustments.*.student_id.in' => '個別調整に、進級の対象ではない生徒が入っています。',
            'classes.*.name.required' => ':position行目のクラス名を入力してください（コピーしない行は、チェックを外してください）。',
            'classes.*.name.distinct' => ':position行目のクラス名「:input」が重複しています。',
            'subjects.*.name.required' => ':position行目の授業名を入力してください（コピーしない行は、チェックを外してください）。',
        ], [
            'year' => '新年度',
            'start_date' => '開始日',
            'end_date' => '終了日',
            'adjustments.*.student_id' => '個別調整の生徒',
            'adjustments.*.action' => '個別調整の処理',
            'adjustments.*.grade' => '個別調整の進級学年',
            'teachers_off' => '教師コピー',
            'teachers_off.*' => '教師コピー',
            'classes.*.name' => ':position行目のクラス名',
            'classes.*.grade' => ':position行目のクラスの学年',
            'subjects.*.name' => ':position行目の授業名',
            'subjects.*.grade' => ':position行目の授業の学年',
        ])->validate();
    }
}
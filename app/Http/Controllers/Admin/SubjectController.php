<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\UsesAcademicYear;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Department;
use App\Models\Subject;
use App\Models\SubjectSchedule;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// 授業は年度ごとに別のもの。一覧と追加は ?year=2026 の年度（なければ今年度）、編集はその授業の年度で行う
// 過去の年度は読み取り専用（年度の画面で「編集を許可」したときだけ変更できる）
class SubjectController extends Controller
{
    use UsesAcademicYear; // yearOrFail()・ensureEditable()

    public function index(Request $request): View|RedirectResponse
    {
        if (! AcademicYear::query()->exists()) {
            return redirect()->route('admin.years.index')->with('status', '先に「年度更新」から年度を登録してください。');
        }
        $year = $this->yearOrFail($request);

        $subjects = Subject::query()
            ->where('subjects.academic_year_id', $year->id) // 選んでいる年度の授業だけ
            ->with(['schedules.classroom', 'teachers' => fn ($q) => $q->orderBy('name')])
            ->withCount('students')
            ->orderBySchedule()
            ->get();

        return view('admin.subjects.index', compact('year', 'subjects'));
    }

    public function create(Request $request): View
    {
        $year = $this->yearOrFail($request);
        $this->ensureEditable($year);

        return view('admin.subjects.form', $this->formData($year, new Subject(['grade' => 1])));
    }

    public function store(Request $request): RedirectResponse
    {
        $year = $this->yearOrFail($request);
        $this->ensureEditable($year);
        [$data, $schedules, $teacherIds] = $this->validated($request, $year);

        $subject = DB::transaction(function () use ($year, $data, $schedules, $teacherIds) {
            $subject = $year->subjects()->create($data);
            $subject->schedules()->createMany($schedules);
            $subject->teachers()->sync($teacherIds);

            return $subject;
        });

        return redirect()->route('admin.subjects.index', ['year' => $year->year])
            ->with('status', "「{$subject->name}（{$subject->schedule_label}）」を追加しました。");
    }

    // 編集画面（読み取り専用の年度の授業なら、見るだけの画面になる）
    public function edit(Subject $subject): View
    {
        return view('admin.subjects.form', $this->formData($subject->academicYear, $subject));
    }

    public function update(Request $request, Subject $subject): RedirectResponse
    {
        $year = $subject->academicYear;
        $this->ensureEditable($year);
        [$data, $schedules, $teacherIds] = $this->validated($request, $year);

        DB::transaction(function () use ($subject, $data, $schedules, $teacherIds) {
            $subject->update($data);
            // 曜日・時限は、いったん全部消して作り直す（出席記録は「授業・日付・時限」で残っているので消えない）
            SubjectSchedule::where('subject_id', $subject->id)->delete();
            $subject->schedules()->createMany($schedules);
            $subject->teachers()->sync($teacherIds); // 担当教師（一覧にない教師は外れる）
        });

        return redirect()->route('admin.subjects.index', ['year' => $year->year])->with('status', '保存しました。');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        $year = $subject->academicYear;
        $this->ensureEditable($year);

        // 出席記録が残っている授業は削除できない（記録が消えてしまうため）
        if ($subject->attendances()->exists()) {
            return back()->with('error', "「{$subject->name}」には出席記録があるため削除できません。");
        }

        $subject->delete(); // 生徒の受講・教師の担当の設定は外部キー制約で一緒に削除される

        return redirect()->route('admin.subjects.index', ['year' => $year->year])->with('status', '削除しました。');
    }

    // フォーム画面に渡すデータ（授業と、教室・教師の選択肢）
    /** @return array<string, mixed> */
    private function formData(AcademicYear $year, Subject $subject): array
    {
        // 検索に使う教師の一覧（パスワードなどは渡さない）
        $teachers = Teacher::orderBy('name')->get(['id', 'name', 'login_id']);

        // 選ばれている担当教師（入力エラーで戻ってきたときは、さっき選んでいた教師）
        // （前回の入力は文字列のこともあるので、intval で数字にそろえる）
        $selectedIds = array_map('intval', session()->hasOldInput()
            ? (array) old('teacher_ids', [])
            : ($subject->exists ? $subject->teachers->pluck('id')->all() : []));

        return [
            'year' => $year,
            'maxGrade' => Department::maxYears(),
            'subject' => $subject,
            'classrooms' => Classroom::orderBy('floor')->orderBy('name')->get(), // 階 → 教室名の順
            'teachers' => $teachers,
            'selectedTeachers' => $teachers->whereIn('id', $selectedIds)->values(),
        ];
    }

    // [授業の項目, 曜日・時限・教室の配列, 担当教師IDの配列] を返す
    /** @return array{array<mixed>, array<int, array{day_of_week: int, period_start: int, period_end: int, classroom_id: int|null}>, array<int, int>} */
    private function validated(Request $request, AcademicYear $year): array
    {
        $max = Subject::maxPeriod();
        $maxGrade = Department::maxYears();
        // 授業の期間は、その年度の中だけ（空なら、年度の間ずっと）
        $first = $year->start_date->toDateString();
        $last = $year->end_date->toDateString();

        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'grade' => ['required', 'integer', "between:1,{$maxGrade}"],
            // チェックした曜日（1つ以上）
            'days' => ['sometimes', 'array'],
            'days.*' => ['integer', 'distinct', Rule::in(array_keys(Subject::DAYS))],
            'start_date' => ['nullable', 'date', "after_or_equal:{$first}", "before_or_equal:{$last}"],
            // 終了日は開始日と同じか、それより後
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date', "after_or_equal:{$first}", "before_or_equal:{$last}"],
            // 担当教師（0人でもよい。複数可）
            'teacher_ids' => ['nullable', 'array'],
            'teacher_ids.*' => ['integer', 'distinct', 'exists:teachers,id'],
        ];
        $attributes = [
            'name' => '授業名',
            'grade' => '学年',
            'days' => '曜日',
            'start_date' => '開始日',
            'end_date' => '終了日',
            'teacher_ids' => '担当教師',
            'teacher_ids.*' => '担当教師',
        ];

        // チェックした曜日の時限だけをチェックする（チェックしていない曜日の時限は使わない）
        $checkedDays = array_map('intval', array_filter((array) $request->input('days'), 'is_numeric'));
        foreach (Subject::DAYS as $day => $label) {
            if (in_array($day, $checkedDays, true)) {
                $rules["schedules.$day.period_start"] = ['required', 'integer', "between:1,$max"];
                $rules["schedules.$day.period_end"] = ['required', 'integer', "between:1,$max", "gte:schedules.$day.period_start"];
                // 教室（「未設定」なら空 → null）
                $rules["schedules.$day.classroom_id"] = ['nullable', 'integer', 'exists:classrooms,id'];
            }
            $attributes["schedules.$day.period_start"] = "{$label}曜日の開始時限";
            $attributes["schedules.$day.period_end"] = "{$label}曜日の終了時限";
            $attributes["schedules.$day.classroom_id"] = "{$label}曜日の教室";
        }

        $range = $year->start_date->format('Y/m/d').'～'.$year->end_date->format('Y/m/d');
        $data = $request->validate($rules, [
            'start_date.after_or_equal' => "開始日は、{$year->label}（{$range}）の中から選んでください。",
            'start_date.before_or_equal' => "開始日は、{$year->label}（{$range}）の中から選んでください。",
            'end_date.before_or_equal' => "終了日は、{$year->label}（{$range}）の中から選んでください。",
        ], $attributes);

        $schedules = collect(Arr::array($data, 'days', [])) // Arr::array … 配列として取り出す（配列でなければ例外）
            ->map(fn ($day) => [
                'day_of_week' => (int) $day,
                'period_start' => (int) $data['schedules'][$day]['period_start'],
                'period_end' => (int) $data['schedules'][$day]['period_end'],
                'classroom_id' => isset($data['schedules'][$day]['classroom_id'])
                    ? (int) $data['schedules'][$day]['classroom_id']
                    : null,
            ])
            ->sortBy('day_of_week')
            ->values()
            ->all();

        // 担当教師のID（1人も選んでいなければ空の配列 → sync() で全員外れる）
        $teacherIds = array_map('intval', (array) ($data['teacher_ids'] ?? []));

        return [Arr::only($data, ['name', 'grade', 'start_date', 'end_date']), $schedules, $teacherIds];
    }
}
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\UsesAcademicYear;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Department;
use App\Models\Enrollment;
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
            ->with([
                'schedules',
                'teachers' => fn ($q) => $q->orderBy('name'),
                'schoolClasses' => fn ($q) => $q->orderBy('name'),
            ])
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
        [$data, $schedules, $teacherIds, $classIds] = $this->validated($request, $year);

        [$subject, $added] = DB::transaction(function () use ($year, $data, $schedules, $teacherIds, $classIds) {
            $subject = $year->subjects()->create($data);
            $subject->teachers()->sync($teacherIds);
            $this->saveSchedules($subject, $schedules);

            return [$subject, $this->syncClasses($subject, $classIds)];
        });

        return redirect()->route('admin.subjects.index', ['year' => $year->year])
            ->with('status', "「{$subject->name}（{$subject->schedule_label}）」を追加しました。".$this->addedMessage($added));
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
        [$data, $schedules, $teacherIds, $classIds] = $this->validated($request, $year);

        $added = DB::transaction(function () use ($subject, $data, $schedules, $teacherIds, $classIds) {
            $subject->update($data);
            $subject->teachers()->sync($teacherIds); // 担当教師（一覧にない教師は外れる）
            // 曜日・時限は、いったん全部消して作り直す（出席記録は「授業・日付・時限」で残っているので消えない）
            // 教室と担当教師の行（subject_schedule_rooms）は、外部キー制約で一緒に消える
            SubjectSchedule::where('subject_id', $subject->id)->delete();
            $this->saveSchedules($subject, $schedules);

            return $this->syncClasses($subject, $classIds);
        });

        return redirect()->route('admin.subjects.index', ['year' => $year->year])
            ->with('status', '保存しました。'.$this->addedMessage($added));
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        $year = $subject->academicYear;
        $this->ensureEditable($year);

        // 出席記録が残っている授業は削除できない（記録が消えてしまうため）
        if ($subject->attendances()->exists()) {
            return back()->with('error', "「{$subject->name}」には出席記録があるため削除できません。");
        }

        $subject->delete(); // 生徒の受講・教師の担当・クラスの設定は外部キー制約で一緒に削除される

        return redirect()->route('admin.subjects.index', ['year' => $year->year])->with('status', '削除しました。');
    }

    // 曜日・時限と、その曜日の「教室と担当教師」の行を保存する
    /** @param array<int, array{day_of_week: int, period_start: int, period_end: int, rooms: array<int, array{school_class_id: int|null, classroom_id: int|null, teacher_id: int|null}>}> $schedules */
    private function saveSchedules(Subject $subject, array $schedules): void
    {
        foreach ($schedules as $schedule) {
            $subject->schedules()
                ->create(Arr::except($schedule, 'rooms'))
                ->rooms()
                ->createMany($schedule['rooms']);
        }
    }

    // 授業を受けるクラスを保存して、そのクラスの生徒を受講生にそろえる。新しく受講生になった人数を返す
    //   ・選んだクラスに在籍している生徒は、全員この授業の受講生にする（すでに受講生の生徒はそのまま）
    //   ・外したクラスの生徒は、受講生からも外す（出席記録は消えない）
    //   ・クラスを選んでいない授業は何も変えない（受講生は「生徒」の画面で1人ずつ設定する）
    /** @param array<int, int> $classIds */
    private function syncClasses(Subject $subject, array $classIds): int
    {
        $changes = $subject->schoolClasses()->sync($classIds);

        // クラスは年度ごとに別の行なので、クラスの在籍 ＝ その年度の、そのクラスの生徒
        if ($changes['detached'] !== []) {
            $subject->students()->detach(Enrollment::whereIn('school_class_id', $changes['detached'])->pluck('student_id'));
        }
        $added = $subject->students()->syncWithoutDetaching(Enrollment::whereIn('school_class_id', $classIds)->pluck('student_id'));

        return count($added['attached']);
    }

    // 保存のあとのメッセージに足す文（クラスの生徒を受講生にしたとき）
    private function addedMessage(int $added): string
    {
        return $added > 0 ? "選んだクラスの生徒 {$added}人を受講生にしました。" : '';
    }

    // フォーム画面に渡すデータ（授業と、教師・クラス・教室の選択肢）
    /** @return array<string, mixed> */
    private function formData(AcademicYear $year, Subject $subject): array
    {
        $hasOld = session()->hasOldInput(); // 入力エラーで戻ってきたか

        // 検索に使う教師の一覧（パスワードなどは渡さない）
        $teachers = Teacher::orderBy('name')->get(['id', 'name', 'login_id']);
        // 検索に使うクラスの一覧（その年度のクラスだけ。学年 → クラス名の順）
        $classes = $year->schoolClasses()->withCount('enrollments')->orderBy('grade')->orderBy('name')->get();

        // 選ばれている担当教師・クラス（入力エラーで戻ってきたときは、さっき選んでいたもの）
        // （前回の入力は文字列のこともあるので、intval で数字にそろえる）
        $teacherIds = array_map('intval', $hasOld
            ? (array) old('teacher_ids', [])
            : ($subject->exists ? $subject->teachers->pluck('id')->all() : []));
        $classIds = array_map('intval', $hasOld
            ? (array) old('class_ids', [])
            : ($subject->exists ? $subject->schoolClasses->pluck('id')->all() : []));

        return [
            'year' => $year,
            'maxGrade' => Department::maxYears(),
            'subject' => $subject,
            'classrooms' => Classroom::orderBy('floor')->orderBy('name')->get(), // 階 → 教室名の順
            'selectedTeachers' => $teachers->whereIn('id', $teacherIds)->values(),
            'selectedClasses' => $classes->whereIn('id', $classIds)->values(),
            // ---- ここから下は JavaScript に渡す（@json） ----
            'teacherOptions' => $teachers->map->only(['id', 'name', 'login_id'])->values(),
            'classOptions' => $classes->map(fn ($class) => [
                'id' => $class->id,
                'name' => $class->name,
                'grade' => $class->grade,
                'classroom_id' => $class->classroom_id,   // ホームルームの教室（クラスを選んだときの、教室の初期値）
                'students' => $class->enrollments_count,  // 生徒の人数
            ])->values(),
            'rooms' => $this->roomsForForm($subject, $hasOld),
        ];
    }

    // 画面に出す「教室と担当教師」の行（曜日 => 行の配列）
    // 入力エラーで戻ってきたときは、さっきの入力。そうでなければ、保存してある行
    /** @return array<int, array<int, array{school_class_id: int|null, classroom_id: int|null, teacher_id: int|null}>> */
    private function roomsForForm(Subject $subject, bool $hasOld): array
    {
        $rooms = [];
        if ($hasOld) {
            foreach (Arr::wrap(old('schedules')) as $day => $schedule) {
                foreach (Arr::wrap($schedule['rooms'] ?? []) as $room) {
                    $rooms[(int) $day][] = $this->roomRow(Arr::wrap($room));
                }
            }
        } elseif ($subject->exists) {
            foreach ($subject->schedules()->with('rooms')->get() as $schedule) {
                $rooms[$schedule->day_of_week] = $schedule->rooms
                    ->map(fn ($room) => $this->roomRow($room->only(['school_class_id', 'classroom_id', 'teacher_id'])))
                    ->all();
            }
        }

        return $rooms;
    }

    // 1行分の値を、数字（選んでいなければ null）にそろえる
    /**
     * @param  array<mixed>  $room
     * @return array{school_class_id: int|null, classroom_id: int|null, teacher_id: int|null}
     */
    private function roomRow(array $room): array
    {
        $id = fn (string $key) => is_numeric($room[$key] ?? null) ? (int) $room[$key] : null;

        return ['school_class_id' => $id('school_class_id'), 'classroom_id' => $id('classroom_id'), 'teacher_id' => $id('teacher_id')];
    }

    // [授業の項目, 曜日・時限（と教室・担当教師の行）の配列, 担当教師IDの配列, クラスIDの配列] を返す
    /** @return array{array<mixed>, array<int, array{day_of_week: int, period_start: int, period_end: int, rooms: array<int, array{school_class_id: int|null, classroom_id: int|null, teacher_id: int|null}>}>, array<int, int>, array<int, int>} */
    private function validated(Request $request, AcademicYear $year): array
    {
        $max = Subject::maxPeriod();
        $maxGrade = Department::maxYears();
        // 授業の期間は、その年度の中だけ（空なら、年度の間ずっと）
        $first = $year->start_date->toDateString();
        $last = $year->end_date->toDateString();

        // 画面で選んだ担当教師・クラス（下の「教室と担当教師」の行は、この中からしか選べない）
        $pickedTeacherIds = $this->numericIds($request->input('teacher_ids'));
        $pickedClassIds = $this->numericIds($request->input('class_ids'));

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
            // クラス（0個でもよい。複数可）。その授業の年度のクラスだけ
            'class_ids' => ['nullable', 'array'],
            'class_ids.*' => ['integer', 'distinct', Rule::exists('school_classes', 'id')->where('academic_year_id', $year->id)],
        ];
        $attributes = [
            'name' => '授業名',
            'grade' => '学年',
            'days' => '曜日',
            'start_date' => '開始日',
            'end_date' => '終了日',
            'teacher_ids' => '担当教師',
            'teacher_ids.*' => '担当教師',
            'class_ids' => 'クラス',
            'class_ids.*' => 'クラス',
        ];

        // チェックした曜日の時限・教室・担当教師だけをチェックする（チェックしていない曜日の分は使わない）
        $checkedDays = array_map('intval', array_filter((array) $request->input('days'), 'is_numeric'));
        foreach (Subject::DAYS as $day => $label) {
            if (in_array($day, $checkedDays, true)) {
                $rules["schedules.$day.period_start"] = ['required', 'integer', "between:1,$max"];
                $rules["schedules.$day.period_end"] = ['required', 'integer', "between:1,$max", "gte:schedules.$day.period_start"];
                // 教室と担当教師の行（0行でもよい。「未設定」なら空 → null）
                $rules["schedules.$day.rooms"] = ['nullable', 'array', 'max:50'];
                $rules["schedules.$day.rooms.*.school_class_id"] = ['nullable', 'integer', Rule::in($pickedClassIds)];
                $rules["schedules.$day.rooms.*.classroom_id"] = ['nullable', 'integer', 'exists:classrooms,id'];
                $rules["schedules.$day.rooms.*.teacher_id"] = ['nullable', 'integer', Rule::in($pickedTeacherIds)];
            }
            $attributes["schedules.$day.period_start"] = "{$label}曜日の開始時限";
            $attributes["schedules.$day.period_end"] = "{$label}曜日の終了時限";
            $attributes["schedules.$day.rooms"] = "{$label}曜日の教室と担当教師";
            $attributes["schedules.$day.rooms.*.school_class_id"] = "{$label}曜日のクラス";
            $attributes["schedules.$day.rooms.*.classroom_id"] = "{$label}曜日の教室";
            $attributes["schedules.$day.rooms.*.teacher_id"] = "{$label}曜日の担当教師";
        }

        $range = $year->start_date->format('Y/m/d').'～'.$year->end_date->format('Y/m/d');
        $data = $request->validate($rules, [
            'start_date.after_or_equal' => "開始日は、{$year->label}（{$range}）の中から選んでください。",
            'start_date.before_or_equal' => "開始日は、{$year->label}（{$range}）の中から選んでください。",
            'end_date.before_or_equal' => "終了日は、{$year->label}（{$range}）の中から選んでください。",
            'schedules.*.rooms.*.school_class_id.in' => ':attributeは、上の「クラス」で選んだクラスにしてください。',
            'schedules.*.rooms.*.teacher_id.in' => ':attributeは、上の「担当教師」で選んだ教師から選んでください。',
        ], $attributes);

        // クラス・担当教師のID（1つも選んでいなければ空の配列 → sync() で全部外れる）
        $teacherIds = array_map('intval', (array) ($data['teacher_ids'] ?? []));
        $classIds = array_map('intval', (array) ($data['class_ids'] ?? []));

        $schedules = collect(Arr::array($data, 'days', [])) // Arr::array … 配列として取り出す（配列でなければ例外）
            ->map(fn ($day) => [
                'day_of_week' => (int) $day,
                'period_start' => (int) $data['schedules'][$day]['period_start'],
                'period_end' => (int) $data['schedules'][$day]['period_end'],
                'rooms' => $this->roomRows(Arr::wrap($data['schedules'][$day]['rooms'] ?? []), $classIds),
            ])
            ->sortBy('day_of_week')
            ->values()
            ->all();

        return [Arr::only($data, ['name', 'grade', 'start_date', 'end_date']), $schedules, $teacherIds, $classIds];
    }

    // 1つの曜日の「教室と担当教師」の行を、保存する形にそろえる
    //   ・クラスの行 … 選んだクラスごとに必ず1行（教室・教師が未設定でも保存する。「未設定にした」ことを覚えておくため）
    //   ・教室だけの行 … 教室か教師のどちらかを選んである行だけ（何も選んでいない行は保存しない）
    /**
     * @param  array<mixed>  $rows
     * @param  array<int, int>  $classIds
     * @return array<int, array{school_class_id: int|null, classroom_id: int|null, teacher_id: int|null}>
     */
    private function roomRows(array $rows, array $classIds): array
    {
        $rows = collect($rows)->map(fn ($row) => $this->roomRow(Arr::wrap($row)));

        $classRows = collect($classIds)->map(fn (int $classId) => $rows->firstWhere('school_class_id', $classId)
            ?? ['school_class_id' => $classId, 'classroom_id' => null, 'teacher_id' => null]);
        $roomRows = $rows->filter(fn (array $row) => $row['school_class_id'] === null
            && ($row['classroom_id'] !== null || $row['teacher_id'] !== null));

        return $classRows->concat($roomRows)->values()->all();
    }

    // 送られてきた ID の配列から、数字だけを取り出す（URL やフォームをいじって文字や配列が送られてきても落ちないように）
    /** @return array<int, int> */
    private function numericIds(mixed $input): array
    {
        return array_values(array_map('intval', array_filter(Arr::wrap($input), 'is_numeric')));
    }
}
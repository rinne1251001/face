<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Subject;
use App\Models\SubjectSchedule;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// 授業の一覧・追加・編集・削除（管理者のみ）
// 担当の教師は「授業」と「教師」のどちらの画面でも設定できる（同じ subject_teacher 表を使う）
// 受講する生徒は「生徒」の画面で設定する
class SubjectController extends Controller
{
    public function index(): View
    {
        $subjects = Subject::query()
            ->with(['schedules.classroom', 'teachers' => fn ($q) => $q->orderBy('name')])
            ->withCount('students')
            ->orderBySchedule()
            ->get();

        return view('admin.subjects.index', compact('subjects'));
    }

    public function create(): View
    {
        return view('admin.subjects.form', $this->formData(new Subject));
    }

    public function store(Request $request): RedirectResponse
    {
        [$data, $schedules, $teacherIds] = $this->validated($request);

        $subject = DB::transaction(function () use ($data, $schedules, $teacherIds) {
            $subject = Subject::create($data);
            $subject->schedules()->createMany($schedules);
            $subject->teachers()->sync($teacherIds);

            return $subject;
        });

        return redirect()->route('admin.subjects.index')
            ->with('status', "「{$subject->name}（{$subject->schedule_label}）」を追加しました。");
    }

    public function edit(Subject $subject): View
    {
        return view('admin.subjects.form', $this->formData($subject));
    }

    public function update(Request $request, Subject $subject): RedirectResponse
    {
        [$data, $schedules, $teacherIds] = $this->validated($request);

        DB::transaction(function () use ($subject, $data, $schedules, $teacherIds) {
            $subject->update($data);
            // 曜日・時限は、いったん全部消して作り直す（出席記録は「授業・日付・時限」で残っているので消えない）
            SubjectSchedule::where('subject_id', $subject->id)->delete();
            $subject->schedules()->createMany($schedules);
            $subject->teachers()->sync($teacherIds); // 担当教師（一覧にない教師は外れる）
        });

        return redirect()->route('admin.subjects.index')->with('status', '保存しました。');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        // 出席記録が残っている授業は削除できない（記録が消えてしまうため）
        if ($subject->attendances()->exists()) {
            return back()->with('error', "「{$subject->name}」には出席記録があるため削除できません。");
        }

        $subject->delete(); // 生徒の受講・教師の担当の設定は外部キー制約で一緒に削除される

        return redirect()->route('admin.subjects.index')->with('status', '削除しました。');
    }

    // フォーム画面に渡すデータ（授業と、教室・教師の選択肢）
    /** @return array<string, mixed> */
    private function formData(Subject $subject): array
    {
        // 検索に使う教師の一覧（パスワードなどは渡さない）
        $teachers = Teacher::orderBy('name')->get(['id', 'name', 'login_id']);

        // 選ばれている担当教師（入力エラーで戻ってきたときは、さっき選んでいた教師）
        // （前回の入力は文字列のこともあるので、intval で数字にそろえる）
        $selectedIds = array_map('intval', session()->hasOldInput()
            ? (array) old('teacher_ids', [])
            : ($subject->exists ? $subject->teachers->pluck('id')->all() : []));

        return [
            'subject' => $subject,
            'classrooms' => Classroom::orderBy('floor')->orderBy('name')->get(), // 階 → 教室名の順
            'teachers' => $teachers,
            'selectedTeachers' => $teachers->whereIn('id', $selectedIds)->values(),
        ];
    }

    // [授業の項目, 曜日・時限・教室の配列, 担当教師IDの配列] を返す
    /** @return array{array<mixed>, array<int, array{day_of_week: int, period_start: int, period_end: int, classroom_id: int|null}>, array<int, int>} */
    private function validated(Request $request): array
    {
        $max = Subject::maxPeriod();

        $rules = [
            'name' => ['required', 'string', 'max:100'],
            // チェックした曜日（1つ以上）
            'days' => ['sometimes', 'array'],
            'days.*' => ['integer', 'distinct', Rule::in(array_keys(Subject::DAYS))],
            'start_date' => ['nullable', 'date'],
            // 終了日は開始日と同じか、それより後
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            // 担当教師（0人でもよい。複数可）
            'teacher_ids' => ['nullable', 'array'],
            'teacher_ids.*' => ['integer', 'distinct', 'exists:teachers,id'],
        ];
        $attributes = [
            'name' => '授業名',
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

        $data = $request->validate($rules, [], $attributes);

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

        return [Arr::only($data, ['name', 'start_date', 'end_date']), $schedules, $teacherIds];
    }
}
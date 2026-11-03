<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\SubjectSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// 授業の一覧・追加・編集・削除（管理者のみ）
// 担当の教師は「教師」の画面、受講する生徒は「生徒」の画面で設定する
class SubjectController extends Controller
{
    public function index(): View
    {
        $subjects = Subject::query()
            ->with(['schedules', 'teachers' => fn ($q) => $q->orderBy('name')])
            ->withCount('students')
            ->orderBySchedule()
            ->get();

        return view('admin.subjects.index', compact('subjects'));
    }

    public function create(): View
    {
        return view('admin.subjects.form', ['subject' => new Subject]);
    }

    public function store(Request $request): RedirectResponse
    {
        [$data, $schedules] = $this->validated($request);

        $subject = DB::transaction(function () use ($data, $schedules) {
            $subject = Subject::create($data);
            $subject->schedules()->createMany($schedules);

            return $subject;
        });

        return redirect()->route('admin.subjects.index')
            ->with('status', "「{$subject->name}（{$subject->schedule_label}）」を追加しました。");
    }

    public function edit(Subject $subject): View
    {
        return view('admin.subjects.form', compact('subject'));
    }

    public function update(Request $request, Subject $subject): RedirectResponse
    {
        [$data, $schedules] = $this->validated($request);

        DB::transaction(function () use ($subject, $data, $schedules) {
            $subject->update($data);
            // 曜日・時限は、いったん全部消して作り直す（出席記録は「授業・日付・時限」で残っているので消えない）
            SubjectSchedule::where('subject_id', $subject->id)->delete();
            $subject->schedules()->createMany($schedules);
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

    // [授業の項目, 曜日・時限の配列] を返す
    private function validated(Request $request): array
    {
        $max = Subject::maxPeriod();

        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'days' => ['required', 'array', 'min:1'], // チェックした曜日（1つ以上）
            'days.*' => ['integer', 'distinct', Rule::in(array_keys(Subject::DAYS))],
            'start_date' => ['nullable', 'date'],
            // 終了日は開始日と同じか、それより後
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
        $attributes = [
            'name' => '授業名',
            'days' => '曜日',
            'start_date' => '開始日',
            'end_date' => '終了日',
        ];

        // チェックした曜日の時限だけをチェックする（チェックしていない曜日の時限は使わない）
        $checkedDays = array_map('intval', array_filter((array) $request->input('days'), 'is_numeric'));
        foreach (Subject::DAYS as $day => $label) {
            if (in_array($day, $checkedDays, true)) {
                $rules["schedules.$day.period_start"] = ['required', 'integer', "between:1,$max"];
                // 終了時限は開始時限以上（1限だけなら開始と同じ）
                $rules["schedules.$day.period_end"] = ['required', 'integer', "between:1,$max", "gte:schedules.$day.period_start"];
            }
            $attributes["schedules.$day.period_start"] = "{$label}曜日の開始時限";
            $attributes["schedules.$day.period_end"] = "{$label}曜日の終了時限";
        }

        $data = $request->validate($rules, [], $attributes);

        $schedules = collect($data['days'])
            ->map(fn ($day) => [
                'day_of_week' => (int) $day,
                'period_start' => (int) $data['schedules'][$day]['period_start'],
                'period_end' => (int) $data['schedules'][$day]['period_end'],
            ])
            ->sortBy('day_of_week')
            ->values()
            ->all();

        return [Arr::only($data, ['name', 'start_date', 'end_date']), $schedules];
    }
}

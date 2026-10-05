<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// 授業の一覧・追加・編集・削除（管理者のみ）
// 担当の教師は「教師」の画面、受講する生徒は「生徒」の画面で設定する
class SubjectController extends Controller
{
    public function index(): View
    {
        $subjects = Subject::query()
            ->with(['teachers' => fn ($q) => $q->orderBy('name')])
            ->withCount('students')
            ->orderBy('day_of_week')
            ->orderBy('period_start')
            ->orderBy('name')
            ->get();

        return view('admin.subjects.index', compact('subjects'));
    }

    public function create(): View
    {
        return view('admin.subjects.form', ['subject' => new Subject(['period_start' => 1, 'period_end' => 1])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $subject = Subject::create($this->validated($request));

        return redirect()->route('admin.subjects.index')
            ->with('status', "「{$subject->name}（{$subject->schedule_label}）」を追加しました。");
    }

    public function edit(Subject $subject): View
    {
        return view('admin.subjects.form', compact('subject'));
    }

    public function update(Request $request, Subject $subject): RedirectResponse
    {
        $subject->update($this->validated($request));

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

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'day_of_week' => ['required', 'integer', Rule::in(array_keys(Subject::DAYS))],
            'period_start' => ['required', 'integer', 'between:1,'.Subject::MAX_PERIOD],
            // 終了時限は開始時限以上（1限だけなら開始と同じ）
            'period_end' => ['required', 'integer', 'between:1,'.Subject::MAX_PERIOD, 'gte:period_start'],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\Enrollment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// 学科の一覧・追加・編集・削除（管理者のみ）
// 修業年限（何年で卒業するか）は学科ごとに決まり、年度更新の「卒業」の判定に使う
class DepartmentController extends Controller
{
    public function index(): View
    {
        $departments = Department::query()
            ->withCount('students') // 生徒数（卒業生も含む。$department->students_count で使える）
            ->orderBy('id')
            ->get();

        return view('admin.departments.index', compact('departments'));
    }

    public function create(): View
    {
        return view('admin.departments.form', ['department' => new Department]);
    }

    public function store(Request $request): RedirectResponse
    {
        $department = Department::create($this->validated($request));

        return redirect()->route('admin.departments.index')->with('status', "「{$department->name}」を追加しました。");
    }

    public function edit(Department $department): View
    {
        return view('admin.departments.form', compact('department'));
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $department->update($this->validated($request, $department));

        return redirect()->route('admin.departments.index')->with('status', '保存しました。');
    }

    public function destroy(Department $department): RedirectResponse
    {
        // 生徒がいる学科は削除できない（データベースでも止めているが、わかりやすい文を出すため先に確かめる）
        if ($department->students()->exists()) {
            return back()->with('error', "「{$department->name}」には生徒がいるため削除できません。");
        }

        $department->delete();

        return redirect()->route('admin.departments.index')->with('status', '削除しました。');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Department $department = null): array
    {
        // 修業年限は、在校生（今年度とこれからの年度）のいちばん上の学年より短くできない
        // （短くすると、その学年の生徒が年度更新のときに進級も卒業もできなくなるため）
        $minYears = 1;
        if ($department?->exists) {
            $minYears = max(1, (int) Enrollment::query()
                ->whereIn('student_id', $department->students()->select('id'))
                ->whereIn('academic_year_id', AcademicYear::query()->whereDate('end_date', '>=', today())->select('id'))
                ->max('grade'));
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('departments')->ignore($department)],
            'years' => ['required', 'integer', 'between:1,6', "min:{$minYears}"],
        ], [
            'years.min' => "在校生に{$minYears}年生がいるため、修業年限は{$minYears}年より短くできません。",
        ], [
            'name' => '学科名',
            'years' => '修業年限',
        ]);
    }
}
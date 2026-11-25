<?php

namespace App\Http\Controllers\Admin;

use App\Models\Classroom;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClassroomController extends Controller
{
    // 一覧画面
    public function index(): View
    {
        $rooms = Classroom::all();
        return view('admin.rooms.index', compact('rooms'));
    }

    // 新規作成画面（← これが消えていたためエラーになっていました）
    public function create(): View
    {
        return view('admin.rooms.form', ['room' => new Classroom()]);
    }

    // 新規作成保存処理
    public function store(Request $request): RedirectResponse
    {
        Classroom::create($this->validated($request));

        return redirect()->route('admin.rooms.index');
    }

    // 詳細画面
    public function show(Classroom $room): View
    {
        return view('admin.rooms.show', compact('room'));
    }

    // 編集画面
    public function edit(Classroom $room): View
    {
        return view('admin.rooms.form', compact('room'));
    }

    // 編集保存処理
    public function update(Request $request, Classroom $room): RedirectResponse
    {
        $room->update($this->validated($request, $room));

        return redirect()->route('admin.rooms.index');
    }

    // 削除処理
    public function destroy(Classroom $room): RedirectResponse
    {
        $room->delete();

        return redirect()->route('admin.rooms.index');
    }

    // バリデーション処理
    private function validated(Request $request, ?Classroom $room = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('classrooms', 'name')->ignore($room?->id),
            ],
            'floor' => ['required', 'integer'],
        ], [], [
            'name'  => '教室名',
            'floor' => '階数',
        ]);
    }
}
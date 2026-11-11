<?php

namespace App\Http\Controllers\Admin;

// 必要なクラスや機能をインポートする
use App\Http\Controllers\Controller;
use App\Models\Classroom;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

// クラス宣言
class ClassroomController extends Controller
{
    // 一覧画面
    public function index(): View
    {
        // sunjectsテーブルのデータをコレクション形式で一括取得
        $rooms = Classroom::query()
            ->with('schoolClasses')
            ->get();
        // resources/views/tests/tests/index.blade.phpのファイルを画面に表示
        return view('admin.rooms.index', compact('rooms'));
    }

    // 新規作成画面
    public function create(): View
    {
        // まだデータベースに保存されていない中身が空のSubjectオブジェクトを生成
        // old('入力項目名')だけでなく、old('入力項目名', $subject->カラム名)と書くことができるのでcreateとeditを同じ画面にまとめられる
        return view('admin.rooms.form', ['room' => new Classroom()]);
    }

    // 新規作成保存処理
    public function store(Request $request): RedirectResponse
    {
        $subject = Classroom::create($this->validated($request));

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
        $room->update($this->validated($request));

        return redirect()->route('admin.rooms.index')->with('status', '保存しました。');
    }

    // 削除処理
    public function destroy(Classroom $room): RedirectResponse
    {
        $room->delete();

        return redirect()->route('admin.rooms.index')->with('status', '教室を削除しました。');
    }
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'floor' => ['required', 'integer'],
            
        ], [], [
            'name' => 'クラス名',
            'floor' => '階',
        ]);
    }
}
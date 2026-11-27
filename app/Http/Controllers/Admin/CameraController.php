<?php

namespace App\Http\Controllers\Admin;

use App\Models\Camera;
use App\Models\Classroom;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CameraController extends Controller
{
    // 一覧画面
    public function index(): View
    {
        $cameras = Camera::with('classroom')->get(); // 教室名も一緒に読み込む（1台ごとに SQL が増えないように）
        return view('admin.cameras.index', compact('cameras'));
    }

    // 新規作成画面
    public function create(): View
    {
        // まだカメラがない教室だけ（1つの教室に1台）
        $rooms = Classroom::doesntHave('camera')->orderBy('name')->get();
        return view('admin.cameras.form', ['camera' => new Camera(), 'rooms' => $rooms]);
    }

    // 新規作成保存処理
    public function store(Request $request): RedirectResponse
    {
        Camera::create($this->validated($request));

        return redirect()->route('admin.cameras.index');
    }

    // 詳細画面
    public function show(Camera $camera): View
    {
        $classroom = $camera->classroom;

        return view('admin.cameras.show', compact('camera', 'classroom'));
    }

    // 編集画面
    public function edit(Camera $camera): View
    {
        // カメラがない教室と、このカメラの今の教室
        $rooms = Classroom::doesntHave('camera')
            ->orWhere('id', $camera->classroom_id)
            ->orderBy('name')
            ->get();
        return view('admin.cameras.form', compact('camera', 'rooms'));
    }

    // 編集保存処理
    public function update(Request $request, Camera $camera): RedirectResponse
    {
        // store と同じ validated() を利用（パスワード空欄時は更新しない）
        $camera->update($this->validated($request, $camera));

        return redirect()->route('admin.cameras.index');
    }

    // 削除処理
    public function destroy(Camera $camera): RedirectResponse
    {
        $camera->delete();

        return redirect()->route('admin.cameras.index');
    }

    // バリデーション処理
    /** @return array<string, mixed> */
    private function validated(Request $request, ?Camera $camera = null): array // Camara → Camera に修正
    {
        $data = $request->validate([
            // 1つの教室に1台（編集のときは、このカメラ自身を除いて確かめる）
            'classroom_id' => ['required', 'exists:classrooms,id', Rule::unique('cameras', 'classroom_id')->ignore($camera)],
            'password'     => [$camera ? 'nullable' : 'required', 'string', 'min:8', 'max:100'],
        ], [
            'classroom_id.unique' => 'この教室には、もうカメラがあります。',
        ], [
            'classroom_id' => '教室名',
            'password'     => 'パスワード',
        ]);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        return $data; // [$data] ではなく $data をそのまま返す
    }
}
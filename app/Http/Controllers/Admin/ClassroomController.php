<?php

namespace App\Http\Controllers\Admin;

// 必要なクラスや機能をインポートする
use App\Http\Controllers\Concerns\SortsList;
use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

// クラス宣言
class ClassroomController extends Controller
{
    use SortsList;

    // 一覧画面
    public function index(Request $request): Response
    {
        // 検索の文字（空のときや、URL をいじって文字以外が送られてきたときは絞り込まない）
        $input = $request->query('q');
        $q = is_string($input) ? trim($input) : '';

        // 空白（全角の空白も）で区切って、キーワードごとに分ける
        // 例：「3階 A組」→ ['3階', 'A組']（両方に当てはまる教室だけを出す）
        $keywords = preg_split('/[\s　]+/u', $q, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        // 階（「階」のままなら null。地下は -1 のようにマイナスの数字で送られてくる）
        $input = $request->query('floor');
        $floor = is_string($input) && preg_match('/^-?\d+$/', $input) ? (int) $input : null;

        // 紐づくクラス：'with'（クラスあり）か 'without'（クラスなし）。それ以外は「すべて」
        $classFilter = $request->query('classes');
        if (! in_array($classFilter, ['with', 'without'], true)) {
            $classFilter = '';
        }

        $query = Classroom::query()
            ->with('schoolClasses', fn ($c) => $c->orderBy('name'))
            // 階を選んでいるときだけ、その階の教室に絞り込む
            ->when($floor !== null, fn ($w) => $w->where('floor', $floor))
            // has()：クラスが1つ以上ある教室 ／ doesntHave()：クラスが1つもない教室
            ->when($classFilter === 'with', fn ($w) => $w->has('schoolClasses'))
            ->when($classFilter === 'without', fn ($w) => $w->doesntHave('schoolClasses'));

        // キーワードが、教室名・クラス名のどちらかに含まれる教室
        foreach ($keywords as $keyword) {
            $like = "%{$keyword}%";
            $query->where(fn ($w) => $w
                ->where('name', 'like', $like)
                ->orWhereHas('schoolClasses', fn ($c) => $c->where('name', 'like', $like)));
        }

        // 並び替え（何も指定がなければ階の順）
        [$sort, $direction] = $this->applySort($query, $request, $this->sortColumns(), 'floor');

        $rooms = $query
            ->orderBy('name') // 同じ値どうしは教室名の順（同じ階の中など）
            ->orderBy('id')
            ->get();

        // 階の選択肢（登録されている階だけ。小さい順）
        $floors = Classroom::query()->select('floor')->distinct()->orderBy('floor')->get();

        // 何か1つでも絞り込んでいるか（「クリア」の表示や、0件のときの文に使う）
        $filtering = $q !== '' || $floor !== null || $classFilter !== '';

        // Ajax（見出しを押した並び替え）のときは、一覧の表の部分だけを返す
        $view = $request->ajax() ? 'admin.rooms.partials.list' : 'admin.rooms.index';

        return response()
            ->view($view, compact('rooms', 'q', 'floor', 'floors', 'classFilter', 'filtering', 'sort', 'direction'))
            // 同じ URL で「ページ全体」と「表だけ」を返し分けるので、ブラウザのキャッシュで混ざらないようにする
            ->header('Vary', 'X-Requested-With');
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
    
    /** @return array<string, mixed> */
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

    // 一覧で並び替えできる列（URL の ?sort= に使うキー => 並び替えに使う列）
    /** @return array<string, mixed> */
    private function sortColumns(): array
    {
        return [
            'name' => 'name',
            'floor' => 'floor',
            // クラス名は別のテーブルにあるので、その教室のクラス名を取り出すサブクエリで並び替える
            // （クラスが複数あるときは、名前がいちばん先になるものを使う）
            'class' => SchoolClass::selectRaw('min(name)')->whereColumn('school_classes.classroom_id', 'classrooms.id'),
        ];
    }
}
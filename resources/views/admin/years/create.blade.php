@extends('layouts.test')
@section('title', '年度更新')
@push('styles')
<style>
    /* ステップ切り替え用のラジオボタン（画面には表示しない） */
    .step-radio {
        position: absolute;
        top: 0;
        left: 0;
        opacity: 0;
        pointer-events: none;
    }

    .progress {
        display: flex;
        padding: 0 1em; /* 両端のラベルがはみ出さないための余白 */
        container-type: inline-size; /* ラベルの最大幅の計算（cqi）に使う */
        padding-block: 20px;
    }

    .step-container {
        display: flex;
        align-items: flex-start;
        width: 100%;
        --step-color: var(--color-font-light);  /* 初期状態はすべて薄い色 */
        --bar-fill: 0%;
    }

    /* 最後のステップはバーがないので、円の幅だけにする */
    .step-container:last-child {
        width: auto;
    }

    /* ステップの幅を円と同じにして、バーを円にぴったりつなげる */
    .step {
        flex-shrink: 0;
        width: 40px;
        cursor: pointer;
    }

    /* 円 */
    .step span:nth-of-type(1) {
        background-color: var(--step-color);
        color: var(--color-bg);
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: grid;
        place-content: center;
        transition: background-color .3s .3s;
    }

    /* ラベル：円より広くてもよいので、円の中心に合わせて左右にはみ出させる */
    .step span:nth-of-type(2) {
        display: block;
        width: max-content;
        margin-top: 4px;
        position: relative;
        left: 50%;
        translate: -50% 0;
        /* 画面が狭くて隣と重なるときだけ <wbr> の位置で折り返す
           最大幅 ＝（全体の幅 − 円1つ）÷ 5 ＝ 円と円の間隔（ステップが6つなので、間隔は5つ） */
        max-width: calc((100cqi - 40px) / 5 - 4px);
        word-break: keep-all;
        text-align: center;
        color: var(--step-color);
        font-size: 0.9em;
        transition: color .3s .3s;
    }

    .bar {
        flex-shrink: 1;
        height: 3px;
        width: 100%;
        margin-top: calc((40px - 3px) / 2); /* （円の高さ − バーの高さ）÷ 2 で円の上下中央に */
        /* 薄い色の上に main 色を左寄せで重ね、その幅を 0% ⇔ 100% で変化させる */
        background:
            linear-gradient(var(--color-main), var(--color-main)) left / var(--bar-fill) 100% no-repeat,
            var(--color-font-light);
        transition: background-size .3s;
    }

    /* 現在のステップまでの円とラベルを main 色に */
    #step1:checked ~ .progress .step-container:nth-child(-n+1),
    #step2:checked ~ .progress .step-container:nth-child(-n+2),
    #step3:checked ~ .progress .step-container:nth-child(-n+3),
    #step4:checked ~ .progress .step-container:nth-child(-n+4),
    #step5:checked ~ .progress .step-container:nth-child(-n+5),
    #step6:checked ~ .progress .step-container:nth-child(-n+6) {
        --step-color: var(--color-main);
    }

    /* 現在のステップより前のバーを main 色に */
    #step2:checked ~ .progress .step-container:nth-child(-n+1),
    #step3:checked ~ .progress .step-container:nth-child(-n+2),
    #step4:checked ~ .progress .step-container:nth-child(-n+3),
    #step5:checked ~ .progress .step-container:nth-child(-n+4),
    #step6:checked ~ .progress .step-container:nth-child(-n+5) {
        --bar-fill: 100%;
    }

    /* フォームを重ねて置き、はみ出した部分は見せない */
    .form-container {
        position: relative;
        overflow: clip;
    }

    /* 現在より後ろのフォーム：右の外で待機（非表示） */
    .form {
        display: grid;
        gap: 20px;
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        background-color: var(--color-bg);
        translate: 100% 0;
        visibility: hidden;
        /* 横に動かす。消えるのは動き終わってから（0.4秒後） */
        transition: translate .4s ease, visibility 0s .4s;
    }

    /* 現在より前のフォーム：左の外で待機（非表示） */
    #step2:checked ~ .form-container .form:nth-child(-n+1),
    #step3:checked ~ .form-container .form:nth-child(-n+2),
    #step4:checked ~ .form-container .form:nth-child(-n+3),
    #step5:checked ~ .form-container .form:nth-child(-n+4),
    #step6:checked ~ .form-container .form:nth-child(-n+5) {
        translate: -100% 0;
    }

    /* 現在のフォーム：中央に表示（高さもこのフォームに合わせる） */
    #step1:checked ~ .form-container .form:nth-child(1),
    #step2:checked ~ .form-container .form:nth-child(2),
    #step3:checked ~ .form-container .form:nth-child(3),
    #step4:checked ~ .form-container .form:nth-child(4),
    #step5:checked ~ .form-container .form:nth-child(5),
    #step6:checked ~ .form-container .form:nth-child(6) {
        position: static;
        translate: 0 0;
        visibility: visible;
        transition-delay: 0s; /* 表示はすぐに */
    }

    .ay-input-container {
        display: grid;
        gap: 15px;
    }

    .ay-input {
        display: grid;
        gap: 5px;
    }

    .ay-input label {
        font-size: 0.95em;
        padding-left: 2px;
    }

    .ay-input input {
        min-width: 0;
        background-color: color-mix(in srgb, var(--color-font-light), transparent 80%);
        border: none;
        outline: none;
        padding: 7px 10px;
        font-size: 1.05em;
    }

    .ay-error {
        color: var(--color-error-font);
        font-size: 0.8em;
    }

    .ay-table-container {
        overflow-x: auto;
        max-width: 100%;
    }
    .ay-table-container table {
        border-collapse: collapse;
        min-width: 100%;
    }
    .ay-table-container th {
        background-color: var(--color-main);
        color: var(--color-bg);
        padding: 10px 20px;
        white-space: nowrap;
    }
    .ay-table-container td {
        height: 2.5em;
    }
    .ay-table-container th,
    .ay-table-container td {
        min-width: 4em;
        border: 1px solid var(--color-font-light);
    }
    .ay-table-container th:nth-child(1),
    .ay-table-container td:nth-child(1) {
        width: 20em;
    }
    .ay-table-container th:nth-child(5),
    .ay-table-container td:nth-child(5) {
        width: 3em;
    }
    .ay-table-container input,
    .ay-table-container select {
        min-width: 0;
        width: 100%;
        height: 100%;
        padding-inline: 10px;
        border: none;
        outline: none;
    }
    /* コピーのチェックを外した行は薄い色にする */
    .ay-table-container tr:has(input[type="checkbox"]:not(:checked)) {
        background-color: color-mix(in srgb, var(--color-sub), var(--color-font-light) 50%);
        color: var(--color-bg);
    }

    /* コピーのチェックを外した行は薄い色にする
       （「追加」ボタンの中のチェックボックスは対象外にするため、「> td >」でセル直下だけを見る） */
    .ay-table-container tr:has(> td > input[type="checkbox"]:not(:checked)) {
        background-color: color-mix(in srgb, var(--color-sub), var(--color-font-light) 50%);
        color: var(--color-bg);
    }

    /* 文字だけのセル（教師コピーの名前・ログインID）。入力欄と同じ余白にそろえる */
    .ay-table-container td.text-cell {
        padding-inline: 10px;
    }

    /* 「追加」ボタンのセル */
    .ay-table-container .add-cell {
        border: none;
        text-align: center;
        padding-top: 10px;
        background-color: var(--color-bg);
        color: var(--color-main);
    }
    .add-btn {
        display: inline-block;
        padding: 5px 15px;
        border: 1px solid var(--color-font-light);
        cursor: pointer;
        user-select: none;
    }
    /* 個別調整の行を取り消す「×」ボタン */
    .remove-row {
        width: 100%;
        height: 100%;
        border: none;
        background: none;
        color: var(--color-main);
    }

    /* チェックボックス本体は見えなくする（Tab で選んで Space キーでも押せる） */
    .ay-table-container .add-toggle {
        position: absolute;
        width: 1px;
        height: 1px;
        opacity: 0;
        pointer-events: none;
    }

    .add-btn:has(.add-toggle:focus-visible) {
        outline: 2px solid var(--color-main);
    }

    /* まだ追加していない予備行：入力欄を隠して「追加」ボタンだけ表示 */
    .add-row:not(:has(.add-toggle:checked)) > td:not(.add-cell) {
        display: none;
    }

    /* 追加した行：「追加」ボタンを隠して入力欄を表示 */
    .add-row:has(.add-toggle:checked) > .add-cell {
        display: none;
    }

    /* まだ追加していない予備行は、先頭の1行（＝「追加」ボタン）だけ表示 */
    .add-row:has(.add-toggle:not(:checked)) ~ .add-row {
        display: none;
    }

    .search-container {
        position: relative;
        width: 100%;
    }
    .search-container ul {
        position: absolute;
        width: 100%;
        top: 100%;
        left: 0;
        background-color: var(--color-sub);
        max-height: 150px;
        overflow-y: auto;
        display: none;
        z-index: 10;
        box-shadow: 4px 4px 10px rgba(0, 0, 0, 0.15);
    }
    /* 検索欄（または候補）にフォーカスがあり、候補が1件以上あるときだけ一覧を開く */
    .search-container:focus-within ul:has(> li) {
        display: block;
    }
    .search-container li {
        padding: 15px 20px;
        cursor: pointer;
        background-color: var(--color-sub);
        display: flex;
        align-items: flex-end;
        gap: 7px;
    }
    .search-container li:hover,
    .search-container li:focus {
        filter: brightness(0.95);
        outline: none;
    }
    .search-container li span:nth-child(2) {
        font-size: 0.9em;
        opacity: 0.7;
    }
    .search-container li.empty {
        cursor: default;
        opacity: 0.7;
    }
    .search-container li.empty:hover {
        filter: none;
    }
    /* 検索の候補が開いているあいだは、表の下に候補の分の余白を作る
    （.ay-table-container は overflow-x: auto なので、余白がないと候補が枠の中に隠れてしまう） */
    .ay-table-container:has(.search-container:focus-within li) {
        padding-bottom: 150px;
    }

    .confirm {
        border-collapse: collapse;
    }
    .confirm td {
        border-bottom: 1px solid var(--color-font-light);
        padding: 10px 20px;
    }
    .confirm td:nth-child(2) {
        text-align: right;
        font-weight: bolder;
    }

    .btn-container {
        display: flex;
        justify-content: space-between;
    }
    .prev,
    .next {
        color: var(--color-bg);
        padding: 5px 15px;
        cursor: pointer;
        user-select: none;
    }
    .prev {
        background-color: var(--color-font-light);
    }
    .next {
        background-color: var(--color-main);
    }

    /* 「動きを減らす」設定の人にはアニメーションしない */
    @media (prefers-reduced-motion: reduce) {
        .form {
            transition: none;
        }
    }
</style>
@endpush

@section('content')

<main class="main">
    <div class="content-wrapper">

        <div class="page-header">
            <h1>年度更新</h1>
        </div>

        @php
            $steps = ['年度<wbr>作成', '生徒<wbr>進級', '教師<wbr>コピー', 'クラス<wbr>コピー', '授業<wbr>コピー', '確認'];

            // 入力エラーで戻ってきたときは、エラーがある最初のステップを開く
            $openStep = match (true) {
                $errors->hasAny(['year', 'start_date', 'end_date']) => 1,
                $errors->has('adjustments*') => 2,
                $errors->has('teachers_off*') => 3,
                $errors->has('classes*') => 4,
                $errors->has('subjects*') => 5,
                default => 1,
            };

            // 教師コピーのチェック：最初は全員チェック済み。入力エラーで戻ってきたときは、さっきの状態
            $teacherCopied = fn (int $id) => ! session()->hasOldInput() || ! empty(old("teachers.$id.copy"));

            // 入力エラーで戻ってきたときの、さっきの個別調整（下のスクリプトが行を作り直す）
            // （スクリプトに渡すときは変数にしておく。カンマを含む式をそのまま渡すと、Blade が式を途中で区切ることがある）
            $savedAdjustments = array_values((array) old('adjustments', []));

            // クラスと授業の表の行
            // 入力エラーで戻ってきたときは、さっき入力していた行。そうでなければ、コピー元の年度のクラス・授業
            $rowsFor = fn (string $field, $source) => session()->hasOldInput()
                ? collect((array) old($field, []))->filter(fn ($row) => is_array($row) && ! empty($row['added']))->values()->all()
                : $source->map(fn ($item) => ['copy' => 1, 'name' => $item->name, 'grade' => $item->grade])->all();
        @endphp

        {{-- novalidate：隠れているステップの入力欄でブラウザのチェックが止まらないように、チェックはサーバーに任せる --}}
        <form method="POST" action="{{ route('admin.years.store') }}" id="year-form" novalidate>
            @csrf

            {{-- 表示中のステップを保持するラジオボタン（切り替えはすべてCSSで行う） --}}
            @foreach ($steps as $label)
                <input type="radio" class="step-radio" name="step" id="step{{ $loop->iteration }}" value="{{ $loop->iteration }}"
                    aria-label="ステップ{{ $loop->iteration }}" @checked($loop->iteration === $openStep)>
            @endforeach

            <div class="progress">
                @foreach ($steps as $label)
                    <div class="step-container">
                        <label class="step" for="step{{ $loop->iteration }}">
                            <span>{{ $loop->iteration }}</span>
                            <span>{!! $label !!}</span>
                        </label>
                        @unless ($loop->last)
                            <div class="bar"></div>
                        @endunless
                    </div>
                @endforeach
            </div>

            <div class="form-container">
                {{-- 1. 年度作成 --}}
                <div class="form">
                    <div>年度作成</div>
                    <div class="ay-input-container">
                        <div>
                            <div class="ay-input">
                                <label for="year">新年度</label>
                                <input type="number" id="year" name="year" min="2000" max="2100" step="1"
                                    value="{{ old('year', $newYear) }}" placeholder="{{ $newYear }}">
                            </div>
                            @error('year')
                                <p class="ay-error">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <div class="ay-input">
                                {{-- 新年度を入れると、4月1日〜翌年3月31日が自動で入る（手で直すこともできる） --}}
                                <label for="start_date">年度範囲</label>
                                <div style="display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 3px;">
                                    <input type="date" id="start_date" name="start_date" value="{{ old('start_date', $newYear.'-04-01') }}" aria-label="開始日">
                                    <span>～</span>
                                    <input type="date" id="end_date" name="end_date" value="{{ old('end_date', ($newYear + 1).'-03-31') }}" aria-label="終了日">
                                </div>
                            </div>
                            @foreach ([...$errors->get('start_date'), ...$errors->get('end_date')] as $message)
                                <p class="ay-error">{{ $message }}</p>
                            @endforeach
                        </div>
                    </div>
                    <div class="btn-container">
                        <div></div>
                        <label class="next" for="step2">次へ</label>
                    </div>
                </div>

                {{-- 2. 生徒進級（人数は下のスクリプトが数える。クラスと出席番号は未定のまま登録される） --}}
                <div class="form">
                    <div>生徒進級</div>
                    @if ($from)
                        <p>{{ $from->label }}に在籍している{{ count($students) }}人が対象です。新入生は、年度を登録したあとで生徒の画面から登録します。</p>
                    @else
                        <p>最初の年度なので、進級する生徒はいません。</p>
                    @endif
                    <div>
                        <div>
                            @for ($g = 1; $g < $maxGrade; $g++)
                                <div>
                                    <div>{{ $g }}年→{{ $g + 1 }}年</div>
                                    <div><span data-promote="{{ $g }}">0</span>人</div>
                                </div>
                            @endfor
                            <div>
                                <div>卒業</div>
                                <div><span data-count="graduated">0</span>人</div>
                            </div>
                        </div>
                    </div>
                    <div class="ay-table-container">
                        <div>個別調整</div>
                        <table>
                            <thead>
                                <tr><th>名前</th><th>クラス</th><th>処理</th><th>進級学年</th><th></th></tr>
                            </thead>
                            {{-- 行は「追加」を押すと、下の template から作られる --}}
                            <tbody id="adjustments"></tbody>
                            <tbody>
                                <tr>
                                    <td class="add-cell" colspan="5">
                                        <button type="button" class="add-btn" id="add-adjustment">追加</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        @foreach (collect($errors->get('adjustments*'))->flatten()->unique() as $message)
                            <p class="ay-error">{{ $message }}</p>
                        @endforeach
                    </div>
                    <div class="btn-container">
                        <label class="prev" for="step1">前へ</label>
                        <label class="next" for="step3">次へ</label>
                    </div>
                </div>

                {{-- 3. 教師コピー（教師は年度ごとに作り直さない。チェックを外した教師を、ログインできない設定にするだけ） --}}
                <div class="form">
                    <div>教師コピー</div>
                    <p>
                        新年度も続ける教師にチェックを入れます。チェックを外した教師は、年度を登録するとログインできなくなります
                        （これまでの記録は残ります。教師の画面の「ログイン可」で、いつでも戻せます）。
                        新しい教師は、年度を登録したあとで教師の画面から追加します。
                    </p>
                    <div class="ay-table-container">
                        <table data-teacher-table>
                            <thead>
                                <tr><th>コピー</th><th>名前</th><th>ログインID</th></tr>
                            </thead>
                            <tbody>
                                @forelse ($teachers as $teacher)
                                    <tr>
                                        <td>
                                            {{-- listed … この画面に出ていた教師の印（出ていない教師は、登録しても何も変わらない） --}}
                                            <input type="hidden" name="teachers[{{ $teacher->id }}][listed]" value="1">
                                            <input type="checkbox" name="teachers[{{ $teacher->id }}][copy]" value="1" @checked($teacherCopied($teacher->id))
                                                aria-label="{{ $teacher->name }} 先生は新年度も続ける">
                                        </td>
                                        <td class="text-cell">{{ $teacher->name }}</td>
                                        <td class="text-cell">{{ $teacher->login_id }}</td>
                                    </tr>
                                @empty
                                    <tr><td class="text-cell" colspan="3">ログインできる教師が登録されていません。</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        @foreach (collect($errors->get('teachers_off*'))->flatten()->unique() as $message)
                            <p class="ay-error">{{ $message }}</p>
                        @endforeach
                    </div>
                    <div class="btn-container">
                        <label class="prev" for="step2">前へ</label>
                        <label class="next" for="step4">次へ</label>
                    </div>
                </div>

                {{-- 4. クラスコピー（名前と学年だけ。教室と担任は、あとでクラスの画面から設定する） --}}
                <div class="form">
                    <div>クラスコピー</div>
                    @include('admin.years.partials.copy-table', [
                        'field' => 'classes', 'label' => 'クラス名', 'maxLength' => 50,
                        'rows' => $rowsFor('classes', $classes),
                    ])
                    <div class="btn-container">
                        <label class="prev" for="step3">前へ</label>
                        <label class="next" for="step5">次へ</label>
                    </div>
                </div>

                {{-- 5. 授業コピー（名前と学年だけ。期間・時間割・担当は、あとで授業の画面から登録する） --}}
                <div class="form">
                    <div>授業コピー</div>
                    @include('admin.years.partials.copy-table', [
                        'field' => 'subjects', 'label' => '授業名', 'maxLength' => 100,
                        'rows' => $rowsFor('subjects', $subjects),
                    ])
                    <div class="btn-container">
                        <label class="prev" for="step4">前へ</label>
                        <label class="next" for="step6">次へ</label>
                    </div>
                </div>

                {{-- 6. 確認（数字は下のスクリプトが、今の入力から数える） --}}
                <div class="form">
                    <div>確認</div>
                    <table class="confirm">
                        <tr><td>新年度名</td><td data-count="year"></td></tr>
                        <tr><td>年度</td><td data-count="range"></td></tr>
                        <tr><td>進級生徒数</td><td><span data-count="promoted">0</span>人</td></tr>
                        <tr><td>卒業生徒数</td><td><span data-count="graduated">0</span>人</td></tr>
                        <tr><td>留年数</td><td><span data-count="repeated">0</span>人</td></tr>
                        <tr><td>退学数</td><td><span data-count="withdrawn">0</span>人</td></tr>
                        <tr><td>続ける教師数</td><td><span data-count="teachers">0</span>人</td></tr>
                        <tr><td>ログインできなくなる教師数</td><td><span data-count="teachersOff">0</span>人</td></tr>
                        <tr><td>クラス数</td><td><span data-count="classes">0</span>件</td></tr>
                        <tr><td>授業数</td><td><span data-count="subjects">0</span>件</td></tr>
                    </table>
                    <div class="btn-container">
                        <label class="prev" for="step5">前へ</label>
                        <button type="submit" class="next">登録</button>
                    </div>
                </div>
            </div>
        </form>

        {{-- 個別調整の1行分のひな形（name は、行を追加するときにスクリプトが付ける） --}}
        <template id="adjustment-row">
            <tr>
                <td class="search-container">
                    <input type="hidden" data-field="student_id">
                    {{-- name を付けないので、検索の文字そのものは送信されない --}}
                    <input type="text" class="student-search" placeholder="名前または学籍番号で検索" autocomplete="off" aria-label="生徒">
                    <ul role="listbox" aria-label="検索結果"></ul>
                </td>
                <td>
                    {{-- 生徒を選ぶと、今のクラスと学年が自動で入る --}}
                    <div class="current-class"></div>
                </td>
                <td>
                    <select data-field="action" aria-label="処理">
                        <option value="repeat">留年</option>
                        <option value="withdraw">退学</option>
                    </select>
                </td>
                <td>
                    <select data-field="grade" aria-label="進級学年">
                        @for ($g = 1; $g <= $maxGrade; $g++)
                            <option value="{{ $g }}">{{ $g }}年</option>
                        @endfor
                    </select>
                </td>
                <td>
                    <button type="button" class="remove-row" aria-label="この行を取り消す">×</button>
                </td>
            </tr>
        </template>
    </div>
</main>

@endsection
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // 進級の対象の生徒（コピー元の年度に在籍している生徒）
            //   { id, name, furigana, number, class, grade, years } … years は学科の修業年限
            const students = @json($students);
            // 入力エラーで戻ってきたときの、さっきの個別調整
            const savedAdjustments = @json($savedAdjustments);

            const form = document.getElementById('year-form');
            const yearInput = document.getElementById('year');
            const startInput = document.getElementById('start_date');
            const endInput = document.getElementById('end_date');
            const tbody = document.getElementById('adjustments');
            const template = document.getElementById('adjustment-row');

            // 全角・半角、大文字・小文字、空白、カタカナ・ひらがなの違いを無視して比べるための変換
            const norm = (s) => String(s ?? '').normalize('NFKC').toLowerCase().replace(/\s+/g, '')
                .replace(/[\u30a1-\u30f6]/g, (c) => String.fromCharCode(c.charCodeAt(0) - 0x60)); // カタカナ → ひらがな
            // 行の中の部品を取り出す
            const field = (row, name) => row.querySelector(`[data-field="${name}"]`);

            // ---------- 1. 年度を入れると、期間（4月1日〜翌年3月31日）も自動で合わせる ----------
            yearInput.addEventListener('input', () => {
                const year = Number(yearInput.value);
                if (!Number.isInteger(year) || year < 2000 || year > 2100) return; // 入力の途中（「20」など）は何もしない
                startInput.value = `${year}-04-01`;
                endInput.value = `${year + 1}-03-31`;
            });

            // ---------- 2. 個別調整：名前で検索して選ぶと、所属（クラス・学年）が自動で入る ----------
            let nextIndex = 0; // 行ごとに違う番号を付ける（adjustments[0][...]、adjustments[1][...]）

            // 行を追加する（saved があれば、その内容を入れた状態で追加する）
            function addRow(saved = null) {
                const row = template.content.firstElementChild.cloneNode(true);
                const index = nextIndex++;
                row.querySelectorAll('[data-field]').forEach((el) => {
                    el.name = `adjustments[${index}][${el.dataset.field}]`;
                });
                tbody.append(row);

                const student = saved ? students.find((s) => s.id === Number(saved.student_id)) : null;
                if (student) {
                    choose(row, student);
                    if (saved.action) field(row, 'action').value = saved.action;
                    if (saved.grade) field(row, 'grade').value = saved.grade;
                }
                syncRow(row);

                return row;
            }

            // 生徒を選ぶ：所属を自動で入れ、進級学年は今の学年（＝留年）にしておく
            function choose(row, student) {
                field(row, 'student_id').value = student.id;
                row.querySelector('.student-search').value = student.name;
                row.querySelector('.current-class').textContent = `${student.class}（${student.grade}年）`;
                field(row, 'grade').value = Math.min(student.grade, student.years);
                row.querySelector('ul').replaceChildren(); // 候補の一覧を閉じる
            }

            // 退学のときは、進級学年を選べなくする（disabled の欄は送信されない）
            function syncRow(row) {
                field(row, 'grade').disabled = field(row, 'action').value === 'withdraw';
            }

            // ほかの行ですでに選んである生徒のID
            const chosenIds = () => [...tbody.querySelectorAll('[data-field="student_id"]')]
                .map((el) => Number(el.value))
                .filter((id) => id > 0);

            // 検索結果の一覧を作り直す（文字が空なら出さない。選んである生徒は出さない。多すぎないよう20件まで）
            function renderResults(row) {
                const word = norm(row.querySelector('.student-search').value);
                const list = row.querySelector('ul');
                const chosen = chosenIds();
                const hits = word === '' ? [] : students
                    .filter((s) => !chosen.includes(s.id)
                        && (norm(s.name).includes(word) || norm(s.furigana).includes(word) || norm(s.number).includes(word)))
                    .slice(0, 20);

                list.replaceChildren(...hits.map((s) => {
                    const li = document.createElement('li');
                    li.setAttribute('role', 'option');
                    li.dataset.id = s.id;
                    const name = document.createElement('span');
                    name.textContent = s.name; // textContent なので、名前に記号があっても安全
                    const sub = document.createElement('span');
                    sub.textContent = `${s.number}・${s.class}`;
                    li.append(name, sub);
                    return li;
                }));

                if (word !== '' && hits.length === 0) {
                    const li = document.createElement('li');
                    li.className = 'empty';
                    li.textContent = '見つかりません';
                    list.append(li);
                }
            }

            document.getElementById('add-adjustment').addEventListener('click', () => {
                addRow().querySelector('.student-search').focus();
            });

            // 行の中の操作は、表（tbody）でまとめて受け取る（あとから追加した行でも動くように）
            tbody.addEventListener('input', (e) => {
                if (!e.target.matches('.student-search')) return;
                const row = e.target.closest('tr');
                // 名前を打ち直したら、選んでいた生徒は取り消す（名前と中身が食い違わないように）
                field(row, 'student_id').value = '';
                row.querySelector('.current-class').textContent = '';
                renderResults(row);
            });
            tbody.addEventListener('focusin', (e) => {
                if (e.target.matches('.student-search') && field(e.target.closest('tr'), 'student_id').value === '') {
                    renderResults(e.target.closest('tr'));
                }
            });
            tbody.addEventListener('change', (e) => {
                if (e.target.matches('[data-field="action"]')) syncRow(e.target.closest('tr'));
            });
            // 候補を押しても入力欄からフォーカスが外れないようにする（外れると一覧が閉じて、クリックが届かない）
            tbody.addEventListener('mousedown', (e) => {
                if (e.target.closest('li')) e.preventDefault();
            });
            tbody.addEventListener('click', (e) => {
                const row = e.target.closest('tr');
                const li = e.target.closest('li[role="option"]');
                if (li) {
                    choose(row, students.find((s) => s.id === Number(li.dataset.id)));
                } else if (e.target.closest('.remove-row')) {
                    row.remove();
                }
                update();
            });
            // Enter でいちばん上の候補を選ぶ
            tbody.addEventListener('keydown', (e) => {
                if (e.key !== 'Enter' || !e.target.matches('.student-search')) return;
                if (e.isComposing || e.keyCode === 229) return; // 日本語入力の変換中は何もしない
                const first = e.target.closest('tr').querySelector('li[role="option"]');
                if (first) {
                    choose(e.target.closest('tr'), students.find((s) => s.id === Number(first.dataset.id)));
                    update();
                }
            });

            // Enter キーで、途中のステップからフォームが送信されないようにする（送信は最後の「登録」ボタンだけ）
            form.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && e.target.matches('input:not([type="checkbox"]):not([type="radio"])')) {
                    e.preventDefault();
                }
            });

            // ---------- 3. 人数と件数を数えて、進級の表と確認の表に出す ----------
            // （画面に見せるための数字。実際の判定は、登録のときにサーバーでもう一度行う）
            function update() {
                // 生徒ID => 処理（'repeat' か 'withdraw'）
                const actions = new Map();
                tbody.querySelectorAll('tr').forEach((row) => {
                    const id = Number(field(row, 'student_id').value);
                    if (id > 0) actions.set(id, field(row, 'action').value);
                });

                const counts = { promoted: 0, graduated: 0, repeated: 0, withdrawn: 0 };
                const promotedByGrade = {}; // 今の学年 => 進級する人数
                students.forEach((s) => {
                    const action = actions.get(s.id);
                    if (action === 'withdraw') {
                        counts.withdrawn++;
                    } else if (action === 'repeat') {
                        counts.repeated++;
                    } else if (s.grade >= s.years) {
                        counts.graduated++; // 学科の最後の学年なら卒業
                    } else {
                        counts.promoted++;
                        promotedByGrade[s.grade] = (promotedByGrade[s.grade] ?? 0) + 1;
                    }
                });

                // 登録される行の数（「追加」を押していない予備の行・チェックを外した行・名前が空の行は数えない）
                const countRows = (name) => [...document.querySelectorAll(`[data-copy-table="${name}"] tbody tr`)]
                    .filter((row) => {
                        const added = row.querySelector('[name$="[added]"]');
                        return (added.type === 'hidden' || added.checked)
                            && row.querySelector('[name$="[copy]"]').checked
                            && row.querySelector('[name$="[name]"]').value.trim() !== '';
                    }).length;

                // 教師コピー：チェックした教師（続ける）と、外した教師（ログインできなくなる）の人数
                const teacherBoxes = [...document.querySelectorAll('[data-teacher-table] input[type="checkbox"]')];
                const teachers = teacherBoxes.filter((box) => box.checked).length;

                const values = {
                    ...counts,
                    year: yearInput.value === '' ? '' : `${yearInput.value}年度`,
                    range: `${startInput.value.replaceAll('-', '/')}～${endInput.value.replaceAll('-', '/')}`,
                    teachers,
                    teachersOff: teacherBoxes.length - teachers,
                    classes: countRows('classes'),
                    subjects: countRows('subjects'),
                };
                document.querySelectorAll('[data-count]').forEach((el) => {
                    el.textContent = values[el.dataset.count];
                });
                document.querySelectorAll('[data-promote]').forEach((el) => {
                    el.textContent = promotedByGrade[el.dataset.promote] ?? 0;
                });
            }

            // フォームのどこかを変えたら数え直す
            form.addEventListener('input', update);
            form.addEventListener('change', update);

            // 入力エラーで戻ってきたときは、さっきの個別調整を元に戻す
            savedAdjustments.forEach((saved) => addRow(saved));
            update();
        });
    </script>
@endpush
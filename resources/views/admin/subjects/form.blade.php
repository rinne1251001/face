@extends('layouts.test')
@section('title', ($subject->exists ? '授業の編集' : '授業の追加').'（'.$year->label.'）')
@push('styles')
    <style>
        :root {
            --color-font-light1: #51626F;
            --color-font-light2: #829AAB;
            --color-border: #ececf1;
        }

        .page-header {
            color: var(--color-main);
            border-bottom: 1.5px solid var(--color-main);
        }

        form {
            display: grid;
            gap: 20px;
        }

        .inp-container p {
            color: var(--color-error-font);
            font-size: 0.8em;
            padding: 3px 0 0 10px;
        }

        .inp-hov {
            display: grid;
            border: var(--color-border) 1.5px solid;
            outline: transparent 0px solid;
            border-radius: 5px;
            padding: 5px 8px;
            transition: border-color 0.3s ease, outline 0.3s ease;
        }

        .inp-hov:hover {
            border: var(--color-main) 1.5px solid;
            outline: var(--color-main) 0.5px solid;
        }

        .inp-hov input {
            min-width: 0;
            border: none;
            font-size: 1.15em;
            padding: 2px;
            outline: none;
        }

        .inp-hov select {
            min-width: 0;
            border: none;
            font-size: 1.15em;
            padding: 2px;
            outline: none;
        }

        .inp-hov label {
            color: var(--color-font-light1);
            font-size: 0.9em;
            padding: 2px;
            transition: color 0.3s ease;
        }

        .inp-hov:hover label {
            color: var(--color-main);
        }

        .check-container {
            position: relative;
            justify-self: start;
            padding: 5px 20px;
            border: 1.5px solid var(--color-font-light);
            border-radius: 30px;
        }

        .check-container input[type="checkbox"] {
            opacity: 0;
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 100%;
            margin: 0;
            cursor: pointer;
        }

        .check-container > div {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .check-container label {
            font-size: 1em;
        }

        .check-container svg {
            width: 19.2px;
            height: 15.6px;
            stroke: var(--color-font-light);
            margin-top: 2px;
        }

        .check-container input[type="checkbox"]:checked~div svg {
            stroke: var(--color-bg);
        }

        .check-container input[type="checkbox"]:checked~div label {
            color: var(--color-bg)
        }

        .check-container:has(input[type="checkbox"]:checked) {
            background-color: var(--color-main);
            border: 1.5px solid var(--color-main);
        }

        /* 曜日と時限 */
        .same-periods {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 2px;
        }

        .schedule-list {
            display: grid;
            gap: 8px;
            padding: 6px 0;
        }

        .schedule-row {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start; /* 右側の欄が何行になっても、曜日は上にそろえる */
            gap: 12px;
        }

        .schedule-row .check-container {
            min-width: 120px;
        }

        /* 曜日の右側（時限と、教室・担当教師の欄） */
        .schedule-detail {
            display: grid;
            gap: 6px;
            flex: 1;
            min-width: 0;
        }

        /* チェックしていない曜日の時限は見えなくする（教室・担当教師の欄は、場所も取らないように消す） */
        .schedule-row:not(:has(input[name="days[]"]:checked)) .schedule-periods {
            visibility: hidden;
        }

        .schedule-row:not(:has(input[name="days[]"]:checked)) :is(.room-list, .room-add) {
            display: none;
        }

        /* 教室と担当教師の欄（クラスごと・教室ごとに1行） */
        .room-list {
            display: grid;
            gap: 6px;
        }

        .room-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
        }

        /* クラス名（教室だけの行では空なので、場所を取らない） */
        .room-class {
            min-width: 3.5em;
            font-weight: bold;
            color: var(--color-main);
        }

        .room-class:empty {
            display: none;
        }

        .room-row select {
            border: 1.5px solid var(--color-border);
            border-radius: 5px;
            padding: 4px 6px;
            font-size: 1em;
            max-width: 100%;
        }

        .room-row select:hover,
        .room-row select:focus {
            border-color: var(--color-main);
        }

        .room-row .chip-remove {
            background-color: var(--color-border);
            color: var(--color-font-light1);
        }

        .room-row .chip-remove:hover {
            background-color: var(--color-font-light2);
            color: var(--color-bg);
        }

        .room-add {
            justify-self: start;
            border: none;
            background: none;
            padding: 2px 4px;
            color: var(--color-main);
            font-size: 0.95em;
            cursor: pointer;
        }

        .room-add:hover {
            text-decoration: underline;
        }

        /* 欄の下の説明（.inp-container p は入力エラー用の赤い文字なので、説明は div にする） */
        .hint {
            color: var(--color-font-light1);
            font-size: 0.85em;
            padding: 4px 2px 0;
        }

        .schedule-periods select {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            cursor: pointer;
        }

        .search-container {
            position: relative;
            width: 100%;
        }
        .search-container input {
            outline: none;
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
        /* キーボード（↑↓）で選んでいる候補 */
        .search-container li.active {
            filter: brightness(0.9);
        }
        /* 選んだ担当教師・クラス */
        .chip-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            padding: 8px 0 2px;
        }
        /* 1つも選んでいなければ隠す（:empty だと、Blade の改行・空白があるだけで効かなくなる） */
        .chip-list:not(:has(.chip)) {
            display: none;
        }
        .chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 6px 4px 14px;
            border-radius: 30px;
            background-color: var(--color-main);
            color: var(--color-bg);
        }
        .chip-remove {
            display: grid;
            place-items: center;
            width: 22px;
            height: 22px;
            border: none;
            border-radius: 50%;
            background-color: rgb(255 255 255 / 25%);
            color: inherit;
            cursor: pointer;
        }
        .chip-remove:hover {
            background-color: rgb(255 255 255 / 45%);
        }
    </style>
@endpush
@section('content')
<main class="main">
    <div class="content-wrapper">
        <div class="page-header">
            <h1>{{ $subject->exists ? '授業の編集' : '授業の追加' }}（{{ $year->label }}）</h1>
        </div>

        @include('admin.partials.year-lock-notice', ['year' => $year])

        <form method="POST" action="{{ $subject->exists ? route('admin.subjects.update', $subject) : route('admin.subjects.store') }}">
            @csrf
            @if ($subject->exists)
                @method('PUT')
            @else
                {{-- どの年度に授業を作るか（編集のときは、授業の年度のまま） --}}
                <input type="hidden" name="year" value="{{ $year->year }}">
            @endif

            {{-- 読み取り専用の年度では、入力欄をまとめて押せなくする --}}
            <fieldset @disabled(! $year->isEditable()) style="border: none; margin: 0; padding: 0; min-width: 0;">

            <div class="inp-container">
                <div class="inp-hov">
                    <label for="name">授業名</label>
                    <input id="name" name="name" value="{{ old('name', $subject->name) }}" placeholder="例：数学" required autofocus>
                </div>
                @if ($errors->has('name'))
                    @foreach ($errors->get('name') as $message)
                        <p>{{ $message }}</p>
                    @endforeach
                @endif
            </div>

            <div class="inp-container">
                <div class="inp-hov">
                    <label for="grade">学年</label>
                    <select id="grade" name="grade" required>
                        @for ($g = 1; $g <= $maxGrade; $g++)
                            <option value="{{ $g }}" @selected((int) old('grade', $subject->grade) === $g)>{{ $g }}年</option>
                        @endfor
                    </select>
                </div>
                @foreach ($errors->get('grade') as $message)
                    <p>{{ $message }}</p>
                @endforeach
            </div>

            {{-- 担当教師（複数可）：検索して選ぶと、下の一覧に追加される --}}
            <div class="inp-container">
                <div class="inp-hov">
                    <label for="teacher-search">担当教師<span style="font-size: 0.9em;">（名前・ログインIDで検索して選ぶ。何人でも追加できます）</span></label>
                    <div class="search-container">
                        {{-- name を付けないので、検索の文字そのものは送信されない --}}
                        <input type="search" id="teacher-search" placeholder="名前で検索" autocomplete="off"
                               role="combobox" aria-controls="teacher-results" aria-expanded="false" aria-autocomplete="list">
                        <ul id="teacher-results" role="listbox" aria-label="検索結果"></ul>
                    </div>

                    {{-- 選んだ教師。中の hidden の teacher_ids[] が送信される --}}
                    <div class="chip-list" id="teacher-chips">
                        @foreach ($selectedTeachers as $teacher)
                            <span class="chip" data-id="{{ $teacher->id }}">
                                <input type="hidden" name="teacher_ids[]" value="{{ $teacher->id }}">
                                {{ $teacher->name }} 先生
                                <button type="button" class="chip-remove" aria-label="{{ $teacher->name }} 先生を外す">×</button>
                            </span>
                        @endforeach
                    </div>
                </div>
                @foreach (collect($errors->get('teacher_ids'))->merge($errors->get('teacher_ids.*'))->flatten() as $message)
                    <p>{{ $message }}</p>
                @endforeach
            </div>

            {{-- クラス（複数可・選ばなくてもよい）：担当教師と同じように、検索して選ぶと下の一覧に追加される --}}
            <div class="inp-container">
                <div class="inp-hov">
                    <label for="class-search">クラス<span style="font-size: 0.9em;">（クラス名で検索して選ぶ。何クラスでも追加できます。選ばなくても保存できます）</span></label>
                    <div class="search-container">
                        {{-- name を付けないので、検索の文字そのものは送信されない --}}
                        <input type="search" id="class-search" placeholder="クラス名で検索" autocomplete="off"
                               role="combobox" aria-controls="class-results" aria-expanded="false" aria-autocomplete="list">
                        <ul id="class-results" role="listbox" aria-label="検索結果"></ul>
                    </div>

                    {{-- 選んだクラス。中の hidden の class_ids[] が送信される --}}
                    <div class="chip-list" id="class-chips">
                        @foreach ($selectedClasses as $class)
                            <span class="chip" data-id="{{ $class->id }}">
                                <input type="hidden" name="class_ids[]" value="{{ $class->id }}">
                                {{ $class->name }}
                                <button type="button" class="chip-remove" aria-label="{{ $class->name }}を外す">×</button>
                            </span>
                        @endforeach
                    </div>
                    <div class="hint">選んだクラスの生徒は、保存すると全員この授業の受講生になります。クラスを外して保存すると、そのクラスの生徒は受講生からも外れます。</div>
                </div>
                @foreach (collect($errors->get('class_ids'))->merge($errors->get('class_ids.*'))->flatten() as $message)
                    <p>{{ $message }}</p>
                @endforeach
            </div>

            @php
                // チェックが入っている曜日（入力エラーで戻ってきたときは、さっきの状態）
                $checkedDays = array_map('intval', old('days', $subject->schedules->pluck('day_of_week')->all()));
            @endphp
            <div class="inp-container">
                <div class="inp-hov">
                    <label>曜日と時限、教室と担当教師<span style="font-size: 0.9em;">（授業がある曜日にチェックして、時限を選ぶ。1限だけの授業は「1限〜1限」）</span></label>

                    <label class="same-periods">
                        <input type="checkbox" id="same-periods" name="same_periods" value="1" @checked(old('same_periods'))>
                        全ての曜日を同じ時限にする
                    </label>
                    <div class="hint">
                        教室と担当教師は、クラスを選んだときはクラスごとに、選ばないときは教室ごとに決めます（どちらも「未設定」のままで保存できます）。
                        「＋ 追加」を押すと、教室と担当教師の欄が増えます。担当教師は、上の「担当教師」で選んだ教師から選びます。
                    </div>

                    <div class="schedule-list" id="schedule-list">
                        @foreach (\App\Models\Subject::DAYS as $day => $label)
                            @php($schedule = $subject->schedules->firstWhere('day_of_week', $day))
                            <div class="schedule-row">
                                <div class="check-container">
                                    <input type="checkbox" id="day-{{ $day }}" name="days[]" value="{{ $day }}" @checked(in_array($day, $checkedDays, true))>
                                    <div>
                                        <svg><use href="#check" /></svg>
                                        <label for="day-{{ $day }}">{{ $label }}曜日</label>
                                    </div>
                                </div>
                                <div class="schedule-detail">
                                    <div class="schedule-periods">
                                        <select name="schedules[{{ $day }}][period_start]" class="period-start" aria-label="{{ $label }}曜日の開始時限">
                                            @for ($p = 1; $p <= \App\Models\Subject::maxPeriod(); $p++)
                                                <option value="{{ $p }}" @selected(old("schedules.$day.period_start", $schedule?->period_start ?? 1) == $p)>{{ $p }}限</option>
                                            @endfor
                                        </select>
                                        〜
                                        <select name="schedules[{{ $day }}][period_end]" class="period-end" aria-label="{{ $label }}曜日の終了時限">
                                            @for ($p = 1; $p <= \App\Models\Subject::maxPeriod(); $p++)
                                                <option value="{{ $p }}" @selected(old("schedules.$day.period_end", $schedule?->period_end ?? 1) == $p)>{{ $p }}限</option>
                                            @endfor
                                        </select>
                                    </div>
                                    {{-- 教室と担当教師の欄（クラスごと・教室ごとに1行）。行は下の JavaScript が作る --}}
                                    <div class="room-list" data-day="{{ $day }}" data-label="{{ $label }}曜日"></div>
                                    <button type="button" class="room-add">＋ 追加</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                {{-- 同じ文が何行分も出ないように、同じ文は1つにまとめる --}}
                @foreach (collect($errors->get('days'))->merge($errors->get('days.*'))->merge($errors->get('schedules.*'))->flatten()->unique() as $message)
                    <p>{{ $message }}</p>
                @endforeach
            </div>

            {{-- 教室と担当教師の1行分のひな形（JavaScript がコピーして使う。<template> の中身は画面に出ず、送信もされない） --}}
            <template id="room-row-template">
                <div class="room-row">
                    <span class="room-class"></span>
                    <input type="hidden" data-field="school_class_id">
                    <select data-field="classroom_id">
                        <option value="">教室：未設定</option>
                        @foreach ($classrooms as $classroom)
                            <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                        @endforeach
                    </select>
                    <select data-field="teacher_id">
                        <option value="">教師：未設定</option>
                    </select>
                    <button type="button" class="chip-remove room-remove" aria-label="この欄を削除">×</button>
                </div>
            </template>

            <div class="inp-container">
                <div class="inp-hov">
                    <label for="start_date">授業開始日（空なら年度の初めから）</label>
                    {{-- 年度の外の日は選べないようにする（サーバーでもチェックする） --}}
                    <input type="date" id="start_date" name="start_date" value="{{ old('start_date', $subject->start_date?->format('Y-m-d')) }}" min="{{ $year->start_date->format('Y-m-d') }}" max="{{ $year->end_date->format('Y-m-d') }}">
                </div>
                @if ($errors->has('start_date'))
                    @foreach ($errors->get('start_date') as $message)
                        <p>{{ $message }}</p>
                    @endforeach
                @endif
            </div>

            <div class="inp-container">
                <div class="inp-hov">
                    <label for="end_date">授業終了日（空なら年度の終わりまで）</label>
                    <input type="date" id="end_date" name="end_date" value="{{ old('end_date', $subject->end_date?->format('Y-m-d')) }}" min="{{ $year->start_date->format('Y-m-d') }}" max="{{ $year->end_date->format('Y-m-d') }}">
                </div>
                @if ($errors->has('end_date'))
                    @foreach ($errors->get('end_date') as $message)
                        <p>{{ $message }}</p>
                    @endforeach
                @endif
            </div>

            </fieldset>

            <div>
                @if ($year->isEditable())
                    <button class="btn">保存</button>
                @endif
                <a href="{{ route('admin.subjects.index', ['year' => $year->year]) }}">授業一覧へ戻る</a>
            </div>
        </form>
    </div>
</main>
@endsection
@push('scripts')
    <script>
        // 「全ての曜日を同じ時限にする」：どこかの時限を変えたら、全ての曜日を同じ時限にそろえる
        document.addEventListener('DOMContentLoaded', () => {
            const same = document.getElementById('same-periods');
            const list = document.getElementById('schedule-list');

            // ある曜日の時限を、全ての曜日にコピーする
            function copyFrom(row) {
                const start = row.querySelector('.period-start').value;
                const end = row.querySelector('.period-end').value;
                list.querySelectorAll('.period-start').forEach((s) => { s.value = start; });
                list.querySelectorAll('.period-end').forEach((s) => { s.value = end; });
            }

            // チェックを入れたとき：チェックしてある最初の曜日（なければ月曜）の時限にそろえる
            same.addEventListener('change', () => {
                if (!same.checked) return;
                const first = list.querySelector('input[name="days[]"]:checked') ?? list.querySelector('input[name="days[]"]');
                copyFrom(first.closest('.schedule-row'));
            });

            // 時限を変えたとき：チェックが入っていれば、全ての曜日にコピーする
            list.addEventListener('change', (e) => {
                if (same.checked && e.target.matches('.period-start, .period-end')) {
                    copyFrom(e.target.closest('.schedule-row'));
                }
            });
        });

        // 担当教師・クラス（検索して選ぶ）と、教室・担当教師の欄
        document.addEventListener('DOMContentLoaded', () => {
            const teachers = @json($teacherOptions);  // [{ id, name, login_id }]
            const classes = @json($classOptions);     // [{ id, name, grade, classroom_id, students }]
            const savedRooms = @json($rooms);         // { 曜日: [{ school_class_id, classroom_id, teacher_id }, ...] }

            // 全角・半角、大文字・小文字、空白の違いを無視して比べるための変換
            const norm = (s) => String(s ?? '').normalize('NFKC').toLowerCase().replace(/\s+/g, '');

            // ---- 検索して選ぶ欄（担当教師とクラスで、同じ動きを使い回す） ----
            //   key      … 'teacher' か 'class'（画面の id の先頭に使う：teacher-search・teacher-results・teacher-chips）
            //   items    … 候補の一覧 [{ id, ... }]
            //   name     … 送信するときの名前（例：'teacher_ids[]'）
            //   text     … 表示する名前を返す関数、sub … 名前の横に小さく出す文字を返す関数
            //   words    … 検索の対象にする文字の配列を返す関数
            //   noItems  … 候補が1件も登録されていないときの文
            //   onChange … 追加・削除したあとに呼ぶ関数
            // 戻り値：{ ids } … ids() で、いま選んでいる ID の配列（選んだ順）が取れる
            function setupPicker({ key, items, name, text, sub, words, noItems, onChange }) {
                const search = document.getElementById(`${key}-search`);
                const results = document.getElementById(`${key}-results`);
                const selected = document.getElementById(`${key}-chips`); // 選んだものを並べる場所
                let active = -1; // ↑↓キーで選んでいる候補の番号（-1 はどれも選んでいない）

                const ids = () => [...selected.querySelectorAll('.chip')].map((chip) => Number(chip.dataset.id));
                const options = () => [...results.querySelectorAll('li[role="option"]')];

                // 検索結果の一覧を作り直す（追加済みのものは出さない。文字が空なら全部）
                function renderResults() {
                    const word = norm(search.value);
                    const chosen = ids();
                    const hits = items.filter((item) => !chosen.includes(item.id)
                        && words(item).some((w) => norm(w).includes(word)));

                    results.replaceChildren(...hits.map((item) => {
                        const li = document.createElement('li');
                        li.id = `${key}-option-${item.id}`;
                        li.setAttribute('role', 'option');
                        li.dataset.id = item.id;
                        const main = document.createElement('span');
                        main.textContent = text(item); // textContent なので、名前に記号があっても安全
                        const small = document.createElement('span');
                        small.textContent = sub(item);
                        li.append(main, small);
                        return li;
                    }));

                    if (hits.length === 0) {
                        const li = document.createElement('li');
                        li.className = 'empty';
                        li.textContent = items.length === 0 ? noItems
                            : chosen.length === items.length ? '全て追加済みです' : '見つかりません';
                        results.append(li);
                    }
                    search.setAttribute('aria-expanded', 'true');
                    setActive(-1);
                }

                // 候補の一覧を閉じる（中身を空にすると、CSS の ul:has(> li) に当てはまらなくなって消える）
                function closeResults() {
                    results.replaceChildren();
                    search.setAttribute('aria-expanded', 'false');
                    setActive(-1);
                }

                // ↑↓キーで選んでいる候補に印を付ける
                function setActive(index) {
                    const list = options();
                    active = list.length === 0 ? -1 : (index + list.length) % list.length;
                    if (index < 0) active = -1;
                    list.forEach((li, i) => {
                        li.classList.toggle('active', i === active);
                        li.setAttribute('aria-selected', i === active ? 'true' : 'false');
                    });
                    if (active >= 0) {
                        search.setAttribute('aria-activedescendant', list[active].id);
                        list[active].scrollIntoView({ block: 'nearest' });
                    } else {
                        search.removeAttribute('aria-activedescendant');
                    }
                }

                // 下の一覧に追加する
                function add(id) {
                    const item = items.find((i) => i.id === id);
                    if (!item || ids().includes(id)) return;

                    const chip = document.createElement('span');
                    chip.className = 'chip';
                    chip.dataset.id = item.id;

                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = name;
                    hidden.value = item.id;

                    const remove = document.createElement('button');
                    remove.type = 'button'; // type="button" にしないと、押したときにフォームが送信される
                    remove.className = 'chip-remove';
                    remove.textContent = '×';
                    remove.setAttribute('aria-label', `${text(item)}を外す`);

                    chip.append(hidden, text(item), remove);
                    selected.append(chip);
                    search.value = '';
                    // 追加したら候補の一覧を閉じる（開いたままだと、下に追加したものの×ボタンが一覧に隠れて押せない）
                    // 入力欄にはフォーカスが残るので、続けて文字を打てば、また一覧が開く
                    closeResults();
                    onChange();
                }

                // 入力欄を選んだとき・クリックしたとき・文字を打ったときに、候補の一覧を開く
                search.addEventListener('focus', renderResults);
                search.addEventListener('click', () => {
                    if (results.children.length === 0) renderResults(); // 追加して閉じたあとに、もう一度クリックしたとき
                });
                search.addEventListener('input', renderResults);
                search.addEventListener('blur', () => search.setAttribute('aria-expanded', 'false'));

                search.addEventListener('keydown', (e) => {
                    if (e.isComposing || e.keyCode === 229) return; // 日本語入力の変換中は何もしない
                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        if (results.children.length === 0) renderResults(); // 閉じていたら開く
                        setActive(active + 1);
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        setActive(active < 0 ? -1 : active - 1);
                    } else if (e.key === 'Enter') {
                        e.preventDefault(); // Enter で授業のフォームが送信されないようにする
                        const list = options();
                        // ↑↓で選んだ候補、なければ（文字を入れているときだけ）いちばん上の候補を追加する
                        const target = list[active] ?? (search.value.trim() !== '' ? list[0] : null);
                        if (target) add(Number(target.dataset.id));
                    } else if (e.key === 'Escape') {
                        search.value = '';
                        search.blur();
                    }
                });

                // 候補を押しても入力欄からフォーカスが外れないようにする（外れると一覧が閉じて、クリックが届かない）
                // 「見つかりません」などの文を押したときは止めない（フォーカスが外れて一覧が閉じ、下の欄を押せるようになる）
                results.addEventListener('mousedown', (e) => {
                    if (e.target.closest('li[role="option"]')) e.preventDefault();
                });
                results.addEventListener('click', (e) => {
                    const li = e.target.closest('li[role="option"]');
                    if (li) add(Number(li.dataset.id));
                });

                // ×ボタンで外す
                selected.addEventListener('click', (e) => {
                    const button = e.target.closest('.chip-remove');
                    if (!button) return;
                    button.closest('.chip').remove();
                    onChange();
                });

                return { ids };
            }

            // 担当教師：外したり追加したりしたら、下の欄の「教師」の選択肢を作り直す
            const teacherPicker = setupPicker({
                key: 'teacher',
                items: teachers,
                name: 'teacher_ids[]',
                text: (t) => `${t.name} 先生`,
                sub: (t) => t.login_id,
                words: (t) => [t.name, t.login_id],
                noItems: '教師が登録されていません',
                onChange: () => fillTeachers(),
            });

            // クラス：外したり追加したりしたら、下の欄の「クラスごとの行」をそろえ直す
            const classPicker = setupPicker({
                key: 'class',
                items: classes,
                name: 'class_ids[]',
                text: (c) => c.name,
                sub: (c) => `${c.grade}年・${c.students}人`,
                words: (c) => [c.name],
                noItems: 'この年度のクラスが登録されていません',
                onChange: () => syncClassRows(),
            });

            // ---- 教室と担当教師の欄（曜日ごと。クラスごと・教室ごとに1行） ----
            const lists = [...document.querySelectorAll('.room-list')]; // 曜日ごとの入れ物
            const template = document.getElementById('room-row-template');

            // 1行作る（room … { school_class_id, classroom_id, teacher_id }。空の行なら {}）
            function createRow(list, room) {
                const row = template.content.firstElementChild.cloneNode(true);
                const cls = classes.find((c) => c.id === room.school_class_id);
                const who = `${list.dataset.label}${cls ? ` ${cls.name}` : ''}`; // 読み上げ用（例：月曜日 1T1）

                row.dataset.classId = cls ? cls.id : ''; // クラスの行か、教室だけの行か（'' なら教室だけの行）
                row.querySelector('.room-class').textContent = cls ? cls.name : '';
                row.querySelector('[data-field="school_class_id"]').value = cls ? cls.id : '';

                const classroom = row.querySelector('[data-field="classroom_id"]');
                classroom.value = room.classroom_id ?? ''; // 選択肢にない教室（削除された教室）なら「未設定」になる
                if (classroom.selectedIndex < 0) classroom.value = '';
                classroom.setAttribute('aria-label', `${who} の教室`);

                // 教師の選択肢は fillTeachers() が入れるので、選んでおきたい教師だけ覚えておく
                const teacher = row.querySelector('[data-field="teacher_id"]');
                teacher.dataset.want = room.teacher_id ?? '';
                teacher.setAttribute('aria-label', `${who} の担当教師`);

                // クラスの行は × で消せない（上の「クラス」から外すと消える）
                if (cls) row.querySelector('.room-remove').remove();

                return row;
            }

            // 「教師」の選択肢を、上の「担当教師」で選んだ教師にそろえる（担当教師から外した教師は「未設定」に戻る）
            function fillTeachers() {
                const chosen = teacherPicker.ids().map((id) => teachers.find((t) => t.id === id)).filter(Boolean);

                document.querySelectorAll('.room-row [data-field="teacher_id"]').forEach((select) => {
                    const current = select.dataset.want || select.value; // 作ったばかりの行は want、それ以外は今の値
                    delete select.dataset.want;
                    select.replaceChildren(
                        new Option('教師：未設定', ''),
                        ...chosen.map((t) => new Option(`${t.name} 先生`, t.id)),
                    );
                    select.value = chosen.some((t) => String(t.id) === String(current)) ? current : '';
                });
            }

            // 送信するときの名前を、上から順の番号で付け直す（例：schedules[1][rooms][0][classroom_id]）
            function renumber() {
                lists.forEach((list) => {
                    [...list.children].forEach((row, i) => {
                        row.querySelectorAll('[data-field]').forEach((field) => {
                            field.name = `schedules[${list.dataset.day}][rooms][${i}][${field.dataset.field}]`;
                        });
                    });
                });
            }

            // クラスごとの行をそろえる：選んだクラスごとに1行。外したクラスの行は消す
            function syncClassRows() {
                const chosen = classPicker.ids();

                lists.forEach((list) => {
                    const hadClassRows = list.querySelector('.room-row:not([data-class-id=""])') !== null;

                    // 外したクラスの行を消す
                    list.querySelectorAll('.room-row:not([data-class-id=""])').forEach((row) => {
                        if (!chosen.includes(Number(row.dataset.classId))) row.remove();
                    });

                    // まだ行がないクラスの行を作る（教室は、そのクラスのホームルームの教室にしておく）
                    chosen.forEach((id) => {
                        if (list.querySelector(`.room-row[data-class-id="${id}"]`)) return;
                        const cls = classes.find((c) => c.id === id);
                        const row = createRow(list, { school_class_id: id, classroom_id: cls?.classroom_id ?? null });
                        // クラスの行は、教室だけの行より上に並べる（教室だけの行がなければ、いちばん下）
                        list.insertBefore(row, list.querySelector('.room-row[data-class-id=""]'));
                    });

                    // 教室だけの行
                    if (chosen.length === 0) {
                        // クラスを選んでいないとき：教室ごとに決めるので、1行もなければ空の行を出す
                        if (list.children.length === 0) list.append(createRow(list, {}));
                    } else if (!hadClassRows) {
                        // 初めてクラスを選んだとき：何も選んでいない教室だけの行は、もういらないので消す
                        list.querySelectorAll('.room-row[data-class-id=""]').forEach((row) => {
                            const empty = [...row.querySelectorAll('select')].every((s) => (s.dataset.want || s.value) === '');
                            if (empty) row.remove();
                        });
                    }
                });

                fillTeachers();
                renumber();
            }

            // 最初の表示：保存してある行（入力エラーで戻ってきたときは、さっきの入力）を並べる
            lists.forEach((list) => {
                (savedRooms[list.dataset.day] ?? []).forEach((room) => list.append(createRow(list, room)));
            });
            syncClassRows();

            // 「＋ 追加」：その曜日に、教室と担当教師の欄を1行増やす
            document.getElementById('schedule-list').addEventListener('click', (e) => {
                const addButton = e.target.closest('.room-add');
                if (addButton) {
                    const list = addButton.closest('.schedule-detail').querySelector('.room-list');
                    const row = createRow(list, {});
                    list.append(row);
                    fillTeachers();
                    renumber();
                    row.querySelector('select').focus();
                }

                // 行の ×：その欄を消す
                const removeButton = e.target.closest('.room-remove');
                if (removeButton) {
                    removeButton.closest('.room-row').remove();
                    renumber();
                }
            });
        });
    </script>
    <svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" style="display: none;">
        <symbol id="check" viewBox="0 0 32 26">
            <path d="m4.5 14.4 7 7 16-17" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" fill="none"/>
        </symbol>
    </svg>
@endpush
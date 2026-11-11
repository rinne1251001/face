@extends('layouts.test')
@section('title', $subject->exists ? '授業の編集' : '授業の追加')
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
            align-items: center;
            gap: 12px;
        }

        .schedule-row .check-container {
            min-width: 120px;
        }

        /* チェックしていない曜日の時限は見えなくする */
        .schedule-row:not(:has(input[name="days[]"]:checked)) .schedule-periods {
            visibility: hidden;
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
        /* 選んだ担当教師 */
        .selected-teachers {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            padding: 8px 0 2px;
        }
        /* 教師が0人なら隠す（:empty だと、Blade の改行・空白があるだけで効かなくなる） */
        .selected-teachers:not(:has(.teacher-chip)) {
            display: none;
        }
        .teacher-chip {
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
            <h1>{{ $subject->exists ? '授業の編集' : '授業の追加' }}</h1>
        </div>

        <form method="POST" action="{{ $subject->exists ? route('admin.subjects.update', $subject) : route('admin.subjects.store') }}">
            @csrf
            @if ($subject->exists)
                @method('PUT')
            @endif

            <div class="inp-container">
                <div class="inp-hov">
                    <label id="name">授業名</label>
                    <input for="name" name="name" value="{{ old('name', $subject->name) }}" placeholder="例：数学" required autofocus>
                </div>
                @if ($errors->has('name'))
                    @foreach ($errors->get('name') as $message)
                        <p>{{ $message }}</p>
                    @endforeach
                @endif
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
                    <div class="selected-teachers" id="selected-teachers">
                        @foreach ($selectedTeachers as $teacher)
                            <span class="teacher-chip" data-id="{{ $teacher->id }}" data-name="{{ $teacher->name }}">
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

            @php
                // チェックが入っている曜日（入力エラーで戻ってきたときは、さっきの状態）
                $checkedDays = array_map('intval', old('days', $subject->schedules->pluck('day_of_week')->all()));
            @endphp
            <div class="inp-container">
                <div class="inp-hov">
                    <label>曜日と時限<span style="font-size: 0.9em;">（授業がある曜日にチェックして、時限を選ぶ。1限だけの授業は「1限〜1限」）</span></label>

                    <label class="same-periods">
                        <input type="checkbox" id="same-periods" name="same_periods" value="1" @checked(old('same_periods'))>
                        全ての曜日を同じ時限にする
                    </label>

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
                                    <select name="schedules[{{ $day }}][classroom_id]" aria-label="{{ $label }}曜日の教室">
                                        <option value="">教室：未設定</option>
                                        @foreach ($classrooms as $classroom)
                                            <option value="{{ $classroom->id }}" @selected(old("schedules.$day.classroom_id", $schedule?->classroom_id) == $classroom->id)>{{ $classroom->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @foreach (collect($errors->get('days'))->merge($errors->get('days.*'))->merge($errors->get('schedules.*'))->flatten() as $message)
                    <p>{{ $message }}</p>
                @endforeach
            </div>

            <div class="inp-container">
                <div class="inp-hov">
                    <label id="name">授業開始日</label>
                    <input type="date" name="start_date" value="{{ old('start_date', $subject->start_date?->format('Y-m-d')) }}">
                </div>
                @if ($errors->has('start_date'))
                    @foreach ($errors->get('start_date') as $message)
                        <p>{{ $message }}</p>
                    @endforeach
                @endif
            </div>

            <div class="inp-container">
                <div class="inp-hov">
                    <label id="name">授業終了日</label>
                    <input type="date" name="end_date" value="{{ old('end_date', $subject->end_date?->format('Y-m-d')) }}">
                </div>
                @if ($errors->has('end_date'))
                    @foreach ($errors->get('end_date') as $message)
                        <p>{{ $message }}</p>
                    @endforeach
                @endif
            </div>

            <div>
                <button class="btn">保存</button>
                <a href="{{ route('admin.subjects.index') }}">授業一覧へ戻る</a>
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

        // 担当教師：検索して選ぶと、下の一覧に追加される
        document.addEventListener('DOMContentLoaded', () => {
            const teachers = @json($teachers->map->only(['id', 'name', 'login_id'])->values());
            const search = document.getElementById('teacher-search');
            const results = document.getElementById('teacher-results');
            const selected = document.getElementById('selected-teachers');
            let active = -1; // ↑↓キーで選んでいる候補の番号（-1 はどれも選んでいない）

            // 全角・半角、大文字・小文字、空白の違いを無視して比べるための変換
            const norm = (s) => String(s ?? '').normalize('NFKC').toLowerCase().replace(/\s+/g, '');
            const chips = () => [...selected.querySelectorAll('.teacher-chip')];
            const selectedIds = () => chips().map((chip) => Number(chip.dataset.id));
            const options = () => [...results.querySelectorAll('li[role="option"]')];

            // 検索結果の一覧を作り直す（追加済みの教師は出さない。文字が空なら全員）
            function renderResults() {
                const word = norm(search.value);
                const chosen = selectedIds();
                const hits = teachers.filter((t) => !chosen.includes(t.id)
                    && (norm(t.name).includes(word) || norm(t.login_id).includes(word)));

                results.replaceChildren(...hits.map((t) => {
                    const li = document.createElement('li');
                    li.id = `teacher-option-${t.id}`;
                    li.setAttribute('role', 'option');
                    li.dataset.id = t.id;
                    const name = document.createElement('span');
                    name.textContent = `${t.name} 先生`; // textContent なので、名前に記号があっても安全
                    const loginId = document.createElement('span');
                    loginId.textContent = t.login_id;
                    li.append(name, loginId);
                    return li;
                }));

                if (hits.length === 0) {
                    const li = document.createElement('li');
                    li.className = 'empty';
                    li.textContent = teachers.length === 0 ? '教師が登録されていません'
                        : chosen.length === teachers.length ? '全員追加済みです' : '見つかりません';
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

            // 教師を下の一覧に追加する
            function addTeacher(id) {
                const teacher = teachers.find((t) => t.id === id);
                if (!teacher || selectedIds().includes(id)) return;

                const chip = document.createElement('span');
                chip.className = 'teacher-chip';
                chip.dataset.id = teacher.id;
                chip.dataset.name = teacher.name;

                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'teacher_ids[]';
                hidden.value = teacher.id;

                const remove = document.createElement('button');
                remove.type = 'button'; // type="button" にしないと、押したときにフォームが送信される
                remove.className = 'chip-remove';
                remove.textContent = '×';
                remove.setAttribute('aria-label', `${teacher.name} 先生を外す`);

                chip.append(hidden, `${teacher.name} 先生`, remove);
                selected.append(chip);
                search.value = '';
                // 追加したら候補の一覧を閉じる（開いたままだと、下に追加した教師の×ボタンが一覧に隠れて押せない）
                // 入力欄にはフォーカスが残るので、続けて名前を打てば、また一覧が開く
                closeResults();
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
                    if (target) addTeacher(Number(target.dataset.id));
                } else if (e.key === 'Escape') {
                    search.value = '';
                    search.blur();
                }
            });

            // 候補を押しても入力欄からフォーカスが外れないようにする（外れると一覧が閉じて、クリックが届かない）
            results.addEventListener('mousedown', (e) => e.preventDefault());
            results.addEventListener('click', (e) => {
                const li = e.target.closest('li[role="option"]');
                if (li) addTeacher(Number(li.dataset.id));
            });

            // ×ボタンで外す
            selected.addEventListener('click', (e) => {
                const button = e.target.closest('.chip-remove');
                if (!button) return;
                button.closest('.teacher-chip').remove();
            });
        });
    </script>
    <svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" style="display: none;">
        <symbol id="check" viewBox="0 0 32 26">
            <path d="m4.5 14.4 7 7 16-17" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" fill="none"/>
        </symbol>
    </svg>
@endpush
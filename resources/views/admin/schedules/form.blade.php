@extends('layouts.test')
@section('title', '休校・振替・変則授業の登録')
@push('styles')
    <style>
        .tab-container {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            place-items: center;
        }

        .tab-item {
            background-color: color-mix(in srgb, var(--color-main), var(--color-bg) 50%);
            color: var(--color-bg);
            font: inherit;
            font-weight: bold;
            width: 100%;
            padding: 10px;
            border: none;
            text-align: center;
            cursor: pointer;
        }

        .tab-item[aria-selected="true"] {
            background-color: var(--color-main);
        }

        .view-content {
            width: 100%;
            background-color: var(--color-sub);
            color: color-mix(in srgb, var(--color-font), var(--color-sub) 30%);
        }

        .view-content[hidden] {
            display: none;
        }

        .view-content form {
            display: grid;
            padding: 20px;
            gap: 10px;
        }
    </style>
@endpush

@section('content')
@php
    // バリデーションエラーで戻ってきたときは、送信した側のタブを開いた状態で表示する
    $activeTab = in_array(old('_tab'), ['special', 'change'], true) ? old('_tab') : 'day';
@endphp
<main class="main">
    <div class="content-wrapper">

        <div class="page-header">
            <h1>休校・振替・変則授業の登録</h1>
        </div>

        <div>
            <div class="tab-container" role="tablist">
                <button type="button" class="tab-item" role="tab" id="tab-day"
                        aria-controls="panel-day"
                        aria-selected="{{ $activeTab === 'day' ? 'true' : 'false' }}">休校・振替</button>
                <button type="button" class="tab-item" role="tab" id="tab-special"
                        aria-controls="panel-special"
                        aria-selected="{{ $activeTab === 'special' ? 'true' : 'false' }}">変則授業</button>
                <button type="button" class="tab-item" role="tab" id="tab-change"
                        aria-controls="panel-change"
                        aria-selected="{{ $activeTab === 'change' ? 'true' : 'false' }}">クラスの授業変更</button>
            </div>

            <div class="view-content" role="tabpanel" id="panel-day" aria-labelledby="tab-day" @if ($activeTab !== 'day') hidden @endif>
                <h2>休校・振替</h2>
                <form method="POST" action="{{ route('admin.schedules.days.store') }}">
                    @csrf
                    <input type="hidden" name="_tab" value="day">

                    <div>
                        <label for="day-date">日付</label>
                        <input id="day-date" type="date" name="date" value="{{ old('date', $date) }}" required>
                        @if ($errors->has('date'))
                            @foreach ($errors->get('date') as $message)
                                <p>{{ $message }}</p>
                            @endforeach
                        @endif
                    </div>

                    <div>
                        <label for="day_of_week">その日の授業</label>
                        <select id="day_of_week" name="day_of_week">
                            <option value="">授業なし（休校・学校行事など）</option>
                            @foreach (\App\Models\Subject::DAYS as $number => $label)
                                <option value="{{ $number }}" @selected(old('day_of_week') == $number)>{{ $label }}曜日の時間割で授業</option>
                            @endforeach
                        </select>
                        @if ($errors->has('day_of_week'))
                            @foreach ($errors->get('day_of_week') as $message)
                                <p>{{ $message }}</p>
                            @endforeach
                        @endif
                    </div>

                    <div>
                        <label for="day-note">メモ（任意）</label>
                        <input id="day-note" name="note" value="{{ old('note') }}" maxlength="100" placeholder="例：文化祭、11/3の振替">
                        @if ($errors->has('day_of_week'))
                            @foreach ($errors->get('day_of_week') as $message)
                                <p>{{ $message }}</p>
                            @endforeach
                        @endif
                    </div>

                    <div>
                        <button class="btn">登録する</button>
                    </div>
                </form>
            </div>

            <div class="view-content" role="tabpanel" id="panel-special" aria-labelledby="tab-special" @if ($activeTab !== 'special') hidden @endif>
                <h2>変則授業</h2>
                <form method="POST" action="{{ route('admin.schedules.special-classes.store') }}">
                    @csrf
                    <input type="hidden" name="_tab" value="special">

                    <div>
                        <label for="subject_id">授業</label>
                        <select id="subject_id" name="subject_id" required>
                            <option value="">選択してください</option>
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject->id }}" @selected(old('subject_id') == $subject->id)>{{ $subject->name }}</option>
                            @endforeach
                        </select>
                        @if ($errors->has('subject_id'))
                            @foreach ($errors->get('subject_id') as $message)
                                <p>{{ $message }}</p>
                            @endforeach
                        @endif
                    </div>

                    <div>
                        <label for="special-date">日付</label>
                        <input id="special-date" type="date" name="date" value="{{ old('date', $date) }}" required>
                        @if ($errors->has('date'))
                            @foreach ($errors->get('date') as $message)
                                <p>{{ $message }}</p>
                            @endforeach
                        @endif
                    </div>

                    <div>
                        <label for="period_start">時限</label>
                        <select name="to_period_start" id="change-to-period-start" aria-label="開始時限">
                            @for ($p = 1; $p <= \App\Models\Subject::maxPeriod(); $p++)
                                <option value="{{ $p }}" @selected(old('period_start', 1) == $p)>{{ $p }}限</option>
                            @endfor
                        </select>
                        〜
                        <select name="to_period_end" id="change-to-period-end" aria-label="終了時限">
                            @for ($p = 1; $p <= \App\Models\Subject::maxPeriod(); $p++)
                                <option value="{{ $p }}" @selected(old('period_end', 1) == $p)>{{ $p }}限</option>
                            @endfor
                        </select>
                        @foreach (collect($errors->get('days'))->merge($errors->get('days.*'))->merge($errors->get('schedules.*'))->flatten() as $message)
                            <p>{{ $message }}</p>
                        @endforeach
                    </div>

                    <div>
                        <label for="special-note">メモ（任意）</label>
                        <input id="special-note" name="note" value="{{ old('note') }}" maxlength="100" placeholder="例：特別講義">
                        @if ($errors->has('note'))
                            @foreach ($errors->get('note') as $message)
                                <p>{{ $message }}</p>
                            @endforeach
                        @endif
                    </div>

                    <div>
                        <button class="btn">登録する</button>
                    </div>
                </form>
            </div>

            <div class="view-content" role="tabpanel" id="panel-change" aria-labelledby="tab-change" @if ($activeTab !== 'change') hidden @endif>
                <h2>クラスの授業変更</h2>
                <form method="POST" action="{{ route('admin.schedules.lesson-changes.store') }}" id="change-form"
                      data-lessons-url="{{ route('admin.schedules.lessons') }}"
                      data-old-lesson="{{ old('lesson') }}" data-old-to-lesson="{{ old('to_lesson') }}">
                    @csrf
                    <input type="hidden" name="_tab" value="change">

                    <div>
                        <label for="change-class">クラス</label>
                        <select id="change-class" name="school_class_id" required>
                            <option value="">選択してください</option>
                            @foreach ($classes as $class)
                                <option value="{{ $class->id }}" @selected(old('school_class_id') == $class->id)>{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="change-type">変更の種類</label>
                        <select id="change-type" name="type" required>
                            @foreach (\App\Models\ClassLessonChange::TYPES as $value => $label)
                                <option value="{{ $value }}" @selected(old('type', 'change') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="change-date">日付</label>
                        <input id="change-date" type="date" name="date" value="{{ old('date', $date) }}" required>
                    </div>

                    <div data-show-for="change cancel move swap">
                        <label for="change-lesson">授業</label>
                        <select id="change-lesson" name="lesson" data-lessons-for="change-date">
                            <option value="">クラスと日付を選んでください</option>
                        </select>
                    </div>

                    <div data-show-for="change add">
                        <label for="change-to-subject">代わりにする授業</label>
                        <select id="change-to-subject" name="to_subject_id">
                            <option value="">選択してください</option>
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject->id }}" @selected(old('to_subject_id') == $subject->id)>{{ $subject->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div data-show-for="move swap">
                        <label for="change-to-date">移動先・入れ替える相手の日付</label>
                        <input id="change-to-date" type="date" name="to_date" value="{{ old('to_date') }}">
                    </div>

                    <div data-show-for="swap">
                        <label for="change-to-lesson">入れ替える相手の授業</label>
                        <select id="change-to-lesson" name="to_lesson" data-lessons-for="change-to-date">
                            <option value="">相手の日付を選んでください</option>
                        </select>
                    </div>

                    <div data-periods-for="change-lesson" hidden
                            data-old-start="{{ old('lesson_period_start') }}" data-old-end="{{ old('lesson_period_end') }}">
                        <span>時限</span>
                        <select name="lesson_period_start" aria-label="変更する時限（開始）"></select>
                        〜
                        <select name="lesson_period_end" aria-label="変更する時限（終了）"></select>
                    </div>
                    <p id="swap-hint" class="muted" aria-live="polite"></p>

                    <div>
                        <label for="change-note">メモ（任意）</label>
                        <input id="change-note" name="note" value="{{ old('note') }}" maxlength="100" placeholder="例：先生の出張のため">
                    </div>

                    @if (old('_tab') === 'change' && $errors->any())
                        @foreach ($errors->all() as $message)
                            <p>{{ $message }}</p>
                        @endforeach
                    @endif

                    <div>
                        <button class="btn">登録する</button>
                    </div>
                </form>
            </div>
        </div>

        <p><a href="{{ route('admin.schedules.index') }}">スケジュールへ戻る</a></p>
    </div>
</main>
@endsection

@push('scripts')
    <script type="module">
        document.querySelectorAll('[role="tablist"]').forEach((tablist) => {
            tablist.addEventListener('click', (event) => {
                const clicked = event.target.closest('[role="tab"]');
                if (!clicked) return;

                tablist.querySelectorAll('[role="tab"]').forEach((tab) => {
                    const selected = tab === clicked;
                    tab.setAttribute('aria-selected', selected);
                    document.getElementById(tab.getAttribute('aria-controls')).hidden = !selected;
                });
            });
        });

        // クラスの授業変更：種類に合わせて入力欄を出し分け、クラスと日付からその日の授業を読み込む
        const changeForm = document.getElementById('change-form');
        if (changeForm) {
            const typeSelect = document.getElementById('change-type');
            const classSelect = document.getElementById('change-class');

            const lessonSelect = document.getElementById('change-lesson');
            const toLessonSelect = document.getElementById('change-to-lesson');
            const toPeriodStart = document.getElementById('change-to-period-start');
            const toPeriodEnd = document.getElementById('change-to-period-end');
            const swapHint = document.getElementById('swap-hint');

            // 「時限」の欄（授業の select の下にある data-periods-for の div）
            const periodBox = (select) => changeForm.querySelector(`[data-periods-for="${select.id}"]`);

            // 選んでいる授業の時限（例：「2:3:5」→ [3, 5]）。選んでいなければ null
            const lessonRange = (select) => {
                const [, from, to] = (select.value || '').split(':').map(Number);
                return from ? [from, to] : null;
            };

            // いま選んでいる時限（「時限」の欄が出ていればその値、出ていなければ授業の全部）
            const selectedRange = (select) => {
                const box = periodBox(select);
                if (!box.hidden) {
                    const [start, end] = box.querySelectorAll('select');
                    return [Number(start.value), Number(end.value)];
                }
                return lessonRange(select);
            };
            const count = (range) => (range ? range[1] - range[0] + 1 : 0);

            // 選んだ授業が2時限以上続くときだけ「時限」の欄を出し、その授業の時限の中から選べるようにする
            // start・end … 選んでおく時限（省略すると授業の全部）
            const updatePeriods = (select, start = null, end = null) => {
                const box = periodBox(select);
                const [startSelect, endSelect] = box.querySelectorAll('select');
                const range = select.disabled ? null : lessonRange(select);
                const multi = range !== null && range[1] > range[0];
                box.hidden = !multi;
                startSelect.disabled = endSelect.disabled = !multi; // 出ていないときは送らない
                if (!multi) return;

                const [from, to] = range;
                const fit = (value, fallback) => Math.min(Math.max(Number(value) || fallback, from), to);
                const fill = (sel, value) => {
                    sel.innerHTML = '';
                    for (let p = from; p <= to; p++) sel.add(new Option(`${p}限`, p, false, p === value));
                };
                fill(startSelect, fit(start, from));
                fill(endSelect, fit(end, to));
            };

            // 入れ替えのとき：相手の時限の数を、元の授業の時限の数にそろえる
            // （1時限の授業と3〜5限の授業なら、相手は「3限」だけにしておく。どの時限かは選び直せる）
            const matchSwap = () => {
                const want = count(selectedRange(lessonSelect));
                const range = lessonRange(toLessonSelect);
                if (typeSelect.value !== 'swap' || !want || !range || count(range) < want) return;
                const current = selectedRange(toLessonSelect)[0];
                const start = Math.min(current, range[1] - want + 1);
                updatePeriods(toLessonSelect, start, start + want - 1);
            };

            // 移動のとき：移動先の終了時限を、元の授業と同じ時限の数になるようにする
            const matchMove = () => {
                const want = count(selectedRange(lessonSelect));
                if (typeSelect.value !== 'move' || !want) return;
                toPeriodEnd.value = String(Math.min(Number(toPeriodStart.value) + want - 1, toPeriodEnd.options.length));
            };

            // 入れ替えの時限の数を表示する（そろっていなければ注意を出す）
            const showHint = () => {
                const a = count(selectedRange(lessonSelect));
                const b = count(selectedRange(toLessonSelect));
                swapHint.textContent = a && b
                    ? `元の授業：${a}時限 ⇄ 相手：${b}時限${a === b ? '' : '（時限の数をそろえてください）'}`
                    : '';
            };

            // 種類に関係する欄だけ見せる（data-show-for="change add" など）。見せない欄は送らない
            const toggleFields = () => {
                changeForm.querySelectorAll('[data-show-for]').forEach((field) => {
                    const show = field.dataset.showFor.split(' ').includes(typeSelect.value);
                    field.hidden = !show;
                    field.querySelectorAll('input, select').forEach((input) => { input.disabled = !show; });
                });
                // 「時限」の欄は、選んだ授業によって出す・出さないが決まるので、改めて決め直す
                [lessonSelect, toLessonSelect].forEach((select) => {
                    const [start, end] = selectedRange(select) ?? [null, null];
                    updatePeriods(select, start, end);
                });
            };

            // 授業の一覧を読み込む（select の data-lessons-for に、日付の入力欄の id が入っている）
            // selected … 読み込んだあとに選んでおく授業（省略すると、いま選んでいる授業のまま）
            // periods  … 読み込んだあとに選んでおく時限 [開始, 終了]（省略すると授業の全部）
            const loadLessons = async (select, selected = select.value, periods = [null, null]) => {
                // 読み込みの番号。続けて読み込んだときは、古い結果で上書きしないようにする
                const token = String(Number(select.dataset.token ?? 0) + 1);
                select.dataset.token = token;

                const date = document.getElementById(select.dataset.lessonsFor).value;
                if (!classSelect.value || !date) {
                    select.innerHTML = '<option value="">クラスと日付を選んでください</option>';
                    updatePeriods(select);
                    showHint();
                    return;
                }
                const url = new URL(changeForm.dataset.lessonsUrl);
                url.searchParams.set('school_class_id', classSelect.value);
                url.searchParams.set('date', date);
                const res = await fetch(url, { headers: { Accept: 'application/json' } });
                const lessons = res.ok ? await res.json() : [];
                if (select.dataset.token !== token) return; // もっと新しい読み込みが始まっている
                select.innerHTML = lessons.length
                    ? '<option value="">選択してください</option>'
                    : '<option value="">この日、このクラスの授業はありません</option>';
                lessons.forEach((lesson) => select.add(new Option(lesson.label, lesson.value, false, lesson.value === selected)));
                updatePeriods(select, ...periods);
                showHint();
            };

            typeSelect.addEventListener('change', () => { toggleFields(); matchSwap(); matchMove(); showHint(); });
            classSelect.addEventListener('change', () => { loadLessons(lessonSelect); loadLessons(toLessonSelect); });
            document.getElementById('change-date').addEventListener('change', () => loadLessons(lessonSelect));
            document.getElementById('change-to-date').addEventListener('change', () => loadLessons(toLessonSelect));

            // 授業を選び直したら、時限はその授業の全部にしてから、入れ替え・移動の時限の数をそろえる
            lessonSelect.addEventListener('change', () => { updatePeriods(lessonSelect); matchSwap(); matchMove(); showHint(); });
            toLessonSelect.addEventListener('change', () => { updatePeriods(toLessonSelect); matchSwap(); showHint(); });

            // 「時限」を変えたとき（開始が終了より後になったら、終了を開始に合わせる）
            [lessonSelect, toLessonSelect].forEach((select) => {
                const [startSelect, endSelect] = periodBox(select).querySelectorAll('select');
                startSelect.addEventListener('change', () => {
                    if (Number(endSelect.value) < Number(startSelect.value)) endSelect.value = startSelect.value;
                    if (select === lessonSelect) { matchSwap(); matchMove(); }
                    showHint();
                });
                endSelect.addEventListener('change', () => {
                    if (Number(endSelect.value) < Number(startSelect.value)) startSelect.value = endSelect.value;
                    if (select === lessonSelect) { matchSwap(); matchMove(); }
                    showHint();
                });
            });
            toPeriodStart.addEventListener('change', matchMove);

            // 入力エラーで戻ってきたときは、さっき選んだ授業と時限を選び直しておく
            const oldPeriods = (select) => [periodBox(select).dataset.oldStart || null, periodBox(select).dataset.oldEnd || null];
            toggleFields();
            loadLessons(lessonSelect, changeForm.dataset.oldLesson, oldPeriods(lessonSelect));
            loadLessons(toLessonSelect, changeForm.dataset.oldToLesson, oldPeriods(toLessonSelect));
        }
    </script>
@endpush
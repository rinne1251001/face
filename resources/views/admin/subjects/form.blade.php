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
    </script>
    <svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" style="display: none;">
        <symbol id="check" viewBox="0 0 32 26">
            <path d="m4.5 14.4 7 7 16-17" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" fill="none"/>
        </symbol>
    </svg>
@endpush
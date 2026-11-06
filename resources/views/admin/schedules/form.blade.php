@extends('layouts.test')
@section('title', '休校・振替・変則授業の登録')
@push('styles')
    <style>
        .tab-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
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
    $activeTab = old('_tab') === 'special' ? 'special' : 'day';
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
                        <select id="period_start" name="period_start" required aria-label="開始時限">
                            @for ($p = 1; $p <= \App\Models\Subject::maxPeriod(); $p++)
                                <option value="{{ $p }}" @selected(old('period_start', 1) == $p)>{{ $p }}限</option>
                            @endfor
                        </select>
                        〜
                        <select name="period_end" required aria-label="終了時限">
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
        </div>

        <p><a href="{{ route('admin.schedules.index') }}">スケジュールへ戻る</a></p>
    </div>
</main>
@endsection

@push('scripts')
    <script type="module">
        document.querySelectorAll('[role="tablist"]').forEach((tablist) => {
            // リスナーはタブごとではなく親に1つだけ（イベント委譲）
            tablist.addEventListener('click', (event) => {
                const clicked = event.target.closest('[role="tab"]');
                if (!clicked) return;

                tablist.querySelectorAll('[role="tab"]').forEach((tab) => {
                    const selected = tab === clicked;
                    tab.setAttribute('aria-selected', selected);
                    // aria-controls で対応するパネルを引くので、並び順（index）に依存しない
                    document.getElementById(tab.getAttribute('aria-controls')).hidden = !selected;
                });
            });
        });
    </script>
@endpush
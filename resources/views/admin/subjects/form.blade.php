@extends('layouts.test')
@section('title', $subject->exists ? '授業の編集' : '授業の追加')
@push('styles')
    <style>
        :root {
            --color-font-light1: #51626F;
            --color-font-light2: #829AAB;
            --color-border: #ececf1;
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
            border: var(--color-accent) 1.5px solid;
            outline: var(--color-accent) 0.5px solid;
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
            color: var(--color-accent);
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

            <div class="inp-container">
                <div class="inp-hov">
                    <label id="day_of_week">曜日</label>
                    <select name="day_of_week" required>
                        <option value="">選択してください</option>
                        @foreach (\App\Models\Subject::DAYS as $number => $label)
                            <option value="{{ $number }}" @selected(old('day_of_week', $subject->day_of_week) == $number)>{{ $label }}曜日</option>
                        @endforeach
                    </select>
                </div>
                @if ($errors->has('day_of_week'))
                    @foreach ($errors->get('day_of_week') as $message)
                        <p>{{ $message }}</p>
                    @endforeach
                @endif
            </div>

            <div class="inp-container">
                <div class="inp-hov">
                    <label id="day_of_week">時限</label>
                    <div>
                        <select name="period_start" required>
                            @for ($p = 1; $p <= \App\Models\Subject::maxPeriod(); $p++)
                                <option value="{{ $p }}" @selected(old('period_start', $subject->period_start) == $p)>{{ $p }}限</option>
                            @endfor
                        </select>
                        〜
                        <select name="period_end" required>
                            @for ($p = 1; $p <= \App\Models\Subject::maxPeriod(); $p++)
                                <option value="{{ $p }}" @selected(old('period_end', $subject->period_end) == $p)>{{ $p }}限</option>
                            @endfor
                        </select>
                    </div>
                    <p style="color: var(--color-font-light1); padding: 3px 0;">（1限だけの授業は「1限〜1限」）</p>
                </div>
                @if ($errors->has('period_start') || $errors->has('period_end'))
                    @foreach (collect($errors->get('period_start'))->merge($errors->get('period_end')) as $message)
                        <p>{{ $message }}</p>
                    @endforeach
                @endif
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

            <button>保存</button>
        </form>
        <p><a href="{{ route('admin.subjects.index') }}">授業一覧へ戻る</a></p>
    </div>
</main>
@endsection

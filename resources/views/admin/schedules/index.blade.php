@extends('layouts.test')
@section('title', 'スケジュール確認')
@push('styles')
    <style>
        .calendar-container {
            width: clamp(100px, 100%, 600px);;
        }

        .week-container {
            display: flex;
            justify-content: space-around;
            align-items: center;
            padding-block: 10px;
        }

        .week-container > a {
            color: var(--color-main);
        }

        .week-container > a:hover {
            color: var(--color-accent);
        }

        .week-container > div {
            font-size: 1.4em;
            font-weight: bold;
            color: var(--color-main);
        }

        .calendar {
            display: inline-grid;
            grid-template-columns: auto repeat(5, 1fr);
            border-top: 1px solid var(--color-main);
            border-left: 1px solid var(--color-main);
            width: 100%;
            min-width: 0;
        }

        .calendar thead,
        .calendar tbody,
        .calendar tr {
            display: contents;
        }

        .calendar th,
        .calendar td {
            padding: 10px;
            border-right: 1px solid var(--color-main);
            border-bottom: 1px solid var(--color-main);
            text-align: center;
        }

        .calendar th {
            color: var(--color-bg);
            background-color: var(--color-main);
        }

        .calendar th:first-child {
            width: 3em;
        }

        .calendar td {
            font-size: 0.9em;
        }

        .calendar td div + div {
            margin-top: 5px;
        }

        /* 祝日・休校など、毎週の授業がない日 */
        .calendar .off {
            background-color: var(--color-sub);
        }

        .calendar th small {
            display: block;
            font-size: 0.75em;
            font-weight: normal;
        }

        /* 変則授業 */
        .calendar .special {
            color: var(--color-accent);
            font-weight: bold;
        }

        .schedule-list {
            width: clamp(100px, 100%, 600px);
            margin-top: 20px;
        }

        .schedule-list li {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 6px 0;
            border-bottom: 1px solid var(--color-sub);
        }
    </style>
@endpush

@section('content')

    <main class="main">
        <div class="content-wrapper">

            <div class="page-header">
                <h1>スケジュール</h1>
                <div>
                    <a class="btn" href="{{ route('admin.schedules.create') }}">＋ 休校・振替・変則授業を登録</a>
                </div>
            </div>

            <div class="calendar-container">
                <div class="week-container">
                    <a href="{{ route('admin.schedules.index', ['date' => $monday->copy()->subWeek()->toDateString()]) }}">◀</a>
                    <div>{{ $isThisWeek ? '今週' : $monday->format('n月j日').'の週' }}</div>
                    <a href="{{ route('admin.schedules.index', ['date' => $monday->copy()->addWeek()->toDateString()]) }}">▶</a>
                </div>
                <table class="calendar">
                    <thead>
                        <tr>
                            <th></th>
                            @foreach ($days as $day)
                                <th>
                                    {{ $day['label'] }} ({{ $day['date']->format('n/j') }})
                                    {{-- 祝日・休校・振替の日は、その説明を出す --}}
                                    @if ($day['note'])
                                        <small>{{ $day['note'] }}</small>
                                    @endif
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @for ($period = 1; $period <= $maxPeriod; $period++)
                            <tr>
                                <th>{{ $period }}限</th>
                                @foreach ($days as $day)
                                    <td @class(['off' => $day['off']])>
                                        @foreach ($grid[$period][$day['date']->toDateString()] ?? [] as $item)
                                            <div @class(['special' => $item['special']])>
                                                {{ $item['name'] }}@if ($item['special'])（変則）@endif
                                            </div>
                                        @endforeach
                                    </td>
                                @endforeach
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>

            <div class="schedule-list">
                <h2>休校・振替（今日以降）</h2>
                <ul>
                    @forelse ($calendarDays as $calendarDay)
                        <li>
                            <span>
                                {{ $calendarDay->date->format('n月j日') }}（{{ \App\Models\Subject::DAYS[$calendarDay->date->dayOfWeekIso] }}）
                                <strong>{{ $calendarDay->label }}</strong>
                                @if ($calendarDay->note)<span>{{ $calendarDay->note }}</span>@endif
                            </span>
                            <form method="POST" action="{{ route('admin.schedules.days.destroy', $calendarDay) }}" onsubmit="return confirm('この設定を取り消しますか？')">
                                @csrf
                                @method('DELETE')
                                <button>取り消す</button>
                            </form>
                        </li>
                    @empty
                        <li>登録はありません。</li>
                    @endforelse
                </ul>

                <h2>変則授業（今日以降）</h2>
                <ul>
                    @forelse ($specialClasses as $special)
                        <li>
                            <span>
                                {{ $special->date->format('n月j日') }}（{{ \App\Models\Subject::DAYS[$special->date->dayOfWeekIso] }}）
                                {{ $special->period_start === $special->period_end ? "{$special->period_start}限" : "{$special->period_start}〜{$special->period_end}限" }}
                                <strong>{{ $special->subject->name }}</strong>
                                @if ($special->note)<span class="muted">{{ $special->note }}</span>@endif
                            </span>
                            <form method="POST" action="{{ route('admin.schedules.special-classes.destroy', $special) }}" onsubmit="return confirm('この変則授業を取り消しますか？')">
                                @csrf
                                @method('DELETE')
                                <button>取り消す</button>
                            </form>
                        </li>
                    @empty
                        <li class="muted">登録はありません。</li>
                    @endforelse
                </ul>
            </div>

        </div>
    </main>

@endsection
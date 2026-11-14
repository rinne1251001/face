@extends('layouts.test')
@section('title', $subject->name . 'の分析詳細')
@push('styles')
    <style>
        .table-container {
            overflow-x: auto;
            max-width: 100%;
        }
        .table-container table {
            border-collapse: collapse;
        }
        .table-container th {
            background-color: var(--color-main);
            color: var(--color-bg);
            border: 1px solid var(--color-bg);
            padding-block: 7px;
            padding-inline: 20px;
        }
        .table-container td {
            color: var(--color-main);
            border: 1px solid var(--color-main);
            padding: 10px 20px;
        }
    </style>
@endpush
@section('content')

    <main class="main">
        <div class="content-wrapper">

            <div class="page-header">
                <h1>{{ $subject->name }}の分析詳細</h1>
            </div>

            <div>
                <h2>時限ごとの出席率</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>時限</th>
                                @foreach ($months as $label)
                                    <th>{{ $label }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($periodRows as $period => $row)
                                <tr>
                                    <td>{{ $period }}限</td>
                                    @foreach (array_keys($months) as $month)
                                        @include('teacher.analyses.partials.rate-cell', ['cell' => $row['months'][$month], 'highlight' => false])
                                    @endforeach
                                </tr>
                            @empty
                                <tr><td colspan="{{ count($months) + 1 }}">まだ出席の記録がありません</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div>
                <h2>曜日×時限ごとの出席率</h2>
                {{-- 年度の初めから今日までの合計。その曜日のその時限に授業がなければ「—」 --}}
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>時限</th>
                                @foreach ($weekdays as $label)
                                    <th>{{ $label }}</th>
                                @endforeach
                                <th>全体</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($grid as $period => $row)
                                <tr>
                                    <td>{{ $period }}限</td>
                                    @foreach ($row['days'] as $rate)
                                        <td>{{ $rate === null ? '—' : $rate.'%' }}</td>
                                    @endforeach
                                    <td>{{ $row['overall'] === null ? '—' : $row['overall'].'%' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="{{ count($weekdays) + 2 }}">まだ出席の記録がありません</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div>
                <h2>生徒の出席率一覧</h2>
                {{-- 出席率が7割未満のセルと、先月より10ポイント以上下がった月のセルは、背景に色が付く（partials.rate-cell） --}}
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>名前</th>
                                <th>全体</th>
                                @foreach ($months as $label)
                                    <th>{{ $label }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $student)
                                <tr>
                                    <td><a href="{{ route('teacher.analyses.student', $student) }}">{{ $student->name }}</a></td>
                                    @include('teacher.analyses.partials.rate-cell', ['cell' => $studentRows[$student->id]['overall']])
                                    @foreach (array_keys($months) as $month)
                                        @include('teacher.analyses.partials.rate-cell', ['cell' => $studentRows[$student->id]['months'][$month]])
                                    @endforeach
                                </tr>
                            @empty
                                <tr><td colspan="{{ count($months) + 2 }}">いません</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @include('teacher.analyses.partials.note')

        </div>
    </main>

@endsection
@extends('layouts.test')
@section('title', $subject->name . 'の分析詳細')
@push('styles')
    <style>
        .list-table {
            overflow-x: auto;
            max-width: 100%;
        }
        .list-table table {
            border-collapse: separate;
            border-spacing: 0;
        }
        .list-table th {
            background-color: var(--color-main);
            color: var(--color-bg);
            border: 1px solid var(--color-bg);
            padding: 7px 20px;
        }
        .list-table td {
            color: var(--color-main);
            border: 1px solid var(--color-main);
            padding: 10px 20px;
        }

        .list-table th,
        .list-table td {
            white-space: nowrap;
            border-width: 0 1px 1px 0;
        }
        /* 先頭の列（名前・時限・授業名）は左に固定して、横にスクロールして前の月を見ても見えるようにする
            折り返してよいが、1〜2文字ずつにならないように、幅は 6em 以上 */
        .list-table tr > :first-child {
            position: sticky;
            left: 0;
            z-index: 1;
            white-space: normal;
            min-width: 6em;
            border-left-width: 1px;
        }
        .list-table td:first-child {
            background-color: var(--color-bg); /* 下を流れる列が透けないように */
        }
        .list-table thead th {
            border-top-width: 1px;
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
                <div class="list-table">
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
                <div class="list-table">
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
                <div class="list-table">
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
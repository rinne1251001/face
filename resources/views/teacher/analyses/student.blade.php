@extends('layouts.app')
@section('title', $student->name . 'の分析詳細')
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

        .profile-container {
            overflow-x: auto;
            max-width: 100%;
        }
        .profile-container th {
            text-align: right;
        }
        .profile-container td {
            padding: 2px 20px;
        }
    </style>
@endpush
@section('content')

    <main class="main">
        <div class="content-wrapper">

            <div class="page-header">
                <h1>{{ $student->name }}の分析詳細</h1>
            </div>

            <div class="profile-container">
                <table>
                    {{-- 出席番号・クラス・担任は、今年度の在籍の値 --}}
                    <tr><th>名前</th><td>{{ $student->name }}（{{ $student->furigana }}）</td></tr>
                    <tr><th>学籍番号</th><td>{{ $student->student_number }}</td></tr>
                    <tr><th>出席番号</th><td>{{ $enrollment?->attend_number ?? '未定' }}</td></tr>
                    <tr><th>クラス</th><td>{{ $enrollment?->schoolClass?->name ?? '未定' }}</td></tr>
                    <tr><th>担任</th><td>{{ $enrollment?->schoolClass?->teachers->pluck('name')->join('・') ?: '未定' }}</td></tr>
                    <tr><th>出席率</th><td>{{ $overall === null ? '—' : $overall.'%' }}</td></tr>
                </table>
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
                <h2>授業の出席率一覧</h2>
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
                            @forelse($subjects as $subject)
                                <tr>
                                    <td>{{ $subject->name }}</td>
                                    @include('teacher.analyses.partials.rate-cell', ['cell' => $subjectRows[$subject->id]['overall']])
                                    @foreach (array_keys($months) as $month)
                                        @include('teacher.analyses.partials.rate-cell', ['cell' => $subjectRows[$subject->id]['months'][$month]])
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
@extends('layouts.test')
@section('title', $student->name . 'の分析詳細')
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
                <h2>授業の出席率一覧</h2>
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
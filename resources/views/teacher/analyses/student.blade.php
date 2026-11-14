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
                    <tr><th>名前</th><td>{{ $student->name }}（{{ $student->furigana }}）</td></tr>
                    <tr><th>学籍番号</th><td>{{ $student->student_number }}</td></tr>
                    <tr><th>出席番号</th><td>{{ $student->attend_number }}</td></tr>
                    <tr><th>クラス</th><td></td></tr>
                    <tr><th>担任</th><td></td></tr>
                    <tr><th>出席率</th><td>%</td></tr>
                </table>
            </div>

            <div>
                <h2>時限ごとの出席率</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr><th>時限</th><th>10月</th><th>9月</th><th>8月</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1限</td>
                                <td>%</td>
                                <td>%</td>
                                <td>%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div>
                <h2>授業の出席率一覧</h2>
                {{-- 出席率が7割未満の授業＆先月より10%以上下がった授業の月のtdはbackground-color: var(--color-sub) --}}
                <div class="table-container">
                    <table>
                        <thead>
                            <tr><th>名前</th><th>全体</th><th>10月</th><th>9月</th><th>8月</th></tr>
                        </thead>
                        <tbody>
                            @forelse($subjects as $subject)
                                <tr>
                                    <td>{{ $subject->name }}</td>
                                    <td>%</td>
                                    <td>%</td>
                                    <td>%</td>
                                    <td>%</td>
                                </tr>
                            @empty
                                <tr colspan="5"><td>いません</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

@endsection
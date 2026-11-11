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
                <h2>曜日×時限ごとの出席率</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr><th>時限</th><th>月</th><th>火</th><th>水</th><th>木</th><th>金</th><th>全体</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1限</td>
                                <td>%</td>
                                <td>%</td>
                                <td>%</td>
                                <td>%</td>
                                <td>%</td>
                                <td>%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div>
                <h2>生徒の出席率一覧</h2>
                {{-- 出席率が7割未満の生徒＆先月より10%以上下がった生徒の月のtdはbackground-color: var(--color-sub) --}}
                <div class="table-container">
                    <table>
                        <thead>
                            <tr><th>名前</th><th>全体</th><th>10月</th><th>9月</th><th>8月</th></tr>
                        </thead>
                        <tbody>
                            @forelse($students as $student)
                                <tr>
                                    <td>{{ $student->name }}</td>
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
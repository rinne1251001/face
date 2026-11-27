@extends('layouts.app')
@section('title', '年度')
@push('styles')
    <style>
        .table-container {
            overflow-x: auto;
            max-width: 100%;
        }
        .table-container table {
            border-collapse: collapse;
            width: 100%;
        }
        .table-container th {
            background-color: var(--color-main);
            color: var(--color-bg);
            border: 1px solid var(--color-bg);
            padding-block: 7px;
            padding-inline: 20px;
        }
        .table-container td {
            border: 1px solid var(--color-main);
            padding: 10px 20px;
        }
        .small {
            color: var(--color-font-light);
            font-size: 0.8em;
        }
    </style>
@endpush
@section('content')

    <main class="main">
        <div class="content-wrapper">
            <div class="page-header">
                <h1>年度</h1>
                <div>
                    <a class="btn" href="{{ route('admin.years.create') }}">年度更新</a>
                </div>
            </div>

            @if ($selected)
                {{-- 年度を選ぶと、その年度の内容に切り替わる（URL は /admin/years?year=2026） --}}
                <form method="GET" action="{{ route('admin.years.index') }}">
                    <label for="year">表示</label>
                    <select id="year" name="year" onchange="this.form.submit()">
                        @foreach ($years as $year)
                            <option value="{{ $year->year }}" @selected($year->is($selected))>{{ $year->label }}</option>
                        @endforeach
                    </select>
                    {{-- JavaScript が使えないときだけ出るボタン --}}
                    <noscript><button>表示</button></noscript>
                </form>

                <div class="table-container">
                    <table>
                        <tr>
                            <th>期間</th>
                            <td>{{ $selected->start_date->format('Y/m/d') }}～{{ $selected->end_date->format('Y/m/d') }}</td>
                        </tr>
                        <tr>
                            <th>在籍生徒数</th>
                            <td>{{ $selected->enrollments_count }}人</td>
                        </tr>
                        <tr>
                            <th>クラス数</th>
                            <td>{{ $selected->school_classes_count }}件</td>
                        </tr>
                        <tr>
                            <th>授業数</th>
                            <td>{{ $selected->subjects_count }}件</td>
                        </tr>
                        <tr>
                            <th>編集</th>
                            <td>
                                @if (! $selected->isPast())
                                    編集可
                                @elseif ($selected->isEditable())
                                    <div>
                                        <div>編集を許可中<span class="small">（{{ $selected->unlocked_at->format('Y/m/d H:i') }}から）</span></div>
                                        <form method="POST" action="{{ route('admin.years.lock', $selected) }}" style="display: inline">
                                            @csrf
                                            <button>読み取り専用に戻す</button>
                                        </form>
                                    </div>
                                @else
                                    <div>
                                        <div>読み取り専用</div>
                                        <form method="POST" action="{{ route('admin.years.unlock', $selected) }}" style="display: inline"
                                            onsubmit="return confirm('{{ $selected->label }}の生徒・クラス・授業を変更できるようにします。よろしいですか？')">
                                            @csrf
                                            <button>編集許可</button>
                                        </form>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            @else
                <p>年度がまだ登録されていません。「年度更新」から最初の年度を作成してください。</p>
            @endif

        </div>
    </main>

@endsection
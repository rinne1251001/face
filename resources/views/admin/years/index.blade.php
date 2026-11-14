@extends('layouts.test')
@section('title', '年度')

@section('content')

    <main class="main">
        <div class="content-wrapper">
            <div class="page-header">
                <h1>年度</h1>
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

                <dl>
                    <dt>期間</dt>
                    <dd>{{ $selected->start_date->format('Y/m/d') }}～{{ $selected->end_date->format('Y/m/d') }}</dd>
                    <dt>在籍している生徒</dt>
                    <dd>
                        {{ $selected->enrollments_count }}人
                        <a href="{{ route('admin.students.index', ['year' => $selected->year]) }}">生徒一覧を見る</a>
                    </dd>
                    <dt>クラス</dt>
                    <dd>{{ $selected->school_classes_count }}件</dd>
                    <dt>授業</dt>
                    <dd>{{ $selected->subjects_count }}件</dd>
                    <dt>編集</dt>
                    <dd>
                        @if (! $selected->isPast())
                            編集できます
                        @elseif ($selected->isEditable())
                            {{-- 過去の年度で、編集を許可しているとき --}}
                            編集を許可しています（{{ $selected->unlocked_at->format('Y/m/d H:i') }}から）
                            <form method="POST" action="{{ route('admin.years.lock', $selected) }}" style="display: inline">
                                @csrf
                                <button>読み取り専用に戻す</button>
                            </form>
                        @else
                            {{-- 過去の年度は読み取り専用。押すときに確認を出す --}}
                            読み取り専用（過去の年度）
                            <form method="POST" action="{{ route('admin.years.unlock', $selected) }}" style="display: inline"
                                onsubmit="return confirm('{{ $selected->label }}の生徒・クラス・授業を変更できるようにします。よろしいですか？')">
                                @csrf
                                <button>編集を許可する</button>
                            </form>
                        @endif
                    </dd>
                </dl>
            @else
                <p>年度がまだ登録されていません。「年度更新」から最初の年度を作成してください。</p>
            @endif

            <div>
                <a href="{{ route('admin.years.create') }}">年度更新</a>
            </div>

        </div>
    </main>

@endsection
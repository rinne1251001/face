@extends('layouts.app')
@section('title', '教師ホーム')

@section('content')
<main class="main">
    <div class="content-wrapper">
        <div class="page-header">
            <h1>{{ $today->format('Y年n月j日') }}（{{ $todayLabel }}）</h1>
        </div>

        <section class="teacher-card">
            <h2>今日の授業</h2>
            @if ($todayRows->isEmpty())
                <p class="muted teacher-empty">今日の担当授業はありません。</p>
            @else
                <div class="teacher-table-wrapper">
                    <table class="teacher-table">
                        <thead>
                            <tr><th>時限</th><th>授業名</th><th>出席（遅刻を含む）</th><th>操作</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($todayRows as ['subject' => $subject, 'period' => $period])
                                <tr>
                                    <td>{{ $period }}限</td>
                                    <td>{{ $subject->name }}</td>
                                    <td>{{ $presentCounts["{$subject->id}-{$period}"] ?? 0 }} / {{ $subject->students_count }}人</td>
                                    <td>
                                        <div class="button-group">
                                            <a class="btn-action" href="{{ route('teacher.checkin.show', [$subject, 'period' => $period]) }}">顔認証で出席を取る</a>
                                            <a class="btn-action" href="{{ route('teacher.subjects.show', [$subject, 'period' => $period]) }}">出席一覧・手動登録</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="teacher-card">
            <h2>担当授業</h2>
            <div class="teacher-table-wrapper">
                <table class="teacher-table">
                    <thead>
                        <tr><th>曜日・時限</th><th>授業名</th><th>受講生徒数</th><th>操作</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($subjects as $subject)
                            <tr>
                                <td>{{ $subject->schedule_label }}</td>
                                <td>{{ $subject->name }}</td>
                                <td>{{ $subject->students_count }}人</td>
                                <td><a class="btn-action" href="{{ route('teacher.subjects.show', $subject) }}">出席一覧</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="4">担当授業が設定されていません。管理者に設定を依頼してください。</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="teacher-card">
            <h2>受け持ちクラス</h2>
            @if ($classes->isEmpty())
                <p class="muted teacher-empty">受け持ちクラスはありません。</p>
            @else
                <div class="teacher-table-wrapper">
                    <table class="teacher-table">
                        <thead>
                            <tr><th>クラス</th><th>在籍生徒数</th><th>操作</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($classes as $schoolClass)
                                <tr>
                                    <td>{{ $schoolClass->name }}</td>
                                    <td>{{ $schoolClass->students_count }}人</td>
                                    <td><a class="btn-action" href="{{ route('teacher.classes.show', $schoolClass) }}">出席状況</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</main>
@endsection

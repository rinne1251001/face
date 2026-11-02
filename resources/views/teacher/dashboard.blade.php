@extends('layouts.test')
@section('title', '教師ホーム')

@section('content')
<h1>{{ $today->format('Y年n月j日') }}（{{ $todayLabel }}）</h1>

<h2>今日の授業</h2>
@if ($todayRows->isEmpty())
    <p class="muted">今日の担当授業はありません。</p>
@else
    <table>
        <tr><th>時限</th><th>授業名</th><th>出席（遅刻を含む）</th><th>操作</th></tr>
        @foreach ($todayRows as ['subject' => $subject, 'period' => $period])
            <tr>
                <td>{{ $period }}限</td>
                <td>{{ $subject->name }}</td>
                <td>{{ $presentCounts["{$subject->id}-{$period}"] ?? 0 }} / {{ $subject->students_count }}人</td>
                <td>
                    <a href="{{ route('teacher.checkin.show', [$subject, 'period' => $period]) }}">顔認証で出席を取る</a> |
                    <a href="{{ route('teacher.subjects.show', [$subject, 'period' => $period]) }}">出席一覧・手動登録</a>
                </td>
            </tr>
        @endforeach
    </table>
@endif

<h2>担当授業</h2>
<table>
    <tr><th>曜日・時限</th><th>授業名</th><th>受講生徒数</th><th>操作</th></tr>
    @forelse ($subjects as $subject)
        <tr>
            <td>{{ $subject->schedule_label }}</td>
            <td>{{ $subject->name }}</td>
            <td>{{ $subject->students_count }}人</td>
            <td><a href="{{ route('teacher.subjects.show', $subject) }}">出席一覧</a></td>
        </tr>
    @empty
        <tr><td colspan="4">担当授業が設定されていません。管理者に設定を依頼してください。</td></tr>
    @endforelse
</table>

<h2>受け持ちクラス</h2>
@if ($classes->isEmpty())
    <p class="muted">受け持ちクラスはありません。</p>
@else
    <ul>
        @foreach ($classes as $schoolClass)
            <li>
                <a href="{{ route('teacher.classes.show', $schoolClass) }}">{{ $schoolClass->name }}</a>
                （{{ $schoolClass->students_count }}人）
            </li>
        @endforeach
    </ul>
@endif
@endsection

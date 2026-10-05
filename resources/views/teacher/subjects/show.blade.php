@extends('layouts.app')
@section('title', $subject->name.' の出席')

@section('content')
<h1>{{ $subject->name }}（{{ $subject->schedule_label }}）の出席</h1>

{{-- 日付の切り替え --}}
<form method="GET" action="{{ route('teacher.subjects.show', $subject) }}">
    <a href="{{ route('teacher.subjects.show', ['subject' => $subject, 'date' => $date->copy()->subWeek()->toDateString()]) }}">« 前の週</a>
    <input type="date" name="date" value="{{ $date->toDateString() }}">
    <button>表示</button>
    <a href="{{ route('teacher.subjects.show', ['subject' => $subject, 'date' => $date->copy()->addWeek()->toDateString()]) }}">次の週 »</a>
    @unless ($isToday)
        | <a href="{{ route('teacher.subjects.show', $subject) }}">今日</a>
    @endunless
</form>

<p>
    <strong>{{ $date->format('Y年n月j日') }}（{{ $dayLabel }}）</strong>
    @if ($wrongDay)
        <span class="warn">※ この授業は{{ \App\Models\Subject::DAYS[$subject->day_of_week] }}曜日です。日付を確認してください。</span>
    @endif
</p>

<p>
    出席 {{ $counts['present'] }}人 ／ 遅刻 {{ $counts['late'] }}人 ／ 欠席 {{ $counts['absent'] }}人 ／
    未記録 {{ $counts['none'] }}人（受講者 {{ $students->count() }}人）
</p>

@if ($isToday)
    <p><a href="{{ route('teacher.checkin.show', $subject) }}">▶ 顔認証で出席を取る（今日）</a></p>
@endif

@if ($isFuture)
    <p class="muted">未来の日付は表示のみです（出席は登録できません）。</p>
@endif

<table>
    <tr><th>学籍番号</th><th>名前</th><th>クラス</th><th>状態</th><th>記録方法</th><th>変更</th></tr>
    @forelse ($students as $student)
        @php($attendance = $attendances->get($student->id))
        <tr>
            <td>{{ $student->student_number }}</td>
            <td>{{ $student->name }}</td>
            <td>{{ $student->schoolClass->name }}</td>
            <td>@include('partials.status-badge', ['attendance' => $attendance])</td>
            <td>
                @if ($attendance)
                    {{ $attendance->methodLabel() }}
                    @if ($attendance->method === 'manual' && $attendance->teacher)
                        （{{ $attendance->teacher->name }}）
                    @endif
                    <span class="muted">{{ $attendance->updated_at->format('H:i') }}</span>
                @else
                    <span class="muted">－</span>
                @endif
            </td>
            <td>
                @unless ($isFuture)
                    <form method="POST" action="{{ route('teacher.attendances.update', [$subject, $student]) }}" class="inline">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="date" value="{{ $date->toDateString() }}">
                        <select name="status">
                            @foreach (\App\Models\Attendance::STATUSES as $value => $label)
                                <option value="{{ $value }}" @selected($attendance?->status === $value)>{{ $label }}</option>
                            @endforeach
                            <option value="none" @selected(! $attendance)>未記録に戻す</option>
                        </select>
                        <button>保存</button>
                    </form>
                @endunless
            </td>
        </tr>
    @empty
        <tr><td colspan="6">この授業の受講者はいません。</td></tr>
    @endforelse
</table>

@if ($errors->any())
    <ul class="error">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
@endif

<p><a href="{{ route('teacher.dashboard') }}">ホームへ戻る</a></p>
@endsection

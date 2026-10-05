@extends('layouts.app')
@section('title', $schoolClass->name.' の出席状況')

@section('content')
<h1>{{ $schoolClass->name }} の出席状況</h1>

{{-- 日付の切り替え --}}
<form method="GET" action="{{ route('teacher.classes.show', $schoolClass) }}">
    <a href="{{ route('teacher.classes.show', ['schoolClass' => $schoolClass, 'date' => $date->copy()->subDay()->toDateString()]) }}">« 前の日</a>
    <input type="date" name="date" value="{{ $date->toDateString() }}">
    <button>表示</button>
    <a href="{{ route('teacher.classes.show', ['schoolClass' => $schoolClass, 'date' => $date->copy()->addDay()->toDateString()]) }}">次の日 »</a>
    @unless ($date->isToday())
        | <a href="{{ route('teacher.classes.show', $schoolClass) }}">今日</a>
    @endunless
</form>

<p><strong>{{ $date->format('Y年n月j日') }}（{{ $dayLabel }}）</strong></p>

@if ($students->isEmpty())
    <p class="muted">このクラスには生徒がいません。</p>
@elseif ($subjects->isEmpty())
    <p class="muted">この日（{{ $dayLabel }}曜日）に、このクラスの生徒が受講する授業はありません。</p>
@else
    <table>
        <tr>
            <th>学籍番号</th>
            <th>名前</th>
            @foreach ($subjects as $subject)
                <th>
                    @if (in_array($subject->id, $mySubjectIds, true))
                        <a href="{{ route('teacher.subjects.show', ['subject' => $subject, 'date' => $date->toDateString()]) }}">{{ $subject->name }}</a>
                    @else
                        {{ $subject->name }}
                    @endif
                    <br><span class="muted">{{ $subject->schedule_label }}</span>
                </th>
            @endforeach
        </tr>
        @foreach ($students as $student)
            <tr>
                <td>{{ $student->student_number }}</td>
                <td>{{ $student->name }}</td>
                @foreach ($subjects as $subject)
                    <td>
                        @if ($student->subjects->contains('id', $subject->id))
                            @include('partials.status-badge', ['attendance' => $attendances->get($student->id.'-'.$subject->id)])
                        @else
                            <span class="muted">－</span>
                        @endif
                    </td>
                @endforeach
            </tr>
        @endforeach
    </table>
    <p class="muted">「－」はその授業を受講していない生徒です。出席の変更は、担当の教師が授業の画面から行います。</p>
@endif

<p><a href="{{ route('teacher.dashboard') }}">ホームへ戻る</a></p>
@endsection

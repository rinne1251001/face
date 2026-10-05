@extends('layouts.app')
@section('title', $subject->exists ? '授業の編集' : '授業の追加')

@section('content')
<h1>{{ $subject->exists ? '授業の編集' : '授業の追加' }}</h1>

<form method="POST" action="{{ $subject->exists ? route('admin.subjects.update', $subject) : route('admin.subjects.store') }}">
    @csrf
    @if ($subject->exists)
        @method('PUT')
    @endif

    <p><label>授業名<br><input name="name" value="{{ old('name', $subject->name) }}" placeholder="例：数学" required></label></p>

    <p><label>曜日<br>
        <select name="day_of_week" required>
            <option value="">選択してください</option>
            @foreach (\App\Models\Subject::DAYS as $number => $label)
                <option value="{{ $number }}" @selected(old('day_of_week', $subject->day_of_week) == $number)>{{ $label }}曜日</option>
            @endforeach
        </select>
    </label></p>

    <p>
        時限<br>
        <select name="period_start" required>
            @for ($p = 1; $p <= \App\Models\Subject::MAX_PERIOD; $p++)
                <option value="{{ $p }}" @selected(old('period_start', $subject->period_start) == $p)>{{ $p }}限</option>
            @endfor
        </select>
        〜
        <select name="period_end" required>
            @for ($p = 1; $p <= \App\Models\Subject::MAX_PERIOD; $p++)
                <option value="{{ $p }}" @selected(old('period_end', $subject->period_end) == $p)>{{ $p }}限</option>
            @endfor
        </select>
        <span class="muted">（1限だけの授業は「1限〜1限」）</span>
    </p>

    @if ($errors->any())
        <ul class="error">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    @endif
    <button>保存</button>
</form>
<p><a href="{{ route('admin.subjects.index') }}">授業一覧へ戻る</a></p>
@endsection

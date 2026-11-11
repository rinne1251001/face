<form method="POST" action="{{ route('tests.update', $subject) }}">
    @csrf
    @method('PUT')

    <div>
        <label for="name">授業名</label>
        <input id="name" name="name" value="{{ old('name', $subject->name) }}" required>
    </div>

    <div>
        <label for="week">曜日</label>
        <select id="week" name="day_of_week" required>
            <option value="">選択してください</option>
            @foreach (\App\Models\Subject::DAYS as $number => $label)
                <option value="{{ $number }}" @selected(old('day_of_week', $subject->day_of_week) == $number)>{{ $label }}曜日</option>
            @endforeach
        </select>
    </div>

    <div>
        <label>時限</label>
        <select name="period_start" required>
            @for ($p = 1; $p <= \App\Models\Subject::MAX_PERIOD; $p++)
                <option value="{{ $p }}" @selected(old('period_start', $subject->period_start) == $p)>{{ $p }}限</option>
            @endfor
        </select>
        ～
        <select name="period_end" required>
            @for ($p = 1; $p <= \App\Models\Subject::MAX_PERIOD; $p++)
                <option value="{{ $p }}" @selected(old('period_end', $subject->period_end) == $p)>{{ $p }}限</option>
            @endfor
        </select>
    </div>

    <div>
        <label for="start">開始日</label>
        <input type="date" id="start" name="start_date" value="{{ old('start_date', $subject->start_date?->format('Y-m-d')) }}">
    </div>

    <div>
        <label for="end">終了日</label>
        <input type="date" id="end" name="end_date" value="{{ old('end_date', $subject->end_date?->format('Y-m-d')) }}">
    </div>

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <button>保存</button>
</form>
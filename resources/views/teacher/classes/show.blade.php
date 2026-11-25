@extends('layouts.app')
@section('title', $schoolClass->name.' の出席状況')

@push('styles')
<style>
    .list-table {
        overflow-x: auto;
        max-width: 100%;
    }
    .list-table table {
        border-collapse: separate;
        border-spacing: 0;
    }
    .list-table th {
        background-color: var(--color-main);
        color: var(--color-bg);
        border: 1px solid var(--color-bg);
        padding: 7px 20px;
    }
    .list-table td {
        color: var(--color-main);
        border: 1px solid var(--color-main);
        padding: 10px 20px;
    }

    .list-table th,
    .list-table td {
        white-space: nowrap;
        border-width: 0 1px 1px 0;
    }
    /* 先頭の列（名前・時限・授業名）は左に固定して、横にスクロールして前の月を見ても見えるようにする
        折り返してよいが、1〜2文字ずつにならないように、幅は 6em 以上 */
    .list-table tr > :first-child {
        position: sticky;
        left: 0;
        z-index: 1;
        white-space: normal;
        min-width: 6em;
        border-left-width: 1px;
    }
    .list-table td:first-child {
        background-color: var(--color-bg); /* 下を流れる列が透けないように */
    }
    .list-table thead th {
        border-top-width: 1px;
    }

    .list-table select {
        border: none;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none; 
    }

    /* 変更したけれど、まだ反映していないセル */
    .list-table td.changed {
        background-color: var(--color-sub);
    }
    .list-table td.changed select {
        font-weight: bold;
    }

    /* 何も変えていないあいだは、反映ボタンを押せないことが分かるように薄くする */
    #class-attendance-apply:disabled {
        opacity: 0.4;
        cursor: default;
        background: var(--color-main);
    }
</style>
@endpush

@section('content')
    <main class="main">
        <div class="content-wrapper">
            <div class="page-header">
                <h1>{{ $schoolClass->name }} の出席状況</h1>
            </div>

            {{-- 日付の切り替え --}}
            <form class="button-group" method="GET" action="{{ route('teacher.classes.show', $schoolClass) }}">
                <a class="btn-action" href="{{ route('teacher.classes.show', ['schoolClass' => $schoolClass, 'date' => $date->copy()->subDay()->toDateString()]) }}">« 前の日</a>
                <input type="date" name="date" value="{{ $date->toDateString() }}">
                <button class="btn">表示</button>
                <a class="btn-action" href="{{ route('teacher.classes.show', ['schoolClass' => $schoolClass, 'date' => $date->copy()->addDay()->toDateString()]) }}">次の日 »</a>
                @unless ($date->isToday())
                    <a class="btn-action" href="{{ route('teacher.classes.show', $schoolClass) }}">今日</a>
                @endunless
            </form>

            <p>
                <strong>{{ $date->format('Y年n月j日') }}（{{ $dayLabel }}）</strong>
                @if ($dayNote)<span class="warn">{{ $dayNote }}</span>@endif
            </p>

            @if ($students->isEmpty())
                <p class="muted">このクラスには生徒がいません。</p>
            @elseif ($subjects->isEmpty())
                <p class="muted">この日（{{ $dayLabel }}曜日）に、このクラスの生徒が受講する授業はありません。</p>
            @else
                {{-- 変更したセルだけを送る（変えていないセルは送らないので、ほかの先生や受付の記録を上書きしない。下の JavaScript） --}}
                <form method="POST" action="{{ route('teacher.classes.attendance.update', $schoolClass) }}" id="class-attendance">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="date" value="{{ $date->toDateString() }}">
                    <div class="list-table">
                        <table>
                            <tr>
                                <th>学籍番号</th>
                                <th>名前</th>
                                {{-- 列は「授業 × 時限」（3〜5限の授業なら3列） --}}
                                @foreach ($columns as ['subject' => $subject, 'period' => $period])
                                    <th>
                                        @if (in_array($subject->id, $mySubjectIds, true))
                                            <a href="{{ route('teacher.subjects.show', ['subject' => $subject, 'date' => $date->toDateString(), 'period' => $period]) }}">{{ $subject->name }}</a>
                                        @else
                                            {{ $subject->name }}
                                        @endif
                                        <br><span class="muted">{{ $period }}限</span>
                                    </th>
                                @endforeach
                            </tr>
                            @foreach ($students as $student)
                                <tr>
                                    <td>{{ $student->student_number }}</td>
                                    <td>{{ $student->name }}</td>
                                    @foreach ($columns as ['subject' => $subject, 'period' => $period])
                                        <td>
                                            @if ($student->subjects->contains('id', $subject->id))
                                                {{-- 最初は記録のとおり（記録がなければ「未記録」。受付が終わったコマは「欠席（記録なし）」）
                                                     反映できなかったときは、変更した値に戻す（data-original は記録のままなので、色も付く） --}}
                                                @php($current = $attendances->get("{$student->id}-{$subject->id}-{$period}")?->status ?? 'none')
                                                @php($selected = old("statuses.{$student->id}.{$subject->id}.{$period}", $current))
                                                <select name="statuses[{{ $student->id }}][{{ $subject->id }}][{{ $period }}]"
                                                        data-original="{{ $current }}"
                                                        aria-label="{{ $student->name }} {{ $subject->name }} {{ $period }}限"
                                                        @disabled($lockReason !== null)>
                                                    <option value="none" @selected($selected === 'none')>{{ in_array("{$subject->id}-{$period}", $closedLessons, true) ? '欠席（記録なし）' : '未記録' }}</option>
                                                    @foreach ($statusOptions as $value => $label)
                                                        <option value="{{ $value }}" @selected($selected === $value)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <span>－</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </table>
                    </div>
                    @if ($errors->has('statuses') || $errors->has('date'))
                        <p class="warn">{{ $errors->first('statuses') ?: $errors->first('date') }}</p>
                    @endif
                    @if ($lockReason === null)
                        <button class="btn" id="class-attendance-apply">反映</button>
                    @else
                        <p class="muted">{{ $lockReason }}</p>
                    @endif
                </form>
                <p>「－」はその授業を受講していない生徒です。「反映」を押すと、変更したところだけが記録されます（記録方法は「手動」）。</p>
            @endif

            <p><a class="btn-action" href="{{ route('teacher.dashboard') }}">ホームへ戻る</a></p>
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        // 担任による出欠の変更：変えたセルに色を付け、「反映」では変えたセルだけを送る
        const attendanceForm = document.getElementById('class-attendance');
        const applyButton = document.getElementById('class-attendance-apply');
        if (attendanceForm && applyButton) {
            const selects = [...attendanceForm.querySelectorAll('select[data-original]')];
            const changed = () => selects.filter((select) => select.value !== select.dataset.original);
            let submitting = false;

            // 変えたセルに色を付けて、ボタンに件数を出す（何も変えていなければ押せない）
            const refresh = () => {
                selects.forEach((select) => select.closest('td').classList.toggle('changed', select.value !== select.dataset.original));
                const count = changed().length;
                applyButton.disabled = count === 0;
                applyButton.textContent = count > 0 ? `反映（${count}件）` : '反映';
            };

            attendanceForm.addEventListener('change', refresh);
            attendanceForm.addEventListener('submit', () => {
                // 変えていないセルは送らない（disabled の入力欄は送られない）
                selects.forEach((select) => { select.disabled = select.value === select.dataset.original; });
                submitting = true;
            });
            // 反映せずにほかの日・ほかの画面へ移ろうとしたら、確認する
            window.addEventListener('beforeunload', (event) => {
                if (!submitting && changed().length > 0) event.preventDefault();
            });
            // ブラウザの「戻る」でこの画面に戻ってきたときは、送る前の状態に戻す
            window.addEventListener('pageshow', () => {
                submitting = false;
                selects.forEach((select) => { select.disabled = false; });
                refresh();
            });
            refresh();
        }
    </script>
@endpush

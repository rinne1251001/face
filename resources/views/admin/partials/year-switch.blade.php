{{-- 年度の切り替え（選ぶとすぐに、同じ画面をその年度で開き直す。検索などの条件は付けない）
     使い方：@include('admin.partials.year-switch', ['year' => $year])
     $year … いま表示している年度 --}}
@php
    $yearOptions = \App\Models\AcademicYear::query()->orderByDesc('year')->get(['id', 'year']);
@endphp
<form method="GET" style="display: flex; align-items: center; gap: 8px; margin-bottom: 16px;">
    <label for="year-switch">年度</label>
    <select id="year-switch" name="year" onchange="this.form.submit()">
        @foreach ($yearOptions as $option)
            <option value="{{ $option->year }}" @selected($option->is($year))>{{ $option->label }}</option>
        @endforeach
    </select>
    <noscript><button>表示</button></noscript>
</form>
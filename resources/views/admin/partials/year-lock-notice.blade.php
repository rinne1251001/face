{{-- 過去の年度を開いているときのお知らせ（生徒の一覧・フォーム・一斉追加で使う）
     使い方：@include('admin.partials.year-lock-notice', ['year' => $year]) --}}
@once
    @push('styles')
        <style>
            .year-notice { padding: 10px 14px; border-radius: 6px; margin-bottom: 16px; font-size: 14px; }
            .year-notice.locked { background: color-mix(in srgb, var(--color-font-light), transparent 80%); }
            .year-notice.unlocked { background: color-mix(in srgb, var(--color-error-font), transparent 85%); color: var(--color-error-font); }
        </style>
    @endpush
@endonce
@if ($year->isPast())
    @if ($year->isEditable())
        <p class="year-notice unlocked">
            {{ $year->label }}は過去の年度ですが、編集を許可しています。修正が終わったら
            <a href="{{ route('admin.years.index', ['year' => $year->year]) }}">年度の画面</a>で「読み取り専用に戻す」を押してください。
        </p>
    @else
        <p class="year-notice locked">
            {{ $year->label }}は過去の年度のため、読み取り専用です。修正するときは
            <a href="{{ route('admin.years.index', ['year' => $year->year]) }}">年度の画面</a>で編集を許可してください。
        </p>
    @endif
@endif
@extends('layouts.app')
@section('title', $subject->name.' の出席受付')

@section('content')
<div id="face-checkin" data-checkin-url="{{ route('teacher.checkin.store', $subject) }}">
    <h1>出席受付：{{ $subject->name }}（{{ $subject->schedule_label }}）</h1>
    <p>
        {{ $today->format('Y年n月j日') }}（{{ \App\Models\Subject::DAYS[$today->dayOfWeekIso] }}）
        ／ 記録済み <strong id="recordedCount">{{ $recordedCount }}</strong> / {{ $studentCount }}人
    </p>
    @if ($wrongDay)
        <p class="warn">※ この授業は{{ \App\Models\Subject::DAYS[$subject->day_of_week] }}曜日です。今日の日付で記録されるので注意してください。</p>
    @endif

    <p id="status">画面を準備しています…</p>
    <div class="camera">
        <video id="video" autoplay muted playsinline></video>
        <canvas id="overlay"></canvas>
    </div>
    <p>
        <button id="toggleBtn" disabled>受付を一時停止</button>
        <a href="{{ route('teacher.subjects.show', $subject) }}">出席一覧・手動登録へ</a>
    </p>

    <h2>この画面での受付記録</h2>
    <table>
        <thead><tr><th>時刻</th><th>名前</th><th>クラス</th><th>結果</th></tr></thead>
        <tbody id="log"></tbody>
    </table>
</div>
@endsection

@push('scripts')
    @include('partials.script-check')
    @vite('resources/js/face-checkin.js')
@endpush

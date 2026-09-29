@extends('layouts.app')
@section('title', '顔の登録')

@section('content')
<div id="face-register" data-store-url="{{ route('faces.store', $person) }}">
    <h1>顔の登録：{{ $person->name }}（{{ $person->student_number }}）</h1>
    <p id="status">画面を準備しています…</p>
    <div class="camera">
        <video id="video" autoplay muted playsinline></video>
        <canvas id="overlay"></canvas>
    </div>
    <p>
        <button id="captureBtn" disabled>撮影開始（5枚）</button>
        <button id="saveBtn" disabled>登録する</button>
        <button id="resetBtn">撮り直す</button>
    </p>
    <div id="thumbs" class="thumbs"></div>
    <p><a href="{{ route('people.edit', $person) }}">人物の編集画面へ</a></p>
</div>
@endsection

@push('scripts')
    @include('faces._script-check')
    @vite('resources/js/face-register.js')
@endpush
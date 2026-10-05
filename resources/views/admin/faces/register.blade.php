@extends('layouts.app')
@section('title', '顔写真の登録')

@section('content')
<div id="face-register" data-store-url="{{ route('admin.faces.store', $student) }}">
    <h1>顔写真の登録：{{ $student->name }}（{{ $student->student_number }}）</h1>
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
    <p><a href="{{ route('admin.students.edit', $student) }}">生徒の編集画面へ</a></p>
    <p><a href="{{ route('admin.students.index') }}">顔写真は後で登録する（生徒一覧へ戻る）</a></p>
</div>
@endsection

@push('scripts')
    @include('partials.script-check')
    @vite('resources/js/face-register.js')
@endpush

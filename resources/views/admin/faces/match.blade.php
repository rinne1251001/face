@extends('layouts.app')
@section('title', '照合テスト')

@section('content')
<div id="face-match" data-match-url="{{ route('admin.match.run') }}">
    <h1>照合テスト</h1>
    <p class="muted">しきい値を調整するための画面です。ここで照合しても出席は記録されません。</p>
    <p id="status">画面を準備しています…</p>
    <div class="camera">
        <video id="video" autoplay muted playsinline></video>
        <canvas id="overlay"></canvas>
    </div>
    <p>
        <button id="cameraBtn" disabled>カメラで照合</button>
        <label><input type="checkbox" id="autoCheck" disabled> 連続して照合する</label>
    </p>
    <p>
        <label>画像ファイルで照合：<input type="file" id="fileInput" accept="image/jpeg,image/png" disabled></label>
    </p>
    <div id="result"></div>
</div>
@endsection

@push('scripts')
    @include('partials.script-check')
    @vite('resources/js/face-match.js')
@endpush

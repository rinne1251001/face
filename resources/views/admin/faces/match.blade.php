@extends('layouts.app')
@section('title', '照合テスト')

@section('content')

    {{-- 照合とログ読み込みのURLは、face-match.js がここから読み取る --}}
    <div id="face-match"
         data-match-url="{{ route('admin.match.run') }}"
         data-logs-url="{{ route('admin.match.logs') }}">
        <h1>照合の実験</h1>
        <p id="status">画面を準備しています…</p>
        <div class="camera">
            <video id="video" autoplay muted playsinline></video>
            <canvas id="overlay"></canvas>
        </div>
        <p>
            <button id="cameraBtn" disabled>カメラで照合</button>

            {{-- 押すたびに「ログを取る／取らない」が切り替わる --}}
            <form method="POST" action="{{ route('admin.match.log') }}" class="inline">
                @csrf
                <button>ログを{{ $logEnabled ? '取らない' : '取る' }}</button>
            </form>
            <span class="muted">（いまはログを{{ $logEnabled ? '取っています' : '取っていません' }}）</span>
        </p>
        <p>
            <label>画像ファイルで照合：<input type="file" id="fileInput" accept="image/jpeg,image/png" disabled></label>
        </p>
        <div id="result"></div>
    </div>

    <h2>照合ログ（新しい順に30件）</h2>
    <table>
        <thead>
            <tr>
                <th>名前（一番似ていた生徒）</th>
                <th>判定</th>
                <th>similarity</th>
                <th>margin</th>
                <th>created_at</th>
            </tr>
        </thead>
        <tbody id="logs"></tbody>
    </table>

@endsection

@push('scripts')
    @include('partials.script-check')
    @vite('resources/js/face-match.js')
@endpush
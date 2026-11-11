@extends('layouts.test')
@section('title', '照合テスト')
@push('styles')
    <style>
        .table-container {
            overflow-x: auto;
            max-width: 100%;
        }
        table {
            border-collapse: collapse;
        }
        th {
            background-color: var(--color-main);
            color: var(--color-bg);
            border: 1px solid var(--color-bg);
            padding-block: 7px;
            padding-inline: 20px;
        }
        td {
            color: var(--color-main);
            border: 1px solid var(--color-main);
            padding: 10px 20px;
        }
    </style>
@endpush
@section('content')
<main class="main">
    <div class="content-wrapper">
        <div class="page-header">
            <h1>照合テスト</h1>
            <div>
                <a class="btn" href="#">カードリーダーテスト</a>
            </div>
        </div>

        {{-- 照合とログ読み込みのURLは、face-match.js がここから読み取る --}}
        <div id="face-match" data-match-url="{{ route('admin.match.run') }}" data-logs-url="{{ route('admin.match.logs') }}">
            <p id="status">画面を準備しています…</p>
            <div class="camera">
                <video id="video" autoplay muted playsinline></video>
                <canvas id="overlay"></canvas>
            </div>

            <div>
                <div>
                    <button id="cameraBtn" class="btn" disabled>カメラで照合</button>
                    <form method="POST" action="{{ route('admin.match.log') }}" class="inline">
                        @csrf
                        <button class="btn">ログを{{ $logEnabled ? '取らない' : '取る' }}</button>
                    </form>
                    <span>（いまはログを{{ $logEnabled ? '取っています' : '取っていません' }}）</span>
                </div>
                <div>
                    <label>画像ファイルで照合：<input type="file" id="fileInput" accept="image/jpeg,image/png" disabled></label>
                </div>
            </div>

            <div id="result"></div>
        </div>

        <h2>照合ログ（新しい順に30件）</h2>
        <div class="table-container">
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
        </div>
    </div>
</main>
@endsection

@push('scripts')
    @include('partials.script-check')
    @vite('resources/js/face-match.js')
@endpush
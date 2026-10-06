<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>実験</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

    {{-- 照合は、管理者の照合テストと同じAPI（admin.match.run）に送る --}}
    <div id="face-match" data-match-url="{{ route('admin.match.run') }}">
        <h1>照合の実験</h1>
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

    <p><a href="{{ route('admin.students.index') }}">生徒一覧へ戻る</a></p>

    <script>
        const logsBody = document.getElementById('logs');

        // 照合ログの表を読み込み直す
        async function loadLogs() {
            const res = await fetch(@json(route('tests.experiment.logs')), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            logsBody.innerHTML = res.ok
                ? await res.text()
                : '<tr><td colspan="5">ログを読み込めませんでした。ページを再読み込みしてください。</td></tr>';
        }

        // 照合結果（#result）が書き換わるたびに、表を読み込み直す
        new MutationObserver(loadLogs).observe(document.getElementById('result'), { childList: true });
        loadLogs();
    </script>

    @include('partials.script-check')
    @vite('resources/js/face-match.js')
</body>
</html>
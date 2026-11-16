@extends('layouts.test')
@section('title', $subject->name.' の出席受付')
@push('styles')
    <style>
        /* カメラの上に重ねる案内（すりガラス）。face-checkin.js の showNotice() が出し入れする */
        .camera-notice {
            position: absolute; inset: 4%; /* カメラの少し内側で、全体にかぶせる */
            display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.4em;
            padding: 3% 4%;
            color: var(--color-font); text-align: center;
            pointer-events: none;
            /* すりガラス */
            background-color: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(23px);
            -webkit-backdrop-filter: blur(23px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.25);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .camera-notice[hidden] {
            display: none;
        }
        /* 顔認証の間は、顔が見えるように上に細く出す（下は文字の高さまで） */
        .camera-notice.is-top {
            bottom: auto;
        }
        /* 文字の大きさはカメラの幅（--camera-w）に合わせる。HTML の文字なので、拡大してもぼやけない */
        .camera-notice-title {
            font-size: max(18px, calc(var(--camera-w) / 18)); font-weight: bold; line-height: 1.3; text-wrap: balance;
        }
        .camera-notice-sub {
            font-size: max(13px, calc(var(--camera-w) / 30)); line-height: 1.4; text-wrap: balance;
        }
        .camera-notice-sub:empty {
            display: none;
        }
    </style>
@endpush

@section('content')
<main class="main">
    <div class="content-wrapper">

        {{-- 何限の出席かは、送り先の URL（?period=）で伝える --}}
        {{-- data-card-url … 入校証をかざしたときの送り先 ／ data-checkin-url … 顔認証の送り先 --}}
        <div id="face-checkin"
             data-card-url="{{ route('teacher.checkin.card', [$subject, 'period' => $period]) }}"
             data-checkin-url="{{ route('teacher.checkin.store', [$subject, 'period' => $period]) }}">
            <div class="page-header">
                <h1>出席受付：{{ $subject->name }}（{{ $subject->schedule_label }}）{{ $period }}限</h1>
            </div>

            {{-- 2時限以上続く授業は、ここで受け付ける時限を切り替える --}}
            @if (count($periods) > 1)
                <p>
                    受け付ける時限：
                    @foreach ($periods as $p)
                        @if ($p === $period)
                            <strong>【{{ $p }}限】</strong>
                        @else
                            <a class="btn-action" href="{{ route('teacher.checkin.show', [$subject, 'period' => $p]) }}">{{ $p }}限</a>
                        @endif
                    @endforeach
                </p>
            @endif
            <p>
                {{ $today->format('Y年n月j日') }}（{{ \App\Models\Subject::DAYS[$today->dayOfWeekIso] }}）
                ／ 記録済み <strong id="recordedCount">{{ $recordedCount }}</strong> / {{ $studentCount }}人
            </p>
            {{-- 受付時間（出席・遅刻の分かれ目）。この時間の外は受け付けないので、手動で登録する --}}
            <p>受付時間：{{ $window->label() }}（それ以外の時間は、出席一覧から手動で登録します）</p>
            @if ($wrongDay)
                <p>※ 今日はこの授業の日ではありません（{{ $subject->schedule_label }}{{ $dayNote ? "／今日は{$dayNote}" : '' }}）。今日の日付で記録されるので注意してください。</p>
            @endif

            <p id="status">画面を準備しています…</p>
            {{-- 入校証リーダーの状態（未接続・接続中・接続済み） --}}
            <p id="readerStatus">入校証リーダー：未接続</p>

            {{-- カメラ映像の上に、canvas（顔の枠）と、すりガラスの案内（「入校証をかざしてください」や、入校証の持ち主の名前）を重ねる。案内の中身は face-checkin.js の showNotice() が入れる --}}
            <div class="camera">
                <video id="video" autoplay muted playsinline></video>
                <canvas id="overlay"></canvas>
                <div class="camera-notice" id="notice" hidden>
                    <p class="camera-notice-title" id="noticeTitle"></p>
                    <p class="camera-notice-sub" id="noticeSub"></p>
                </div>
            </div>
            <p class="button-group">
                <button class="btn" id="toggleBtn" disabled>受付を一時停止</button>
                {{-- はじめて使うパソコンでは、このボタンからリーダーを選ぶ（2回目からは自動でつながる） --}}
                <button class="btn" id="readerBtn" disabled>リーダーに接続</button>
                {{-- リーダーがないとき・入校証を忘れた生徒がいるときは、顔認証だけの受付に切り替えられる --}}
                <button class="btn-action" id="modeBtn" disabled>入校証なしで受付（顔認証のみ）</button>
                <a class="btn-action" href="{{ route('teacher.subjects.show', [$subject, 'period' => $period]) }}">出席一覧・手動登録へ</a>
            </p>

            <h2>この画面での受付記録</h2>
            <table>
                <thead><tr><th>時刻</th><th>名前</th><th>クラス</th><th>結果</th></tr></thead>
                <tbody id="log"></tbody>
            </table>
        </div>
    </div>
</main>
@endsection

@push('scripts')
    @include('partials.script-check')
    @vite('resources/js/face-checkin.js')
@endpush
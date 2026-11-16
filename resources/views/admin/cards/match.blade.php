@extends('layouts.test')
@section('title', '入校証の照合・登録')
@push('styles')
    <style>
        .card-panel {
            display: grid; place-content: center; place-items: center; gap: 30px;
            width: 100%; min-height: 500px; padding: 30px 16px;
            background-color: var(--color-main);
        }
        .felica-btn {
            color: var(--color-main); background-color: var(--color-sub); padding: 20px 40px; border-radius: 20px;
            font-size: 1.5em; text-align: center; box-shadow: 4px 4px 10px rgba(0, 0, 0, 0.15);
        }
        button.felica-btn:disabled {
            opacity: 0.5; cursor: not-allowed;
        }

        /* =========================================================
           画面の切り替え
           JS は .card-panel の data-screen を書き換えるだけで、どの画面を見せるかは CSS が決める。
           表示中の画面は display: contents にして、中身を .card-panel のグリッドにそのまま並べる。
        ========================================================== */
        .screen {
            display: none;
        }
        .card-panel[data-screen="start"] > .screen-start,
        .card-panel[data-screen="connecting"] > .screen-connecting,
        .card-panel[data-screen="ready"] > .screen-ready,
        .card-panel[data-screen="link"] > .screen-link,
        .card-panel[data-screen="result"] > .screen-result {
            display: contents;
        }

        /* 接続中・待機中はゆっくり点滅させる */
        .screen-connecting .felica-btn,
        .screen-ready .felica-btn {
            animation: felica-pulse 1.6s ease-in-out infinite;
        }
        @keyframes felica-pulse {
            50% { opacity: 0.6; }
        }
        @media (prefers-reduced-motion: reduce) {
            .screen-connecting .felica-btn,
            .screen-ready .felica-btn {
                animation: none;
            }
        }

        /* 画面の中の文（空のときは場所を取らない） */
        .screen-text,
        .screen-note {
            color: var(--color-sub); text-align: center; max-width: 32em;
        }
        .screen-text {
            font-size: 1.1em;
        }
        .screen-note {
            font-size: 0.9em; min-height: 1.5em;
        }
        .screen-note:empty {
            visibility: hidden;
        }
        .idm {
            font-family: ui-monospace, monospace; overflow-wrap: anywhere;
        }

        /* =========================================================
           生徒の表示（入校証の持ち主）と、生徒を選ぶフォーム
        ========================================================== */
        .student-box {
            display: grid; gap: 15px;
            width: min(100%, 26em); padding: 25px 30px;
            background-color: var(--color-sub); color: var(--color-font);
        }
        .student-box .student-name {
            color: var(--color-main); font-size: 1.4em; font-weight: bold; text-align: center;
        }
        .student-box dl {
            display: grid; grid-template-columns: auto 1fr; gap: 6px 16px;
        }
        .student-box dt {
            opacity: 0.6; font-size: 0.9em;
        }
        .student-box label {
            font-size: 0.9em;
        }
        .student-box .buttons {
            display: flex; flex-wrap: wrap; align-items: center; gap: 12px;
        }
        .student-box .btn:disabled {
            opacity: 0.5; cursor: not-allowed;
        }
        .selected-student {
            min-height: 1.5em; font-weight: bold; color: var(--color-main);
        }

        .search-container input {
            width: 100%; padding: 5px 10px; background-color: var(--color-bg);
        }
        /* 検索の候補（検索欄のすぐ下に並べる。多いときは中でスクロールする） */
        .search-container ul {
            max-height: 200px; overflow-y: auto;
            background-color: var(--color-bg); box-shadow: 4px 4px 10px rgba(0, 0, 0, 0.15);
        }
        .search-container li {
            display: flex; flex-wrap: wrap; align-items: flex-end; gap: 7px;
            padding: 12px 15px; cursor: pointer; background-color: var(--color-bg);
        }
        .search-container li:hover,
        .search-container li:focus {
            filter: brightness(0.95); outline: none;
        }
        .search-container li span:not(:first-child) {
            font-size: 0.9em; opacity: 0.7;
        }
        .search-container li.empty {
            cursor: default; opacity: 0.7;
        }
        .search-container li.empty:hover {
            filter: none;
        }

        /* =========================================================
           ログ
        ========================================================== */
        .log-panel {
            margin-top: 20px; padding: 16px 20px; background-color: var(--color-sub);
        }
        .log-panel h2 {
            font-size: 1em; margin-bottom: 8px; opacity: 0.6; font-weight: bold;
        }
        .log-list {
            max-height: 240px; overflow-y: auto; font-size: 0.9em;
        }
        .log-list:empty::before {
            content: 'まだログはありません'; opacity: 0.6;
        }
        .log-list li {
            padding: 6px 0; border-bottom: 1px solid color-mix(in srgb, var(--color-main) 25%, transparent);
        }
        .log-list li.error {
            color: color-mix(in srgb, var(--color-accent) 55%, var(--color-font));
        }
        .log-list time {
            font-family: ui-monospace, monospace; opacity: 0.6; margin-right: 8px;
        }
    </style>
@endpush

@section('content')
<main class="main">
    <div class="content-wrapper">
        <div class="page-header">
            <h1>入校証の照合・登録</h1>
            <div>
                <a class="btn" href="{{ route('admin.match.show') }}">顔の照合テスト</a>
            </div>
        </div>

        {{-- 読み取り・生徒の検索・登録の送り先は、card-match.js がここから読み取る --}}
        <div class="card-panel" id="card-match" data-screen="start"
             data-touch-url="{{ route('admin.cards.match.touch') }}"
             data-search-url="{{ route('admin.cards.match.students') }}"
             data-link-url="{{ route('admin.cards.match.link') }}">

            {{-- 1. 読み取り開始（一度許可したリーダーが挿さっていれば、自動で 3 に進む） --}}
            <div class="screen screen-start">
                <div><button type="button" class="felica-btn" id="btn-start" disabled>読み取り開始</button></div>
                <div><p class="screen-note" id="status">画面を準備しています…</p></div>
            </div>

            {{-- 2. 接続中 --}}
            <div class="screen screen-connecting">
                <div><p class="felica-btn">接続中・・・</p></div>
                <div><p class="screen-note" id="connect-progress"></p></div>
            </div>

            {{-- 3. 入校証を待っている --}}
            <div class="screen screen-ready">
                <div><p class="felica-btn">入校証をタッチしてください</p></div>
                <div><p class="screen-note" id="ready-note"></p></div>
            </div>

            {{-- 4. まだ誰のものでもない入校証：生徒を選んで登録する --}}
            <div class="screen screen-link">
                <div>
                    <p class="screen-text">まだ生徒が登録されていない入校証です</p>
                    <p class="screen-note">IDm：<span class="idm" id="link-idm"></span></p>
                </div>
                <form class="student-box" id="link-form">
                    <div>
                        <label for="student-search">登録する生徒</label>
                        <div class="search-container">
                            <input type="text" id="student-search" placeholder="名前・フリガナ・学籍番号で検索" autocomplete="off">
                            <ul id="student-results" role="listbox" aria-label="検索結果"></ul>
                        </div>
                    </div>
                    <p class="selected-student" id="selected-student"></p>
                    <div class="buttons">
                        <button type="submit" class="btn" id="btn-link" disabled>この生徒を登録する</button>
                        <button type="button" class="btn-action" id="btn-cancel-link">登録しない</button>
                    </div>
                </form>
                <div><p class="screen-note" id="link-note"></p></div>
            </div>

            {{-- 5. 入校証の持ち主 --}}
            <div class="screen screen-result">
                <div class="student-box">
                    <p class="student-name"><span id="result-name"></span> さん</p>
                    <dl>
                        <dt>学籍番号</dt><dd id="result-number"></dd>
                        <dt>クラス</dt><dd id="result-class"></dd>
                        <dt>IDm</dt><dd class="idm" id="result-idm"></dd>
                    </dl>
                </div>
                <div><p class="screen-note" id="result-note"></p></div>
            </div>
        </div>

        {{-- ログ --}}
        <section class="log-panel" aria-label="ログ">
            <h2>ログ</h2>
            <ul class="log-list" id="log"></ul>
        </section>
    </div>
</main>
@endsection

@push('scripts')
    @include('partials.script-check')
    @vite('resources/js/card-match.js')
@endpush
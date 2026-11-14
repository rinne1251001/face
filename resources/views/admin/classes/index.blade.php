@extends('layouts.test')
@section('title', 'クラス一覧')
@push('styles')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=search" />
<style>
    :root {
        /* 削除・警告関連カラー */
        --color-danger-bg: #fff1f2;
        --color-danger-text: #e11d48;
        --color-danger-border: #fecdd3;
    }
    
    /* ------------------------------------------
        1. ヘッダー（ナビゲーションバー）
    ------------------------------------------ */
    .top-navbar {
        background-color: var(--color-bg);
        border-bottom: 1px solid var(--color-font-light);
        padding: 12px 40px;
    }

    /* Gridで「左側・中央ロゴ・右側」の3コラムにし、ロゴを完全中央配置 */
    .top-navbar-inner {
        max-width: 1200px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: 1fr auto 1fr;
        align-items: center;
    }

    /* ------------------------------------------
        2. メインコンテンツ・テーブル
    ------------------------------------------ */
    .table-container {
        background: var(--color-bg);
        border-radius: 10px;
        border: 1px solid var(--color-font-light);
        overflow: hidden;
    }

    .custom-table {
        width: 100%;
        border-collapse: collapse;
    }

    .custom-table th, .custom-table td {
        padding: 14px 20px;
        border-bottom: 1px solid var(--color-font-light);
        text-align: left;
    }

    .btn-edit {
        padding: 6px 14px;
        border: 1px solid var(--color-font-light);
        border-radius: 6px;
        text-decoration: none;
        color: var(--color-main) !important;
    }

    .btn-delete {
        padding: 6px 14px;
        background-color: var(--color-danger-bg);
        color: var(--color-danger-text);
        border: 1px solid var(--color-danger-border);
        border-radius: 6px;
        cursor: pointer;
    }

    .sort-link {
        display: flex;
        align-items: center;
        justify-content: flex-start; /* この表の見出しは左寄せなので、そろえる */
        gap: 2px;
        padding: 14px 20px;          /* ふつうの th と同じ余白 */
        color: inherit;              /* リンクの色ではなく、th の文字色にする */
        text-decoration: none;
        user-select: none;
    }
    .sort-arrow {
        font-size: 10px;
        opacity: .4;
    }
    .sort-arrow.active {
        opacity: 1; /* いまの並び順の矢印だけ、はっきり見せる */
    }
    /* 並び替えの読み込み中は、表を少し薄くする */
    [data-ajax-list][aria-busy="true"] {
        opacity: .5;
        transition: opacity .2s;
    }

    .class-search-container {
        display: flex;
        gap: 5px;
        color: var(--color-main);
    }
    .class-search-container button {
        background-color: var(--color-main);
        border: var(--color-main) 1px solid;
        color: var(--color-bg);
        padding: 10px;
        display: grid;
        place-content: center;
    }
    .class-search-container input {
        min-width: 0;
        border: var(--color-main) 1px solid;
        outline: none;
        padding: 10px 20px;
    }
    .class-search-container select {
        outline: none;
        border: var(--color-main) 1px solid;
        padding: 10px 50px 10px 20px;
        appearance: none;
        /* 画像（data:）の中では var(--color-main) が使えないので、色を直接書く（# は %23 と書く） */
        background: url("data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 10 6'><path d='M0 0l5 6 5-6z' fill='%238090C1'/></svg>") no-repeat right 20px center / 10px;
    }
    .class-search-container .clear-link {
        align-self: center;
        padding-inline: 10px;
    }
</style>
@endpush
@section('content')

<main class="main">
    <div class="content-wrapper">
        <div class="page-header">
            <h1>クラス一覧</h1>
            <a class="btn" href="{{ route('admin.classes.create') }}">＋ クラスを追加</a>
        </div>

                {{-- 検索フォーム。GET で送るので、検索した状態の URL をブックマーク・共有できる --}}
        <form class="class-search-container" method="GET" action="{{ route('admin.classes.index') }}" role="search">
            <div style="display: flex;">
                <input type="search" name="q" value="{{ $q }}" placeholder="クラス名・教師名で検索" aria-label="クラスを検索">
                <button aria-label="検索"><span class="material-symbols-outlined">search</span></button>
            </div>
            <div>
                {{-- 選んだら、ボタンを押さなくてもすぐに絞り込む --}}
                <select name="students" aria-label="生徒数で絞り込む" onchange="this.form.requestSubmit()">
                    <option value="">生徒数</option>
                    @foreach ($studentRanges as $key => $range)
                        <option value="{{ $key }}" @selected($students === (string) $key)>{{ $range['label'] }}</option>
                    @endforeach
                </select>
                <select name="teachers" aria-label="受け持ちの教師で絞り込む" onchange="this.form.requestSubmit()">
                    <option value="">担任</option>
                    <option value="with" @selected($teachers === 'with')>担任あり</option>
                    <option value="without" @selected($teachers === 'without')>担任なし</option>
                </select>
            </div>
            {{-- 検索しても、いまの並び順のままにする（data-sync-query：並び替えたときに ajax-list.js が値を書き換える） --}}
            <input type="hidden" name="sort" value="{{ $sort }}" data-sync-query>
            <input type="hidden" name="direction" value="{{ $direction }}" data-sync-query>
            @if ($filtering)
                <a class="clear-link" href="{{ route('admin.classes.index') }}">クリア</a>
            @endif
        </form>

        {{-- data-ajax-list：この中の見出し（data-ajax が付いたリンク）を押すと、リロードせずにここだけ入れ替わる --}}
        <div data-ajax-list>
            @include('admin.classes.partials.list')
        </div>

    </div>
</main>
@endsection
@push('scripts')
    {{-- 並び替えをリロードなしで行う（生徒一覧・教室一覧と同じ ajax-list.js を使う） --}}
    @vite('resources/js/ajax-list.js')
@endpush
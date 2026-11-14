@extends('layouts.test')
@section('title', '教室一覧')
@push('styles')
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=search" />
    <style>
        .table-container {
            overflow-x: auto;
            max-width: 100%;
        }
        .table-container table {
            border-collapse: collapse;
            width: 100%;
        }
        .table-container th {
            background-color: var(--color-main);
            color: var(--color-bg);
            border: 1px solid var(--color-bg);
            padding-block: 7px;
            padding-inline: 20px;
        }
        .table-container td {
            color: var(--color-main);
            border: 1px solid var(--color-main);
            padding: 10px 20px;
        }
        .sort-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 2px;
            padding: 7px 20px;
            color: inherit; /* リンクの色（--color-main）ではなく、th の文字色（白）にする */
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

        .room-search-container {
            display: flex;
            gap: 5px;
            color: var(--color-main);
        }
        .room-search-container button {
            background-color: var(--color-main);
            border: var(--color-main) 1px solid;
            color: var(--color-bg);
            padding: 10px;
            display: grid;
            place-content: center;
        }
        .room-search-container input {
            min-width: 0;
            border: var(--color-main) 1px solid;
            outline: none;
            padding: 10px 20px;
        }
        .room-search-container select {
            outline: none;
            border: var(--color-main) 1px solid;
            padding: 10px 50px 10px 20px;
            appearance: none;
            /* 画像（data:）の中では var(--color-main) が使えないので、色を直接書く（# は %23 と書く） */
            background: url("data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 10 6'><path d='M0 0l5 6 5-6z' fill='%238090C1'/></svg>") no-repeat right 20px center / 10px;
        }
        .room-search-container .clear-link {
            align-self: center;
            padding-inline: 10px;
        }
    </style>
@endpush

@section('content')

    <main class="main">
        <div class="content-wrapper">

            <div class="page-header">
                <h1>教室一覧</h1>
                <div>
                    <a class="btn" href="{{ route('admin.rooms.create') }}">教室追加</a>
                </div>
                
            </div>

            {{-- 検索フォーム。GET で送るので、検索した状態の URL をブックマーク・共有できる --}}
            <form class="room-search-container" method="GET" action="{{ route('admin.rooms.index') }}" role="search">
                <div style="display: flex;">
                    <input type="search" name="q" value="{{ $q }}" placeholder="教室名・クラス名で検索" aria-label="教室を検索">
                    <button aria-label="検索"><span class="material-symbols-outlined">search</span></button>
                </div>
                <div>
                    {{-- 階を選んだら、ボタンを押さなくてもすぐに絞り込む --}}
                    <select name="floor" aria-label="階で絞り込む" onchange="this.form.requestSubmit()">
                        <option value="">階</option>
                        @foreach ($floors as $f)
                            <option value="{{ $f->floor }}" @selected($floor === $f->floor)>{{ $f->floor_label }}</option>
                        @endforeach
                    </select>
                    {{-- 紐づくクラスのあり・なし（これも選んだらすぐ絞り込む） --}}
                    <select name="classes" aria-label="紐づくクラスで絞り込む" onchange="this.form.requestSubmit()">
                        <option value="">クラス</option>
                        <option value="with" @selected($classFilter === 'with')>クラスあり</option>
                        <option value="without" @selected($classFilter === 'without')>クラスなし</option>
                    </select>
                </div>
                {{-- 検索しても、いまの並び順のままにする（data-sync-query：並び替えたときに ajax-list.js が値を書き換える） --}}
                <input type="hidden" name="sort" value="{{ $sort }}" data-sync-query>
                <input type="hidden" name="direction" value="{{ $direction }}" data-sync-query>
                @if ($filtering)
                    <a class="clear-link" href="{{ route('admin.rooms.index') }}">クリア</a>
                @endif
            </form>

            {{-- data-ajax-list：この中の見出し（data-ajax が付いたリンク）を押すと、リロードせずにここだけ入れ替わる --}}
            <div data-ajax-list>
                @include('admin.rooms.partials.list')
            </div>

        </div>
    </main>

@endsection
@push('scripts')
    {{-- 並び替えをリロードなしで行う（生徒一覧と同じ ajax-list.js を使う） --}}
    @vite('resources/js/ajax-list.js')
@endpush
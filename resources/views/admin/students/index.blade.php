@extends('layouts.test')
@section('title', '生徒一覧')
@push('styles')
<style>
:root {
    /* ステータスバッジ（有効・無効・写真なし） */
    --color-status-active-bg: #dcfce7;
    --color-status-active-text: #166534;
    --color-status-inactive-bg: #fee2e2;
    --color-status-inactive-text: #991b1b;
    --color-status-warning-text: #d97706;
}

/* 全体 */
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.page-title { font-size: 24px; font-weight: bold; margin: 0; }

/* ボタン */
.btn-add { background: var(--color-main); color: var(--color-bg); padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: bold; }
.btn-add:hover { background: var(--color-accent); }
.btn-search { background: var(--color-main); color: var(--color-bg); border: none; padding: 8px 16px; border-radius: 6px; font-size: 14px; cursor: pointer; }
.btn-search:hover { background: var(--color-accent); }
.btn-action { color: var(--color-main); text-decoration: none; font-size: 13px; padding: 4px 8px; border: 1px solid var(--color-font-light); border-radius: 4px; background: var(--color-bg); }
.btn-action:hover { background: var(--color-bg); }

/* 検索・フィルター */
.filter-card { background: var(--color-bg); border: 1px solid var(--color-font-light); border-radius: 8px; padding: 16px; margin-bottom: 20px; }
.filter-form { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; }
.search-input-wrapper { position: relative; flex: 1; min-width: 220px; }
.search-icon { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: var(--color-sub); pointer-events: none; }
.input-search { width: 100%; padding: 8px 12px 8px 34px; border: 1px solid var(--color-font-light); border-radius: 6px; font-size: 14px; outline: none; box-sizing: border-box; }
.select-filter { padding: 8px 12px; border: 1px solid var(--color-font-light); border-radius: 6px; font-size: 14px; outline: none; }

/* テーブル */
.table-info { display: flex; justify-content: space-between; font-size: 14px; color: var(--color-font-light); margin-bottom: 10px; }
.table-wrapper { overflow-x: auto; border: 1px solid var(--color-font-light); border-radius: 8px; background: var(--color-bg); }
.student-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
.student-table th { background: var(--color-bg); color: var(--color-font-light); padding: 12px 16px; font-weight: 600; border-bottom: 1px solid var(--color-font-light); white-space: nowrap; }
.student-table th.sortable { padding: 0; }
.student-table td { padding: 12px 16px; border-bottom: 1px solid var(--color-bg); vertical-align: middle; }
.student-table tr:last-child td { border-bottom: none; }
.student-table tr:hover { background: var(--color-bg); }

/* 並び替え（見出し全体をリンクにして、どこを押しても並び替わるようにする） */
.sort-link { display: flex; align-items: center; gap: 2px; padding: 12px 16px; color: inherit; text-decoration: none; user-select: none; }
.sort-arrow { font-size: 10px; opacity: .3; }
.sort-arrow.active { opacity: 1; color: var(--color-main); }
[data-ajax-list][aria-busy="true"] { opacity: .5; transition: opacity .2s; } /* 読み込み中は少し薄くする */

/* バッジ・その他 */
.badge-status { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 12px; }
.badge-status.active { background: var(--color-status-active-bg); color: var(--color-status-active-text); }
.badge-status.inactive { background: var(--color-status-inactive-bg); color: var(--color-status-inactive-text); }
.badge-photo-none { color: var(--color-status-warning-text); font-weight: 500; }
.action-links { display: flex; gap: 8px; }

/* ページネーション */
.pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 20px; font-size: 14px; }
.page-link { padding: 6px 12px; border: 1px solid var(--color-font-light); border-radius: 4px; text-decoration: none; color: var(--color-font); }
.page-link.active { background: var(--color-main); color: var(--color-bg); border-color: var(--color-main); }
</style>
@endpush

@section('content')

<main class="main">
    <div class="content-wrapper">
        <div class="page-header">
            <h1 class="page-title">生徒一覧</h1>
            <div>
                <a href="{{ route('admin.students.create') }}" class="btn-add">＋ 生徒を追加</a>
                <a href="{{ route('admin.students.bulk.create') }}" class="btn-add">＋ 一斉に追加</a>
            </div>
        </div>

        <div class="filter-card">
            <form class="filter-form" method="GET">
                <div class="search-input-wrapper">
                    <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" class="input-search" name="q" value="{{ $q }}" placeholder="名前・学籍番号で検索...">
                </div>
                <select class="select-filter" name="class_id">
                    <option value="">すべてのクラス</option>
                    @foreach ($classes as $class)
                        <option value="{{ $class->id }}" @selected($classId === $class->id)>{{ $class->name }}</option>
                    @endforeach
                </select>
                <select class="select-filter" name="faces">
                    <option value="">顔写真：すべて</option>
                    <option value="registered" @selected($faces === 'registered')>登録済</option>
                    <option value="unregistered" @selected($faces === 'unregistered')>未登録</option>
                </select>
                {{-- 検索しても、いまの並び順のままにする（data-sync-query：並び替えたときに JS が値を書き換える） --}}
                <input type="hidden" name="sort" value="{{ $sort }}" data-sync-query>
                <input type="hidden" name="direction" value="{{ $direction }}" data-sync-query>
                <button type="submit" class="btn-search">検索</button>
            </form>
        </div>

        {{-- data-ajax-list：この中の data-ajax が付いたリンクを押すと、リロードせずにここだけ入れ替わる --}}
        <div data-ajax-list>
            @include('admin.students.partials.list')
        </div>

    </div>
</main>
@endsection
@push('scripts')
    @vite('resources/js/ajax-list.js')
@endpush
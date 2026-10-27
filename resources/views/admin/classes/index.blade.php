@extends('layouts.test')
@section('title', 'クラス一覧')
@push('styles')
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
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid var(--color-font-light);
    }

    .btn-create {
        background-color: var(--color-main);
        color: var(--color-bg) !important;
        padding: 9px 18px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
    }

    .control-panel {
        display: flex;
        justify-content: space-between;
        margin-bottom: 20px;
        background: var(--color-bg);
        padding: 14px 18px;
        border-radius: 10px;
        border: 1px solid var(--color-font-light);
    }

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
</style>
@endpush
@section('content')

<main class="main">
    <div class="content-wrapper">
        <div class="page-header">
            <h1>クラス一覧</h1>
            @if (Route::has('admin.classes.create'))
                <a href="{{ route('admin.classes.create') }}" class="btn-create">＋ クラスを追加</a>
            @else
                <a href="#" class="btn-create">＋ クラスを追加</a>
            @endif
        </div>

        <div class="control-panel">
            <input type="text" placeholder="クラス名や担当教師名で検索..." style="padding: 8px; width: 300px;">
            <select style="padding: 8px;">
                <option>クラス名順（昇順）</option>
                <option>生徒数が多い順</option>
            </select>
        </div>

        <div class="table-container">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>クラス名</th>
                        <th>生徒数</th>
                        <th>受け持ちの教師</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($classes as $schoolClass)
                        <tr>
                            <td><strong>{{ $schoolClass->name }}</strong></td>
                            <td>{{ $schoolClass->students_count }} 人</td>
                            <td>
                                @forelse ($schoolClass->teachers as $teacher)
                                    {{ $teacher->name }}@if (! $loop->last)、@endif
                                @empty
                                    なし
                                @endforelse
                            </td>
                            <td>
                                <a href="{{ route('admin.classes.edit', $schoolClass) }}" class="btn-edit">編集</a>
                                <form method="POST" action="{{ route('admin.classes.destroy', $schoolClass) }}" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn-delete">削除</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4">クラスが登録されていません。</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</main>
@endsection
@extends('layouts.test')
@section('title', 'クラス一覧')

@section('content')
<style>
    /* ------------------------------------------
       1. ヘッダー（ナビゲーションバー）
    ------------------------------------------ */
    .top-navbar {
        background-color: #ffffff;
        border-bottom: 1px solid #e2e8f0;
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

    .nav-links-left {
        display: flex;
        align-items: center;
        gap: 24px;
    }

    .nav-item {
        color: #2563eb;
        text-decoration: none;
        font-size: 15px;
        font-weight: 500;
    }

    .nav-item:hover {
        text-decoration: underline;
    }

    /* ★ 中央ロゴアイコン（サイズ調整はここ） ★ */
    .header-logo-container {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 0 20px;
    }

    .header-logo-img {
        height: 75px; /* ロゴの大きさ */
        width: auto;
        display: block;
    }

    .nav-right-group {
        display: flex;
        align-items: center;
        gap: 20px;
        justify-content: flex-end;
    }

    .user-area {
        display: flex;
        align-items: center;
        gap: 16px;
        font-size: 15px;
        color: #334155;
    }

    .btn-logout {
        padding: 6px 16px;
        background-color: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        cursor: pointer;
    }

    /* ------------------------------------------
       2. メインコンテンツ・テーブル
    ------------------------------------------ */
    .page-container {
        max-width: 1000px;
        margin: 0 auto;
        padding: 32px 20px;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid #e2e8f0;
    }

    .btn-create {
        background-color: #2563eb;
        color: #ffffff !important;
        padding: 9px 18px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
    }

    .control-panel {
        display: flex;
        justify-content: space-between;
        margin-bottom: 20px;
        background: #ffffff;
        padding: 14px 18px;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
    }

    .table-container {
        background: #ffffff;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        overflow: hidden;
    }

    .custom-table {
        width: 100%;
        border-collapse: collapse;
    }

    .custom-table th, .custom-table td {
        padding: 14px 20px;
        border-bottom: 1px solid #e2e8f0;
        text-align: left;
    }

    .btn-edit {
        padding: 6px 14px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        text-decoration: none;
        color: #334155 !important;
    }

    .btn-delete {
        padding: 6px 14px;
        background-color: #fff1f2;
        color: #e11d48;
        border: 1px solid #fecdd3;
        border-radius: 6px;
        cursor: pointer;
    }
</style>


<!-- ページコンテンツ -->
<div class="page-container">
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
                @if (isset($classes) && count($classes) > 0)
                    @foreach ($classes as $schoolClass)
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
                    @endforeach
                @else
                    <tr>
                        <td><strong>1年A組</strong></td>
                        <td>7 人</td>
                        <td>テスト教師</td>
                        <td>
                            <a href="#" class="btn-edit">編集</a>
                            <button type="button" class="btn-delete">削除</button>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
@endsection
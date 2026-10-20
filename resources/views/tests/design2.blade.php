@extends('layouts.test')
@section('title', '生徒一覧')

@push('styles')
<style>
/* 全体 */
.student-container { max-width: 1000px; margin: 20px auto; padding: 0 20px; font-family: sans-serif; color: #333; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.page-title { font-size: 24px; font-weight: bold; margin: 0; }

/* ボタン */
.btn-add { background: #2563eb; color: #fff; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: bold; }
.btn-add:hover { background: #1d4ed8; }
.btn-search { background: #475569; color: #fff; border: none; padding: 8px 16px; border-radius: 6px; font-size: 14px; cursor: pointer; }
.btn-search:hover { background: #334155; }
.btn-action { color: #2563eb; text-decoration: none; font-size: 13px; padding: 4px 8px; border: 1px solid #cbd5e1; border-radius: 4px; background: #fff; }
.btn-action:hover { background: #f1f5f9; }

/* 検索・フィルター */
.filter-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin-bottom: 20px; }
.filter-form { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; }
.search-input-wrapper { position: relative; flex: 1; min-width: 220px; }
.search-icon { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: #94a3b8; pointer-events: none; }
.input-search { width: 100%; padding: 8px 12px 8px 34px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none; box-sizing: border-box; }
.select-filter { padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none; }

/* テーブル */
.table-info { display: flex; justify-content: space-between; font-size: 14px; color: #64748b; margin-bottom: 10px; }
.table-wrapper { overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; }
.student-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
.student-table th { background: #f1f5f9; color: #334155; padding: 12px 16px; font-weight: 600; border-bottom: 1px solid #e2e8f0; white-space: nowrap; }
.student-table th.sortable { cursor: pointer; user-select: none; }
.student-table th.sortable:hover { background: #e2e8f0; }
.student-table td { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.student-table tr:last-child td { border-bottom: none; }
.student-table tr:hover { background: #f8fafc; }

/* バッジ・その他 */
.badge-status { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 12px; }
.badge-status.active { background: #dcfce7; color: #166534; }
.badge-status.inactive { background: #fee2e2; color: #991b1b; }
.badge-photo-none { color: #d97706; font-weight: 500; }
.action-links { display: flex; gap: 8px; }

/* ページネーション */
.pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 20px; font-size: 14px; }
.page-link { padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 4px; text-decoration: none; color: #333; }
.page-link.active { background: #2563eb; color: #fff; border-color: #2563eb; }
</style>
@endpush

@section('content')
<main>
    <div class="student-container">
        <div class="page-header">
            <h1 class="page-title">生徒一覧</h1>
            <a href="#" class="btn-add">＋ 生徒を追加</a>
        </div>

        <div class="filter-card">
            <form class="filter-form">
                <div class="search-input-wrapper">
                    <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" class="input-search" placeholder="名前・学籍番号で検索...">
                </div>
                <select class="select-filter">
                    <option value="">すべてのクラス</option>
                    <option value="1A">1年A組</option>
                    <option value="1B">1年B組</option>
                </select>
                <select class="select-filter">
                    <option value="">顔写真：すべて</option>
                    <option value="registered">登録済</option>
                    <option value="unregistered">未登録</option>
                </select>
                <button type="submit" class="btn-search">検索</button>
            </form>
        </div>

        <div class="table-info">
            <span>全 <strong>3</strong> 件中 1〜3 件を表示</span>
        </div>

        <div class="table-wrapper">
            <table class="student-table">
                <thead>
                    <tr>
                        <th class="sortable">学籍番号 ▲▼</th>
                        <th class="sortable">名前 ▲▼</th>
                        <th class="sortable">クラス ▲▼</th>
                        <th>状態</th>
                        <th>顔写真</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>S0001</td>
                        <td>見本 一郎</td>
                        <td>1年A組</td>
                        <td><span class="badge-status active">有効</span></td>
                        <td><span class="badge-photo-none">未登録</span></td>
                        <td class="action-links">
                            <a href="#" class="btn-action">編集</a>
                            <a href="#" class="btn-action">顔を登録</a>
                        </td>
                    </tr>
                    <tr>
                        <td>S0002</td>
                        <td>見本 花子</td>
                        <td>1年A組</td>
                        <td><span class="badge-status active">有効</span></td>
                        <td><span class="badge-photo-none">未登録</span></td>
                        <td class="action-links">
                            <a href="#" class="btn-action">編集</a>
                            <a href="#" class="btn-action">顔を登録</a>
                        </td>
                    </tr>
                    <tr>
                        <td>S0003</td>
                        <td>見本 次郎</td>
                        <td>1年B組</td>
                        <td><span class="badge-status inactive">無効</span></td>
                        <td><span class="badge-photo-none">未登録</span></td>
                        <td class="action-links">
                            <a href="#" class="btn-action">編集</a>
                            <a href="#" class="btn-action">顔を登録</a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="pagination">
            <a href="#" class="page-link">&laquo; 前へ</a>
            <a href="#" class="page-link active">1</a>
            <a href="#" class="page-link">次へ &raquo;</a>
        </div>
    </div>
</main>
@endsection
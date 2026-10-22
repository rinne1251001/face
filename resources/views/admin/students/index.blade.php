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
.student-container { max-width: 1000px; margin: 20px auto; padding: 0 20px; font-family: sans-serif; }
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
.student-table th.sortable { cursor: pointer; user-select: none; }
.student-table td { padding: 12px 16px; border-bottom: 1px solid var(--color-bg); vertical-align: middle; }
.student-table tr:last-child td { border-bottom: none; }
.student-table tr:hover { background: var(--color-bg); }

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

<main>
    <div class="student-container">
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
                    @forelse ($students as $student)
                        <tr>
                            <td>{{ $student->student_number }}</td>
                            <td>{{ $student->name }}</td>
                            <td>{{ $student->schoolClass->name }}</td>
                            <td><span class="badge-status {{ $student->is_active ? 'active' : 'inactive' }}">{{ $student->is_active ? '有効' : '停止中' }}</span></td>
                            <td>
                                @if ($student->face_samples_count > 0)
                                    {{ $student->face_samples_count }}枚
                                @else
                                    <span class="badge-photo-none">未登録</span></td>
                                @endif
                            <td class="action-links">
                                <a href="{{ route('admin.students.edit', $student) }}" class="btn-action">編集</a>
                                <a href="{{ route('admin.faces.create', $student) }}">顔を登録</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6">該当する生徒はいません。</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination">
            @if ($students->previousPageUrl())<a href="{{ $students->previousPageUrl() }}" class="page-link">&laquo; 前へ</a>@endif
            <a href="#" class="page-link active">{{ $students->currentPage() }} / {{ $students->lastPage() }}</a>
            @if ($students->nextPageUrl())<a href="{{ $students->nextPageUrl() }}" class="page-link">次へ &raquo;</a>@endif
        </div>
    </div>
</main>
@endsection

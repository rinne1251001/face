@extends('layouts.test')
@section('title', '生徒一覧')


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
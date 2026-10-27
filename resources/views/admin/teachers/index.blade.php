@extends('layouts.test')
@section('title', '教師一覧')
@push('styles')
<style>
    :root {
        --bg-summary: #f7f2ec;
        --border-summary: #eee8e0;
        --bg-card: #ffffff;
        --text-sub: #4f4d60;
        --text-search: #999999;
        --bg-th: #fbfaf9;
        --border-td: #f0eef0;
        --avatar-cool-bg: #e9edf8;
        --avatar-warm-bg: #fae9e5;
        --avatar-warm-color: #b86e62;
        --tag-bg: #f2f3f8;
        --tag-color: #626d91;
        --danger: #b86e62;
    }

    /* ページタイトルと追加ボタン */
    .heading,
    .toolbar,
    .summary {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
    }

    .heading { margin-bottom: 24px; }
    .eyebrow { margin-bottom: 8px !important; color: var(--color-main); font-size: 12px; font-weight: 700; letter-spacing: .12em; }
    h1 { margin: 0; font-size: clamp(28px, 4vw, 38px); }
    .description { margin-top: 8px !important; color: var(--color-main); font-size: 14px; }
    .add-btn { padding: 12px 18px; border: 0; border-radius: 9px; background: var(--color-main); color: #fff; font-size: 14px; font-weight: 700; }

    /* 教師数のサマリー */
    .summary { margin-bottom: 18px; padding: 18px 22px; border: 1px solid var(--border-summary); border-radius: 12px; background: var(--bg-summary); }
    .count { display: block; margin-top: 3px; font-size: 26px; font-weight: 800; }
    .count small,
    .summary-note { color: var(--color-main); font-size: 13px; }

    /* 教師一覧テーブル */
    .card { overflow: hidden; border: 1px solid var(--color-font-light); border-radius: 12px; background: var(--bg-card); box-shadow: 0 10px 28px rgb(40 38 62 / 5%); }
    .toolbar { padding: 16px 20px; border-bottom: 1px solid var(--color-font-light); }
    .toolbar h2 { margin: 0; font-size: 16px; }
    .search { padding: 9px 12px; border: 1px solid var(--color-font-light); border-radius: 8px; color: var(--text-search); font-size: 13px; }
    .table-wrap { overflow-x: auto; }
    table { width: 100%; margin: 0; border-collapse: collapse; text-align: left; }
    th,
    td { padding: 14px 16px; border: 0; border-bottom: 1px solid var(--border-td); vertical-align: middle; }
    th { background: var(--bg-th); color: var(--color-main); font-size: 11px; white-space: nowrap; }
    td { color: var(--text-sub); font-size: 13px; }
    tbody tr:last-child td { border-bottom: 0; }

    /* 名前・クラス・授業の表示 */
    .person { display: flex; align-items: center; gap: 10px; min-width: 150px; }
    .avatar { display: grid; width: 36px; height: 36px; flex: 0 0 36px; place-items: center; border-radius: 10px; background: var(--avatar-cool-bg); color: var(--color-main); font-weight: 800; }
    .avatar--warm { background: var(--avatar-warm-bg); color: var(--avatar-warm-color); }
    .name,
    .subject { display: block; color: var(--color-font); font-weight: 700; }
    .subtext { display: block; margin-top: 3px; color: var(--color-main); font-size: 11px; }
    .tags,
    .actions { display: flex; flex-wrap: wrap; gap: 6px; white-space: nowrap; }
    .tag { padding: 4px 7px; border-radius: 5px; background: var(--tag-bg); color: var(--tag-color); font-size: 11px; }
    .action { padding: 5px 9px; border: 1px solid var(--color-font-light); border-radius: 6px; background: var(--bg-card); color: var(--color-main); font-size: 11px; }
    .action--delete { color: var(--danger); }
    .note { margin-top: 12px !important; color: var(--color-main); font-size: 12px; }

    @media (max-width: 767px) {
        .heading,
        .toolbar,
        .summary { align-items: flex-start; flex-direction: column; }
    }
</style>
@endpush
@section('content')

<main class="main">
    <div class="content-wrapper">
        <div class="heading">
            <div>
                <h1>教師一覧</h1>
                <p class="description">教師と担当クラス・授業をまとめて確認できます。</p>
            </div>
            <form action="{{ route('admin.teachers.create') }}" method="GET">
                <button class="add-btn" type="submit">＋ 教師を追加</button>
            </form>
        </div>

        <section class="summary" aria-label="教師数">
            <div>
                <span class="subtext">登録されている教師</span>
                <span class="count">{{ \App\Models\Teacher::count() }}<small> 名</small></span>
            </div>
        </section>

        <section class="card" aria-labelledby="teacher-demo-title">
            <div class="toolbar">
                <h2 id="teacher-demo-title">教師アカウント</h2>
                <span class="search">⌕ 名前・ログインIDで検索</span>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>教師</th><th>ログインID</th><th>受け持ちクラス</th><th>担当授業</th><th>操作</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($teachers as $teacher)
                            <tr>
                                <td><div class="person"><span class="avatar">佐</span><span><span class="name">{{ $teacher->name }}</span><span class="subtext">教師アカウント</span></span></div></td>
                                <td>{{ $teacher->login_id }}</td>
                                <td><div class="tags"><span class="tag">1年A組</span><span class="tag">1年B組</span></div></td>
                                <td><span class="subject">国語</span><span class="subtext">月曜日・1〜2限</span></td>
                                <td><div class="actions"><button class="action" type="button">編集</button><button class="action action--delete" type="button">削除</button></div></td>
                            </tr>
                            @empty
                            <tr><td colspan="5"><span class="muted">なし</span></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>
@endsection
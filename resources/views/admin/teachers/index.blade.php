@extends('layouts.test')
@section('title', '教師一覧')
@push('styles')
<style>
    :root {
        --text-sub: #4f4d60;
        --avatar-cool-bg: #e9edf8;
        --avatar-warm-bg: #fae9e5;
        --avatar-warm-color: #b86e62;
        --tag-bg: #f2f3f8;
        --tag-color: #626d91;
        --danger: #b86e62;
    }

    .page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
    }

    .summary { display: flex; align-items: center; justify-content: space-between; gap: 20px; margin-bottom: 20px; padding: 16px; border: 1px solid var(--color-font-light); border-radius: 8px; background: var(--color-bg); }
    .count { display: block; margin-top: 3px; font-size: 26px; font-weight: 800; }
    .count small,
    .subtext { color: var(--color-font-light); font-size: 13px; }

    .card { overflow: hidden; border: 1px solid var(--color-font-light); border-radius: 8px; background: var(--color-bg); }
    .toolbar { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; padding: 16px; border-bottom: 1px solid var(--color-font-light); }
    .toolbar h2 { margin: 0; font-size: 16px; }
    .search { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
    .search-input { min-width: 220px; padding: 8px 12px; border: 1px solid var(--color-font-light); border-radius: 6px; font-size: 14px; }
    .table-wrap { overflow-x: auto; }
    table { width: 100%; margin: 0; border-collapse: collapse; text-align: left; }
    th,
    td { padding: 12px 16px; border: 0; border-bottom: 1px solid var(--color-font-light); vertical-align: middle; }
    th { background: var(--color-bg); color: var(--color-font-light); font-size: 14px; font-weight: 600; white-space: nowrap; }
    td { color: var(--text-sub); font-size: 14px; }
    tbody tr:last-child td { border-bottom: 0; }

    .person { display: flex; align-items: center; gap: 10px; min-width: 150px; }
    .avatar { display: grid; width: 36px; height: 36px; flex: 0 0 36px; place-items: center; border-radius: 10px; background: var(--avatar-cool-bg); color: var(--color-main); font-weight: 800; }
    .avatar--warm { background: var(--avatar-warm-bg); color: var(--avatar-warm-color); }
    .name,
    .subject { display: block; color: var(--color-font); font-weight: 700; }
    .subtext { display: block; margin-top: 3px; color: var(--color-font-light); font-size: 11px; }
    .tags,
    .actions { display: flex; flex-wrap: wrap; gap: 6px; white-space: nowrap; }
    .tag { padding: 4px 7px; border-radius: 5px; background: var(--tag-bg); color: var(--tag-color); font-size: 11px; }
    .btn-action { display: inline-block; padding: 4px 8px; border: 1px solid var(--color-font-light); border-radius: 4px; background: var(--color-bg); color: var(--color-main); font-size: 13px; text-decoration: none; }
    .btn-action--delete { color: var(--danger); }

    @media (max-width: 767px) {
        .toolbar,
        .summary { align-items: flex-start; flex-direction: column; }
    }
</style>
@endpush
@section('content')

<main class="main">
    <div class="content-wrapper">
        <div class="page-header">
            <h1>教師一覧（{{ $year->label }}）</h1>
            <a class="btn" href="{{ route('admin.teachers.create', ['year' => $year->year]) }}">＋ 教師を追加</a>
        </div>

        {{-- 受け持ちクラス・担当授業を表示する年度 --}}
        @if ($year)
            @include('admin.partials.year-switch', ['year' => $year])
        @endif

        <section class="summary" aria-label="教師数">
            <div>
                <span class="subtext">登録されている教師</span>
                <span class="count">{{ $total }}<small> 名</small></span>
            </div>
        </section>

        <section class="card" aria-labelledby="teacher-demo-title">
            <div class="toolbar">
                <h2 id="teacher-demo-title">教師アカウント</h2>
                <form class="search" method="GET" action="{{ route('admin.teachers.index') }}" role="search">
                    <input class="search-input" type="search" name="q" value="{{ $q }}"
                           placeholder="⌕ 名前・ログインID・クラス・授業で検索..." aria-label="教師を検索">
                    <button class="btn" type="submit">検索</button>
                    @if ($q !== '')
                        <a class="btn-action" href="{{ route('admin.teachers.index') }}">クリア</a>
                    @endif
                    @if ($year)
                        <input type="hidden" name="year" value="{{ $year->year }}">
                    @endif
                </form>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>教師</th><th>ログインID</th><th>受け持ちクラス（{{ $year?->label }}）</th><th>担当授業（{{ $year?->label }}）</th><th>操作</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($teachers as $teacher)
                            <tr>
                                <td><div class="person"><span class="avatar">{{ mb_substr($teacher->name, 0, 1) }}</span><span><span class="name">{{ $teacher->name }}</span><span class="subtext">{{ $teacher->can_login ? '教師アカウント' : 'ログイン不可' }}</span></span></div></td>
                                <td>{{ $teacher->login_id }}</td>
                                <td>
                                    <div class="tags">
                                        @forelse ($teacher->schoolClasses as $schoolClass)
                                            <span class="tag">{{ $schoolClass->name }}</span>
                                        @empty
                                            <span>なし</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td>
                                    @forelse ($teacher->subjects as $subject)
                                        <div><span class="subject">{{ $subject->name }}</span><span class="subtext">{{ $subject->schedule_label }}</span></div>
                                    @empty
                                        未設定
                                    @endforelse
                                </td>
                                <td>
                                    <div class="actions">
                                        <a class="btn-action" href="{{ route('admin.teachers.edit', [$teacher, 'year' => $year?->year]) }}">編集</a>
                                        <form method="POST" action="{{ route('admin.teachers.destroy', $teacher) }}" onsubmit="return confirm('この教師を削除しますか？')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn-action btn-action--delete">削除</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5">{{ $q !== '' ? "「{$q}」に当てはまる教師はいません。" : '教師が登録されていません。' }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>
@endsection
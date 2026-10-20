@extends('layouts.test')
@section('title', '教師一覧デザイン')

@push('styles')
<style>
    /* このページの中だけにデザインを適用 */
    .teacher-demo {
        --ink: #28263e;
        --muted: #77768a;
        --blue: #596a9f;
        --line: #ece9e5;
        max-width: 1120px;
        margin: 48px auto;
        padding: 0 24px 64px;
        color: var(--ink);
    }

    .teacher-demo * { box-sizing: border-box; }
    .teacher-demo p { margin: 0; }

    /* ページタイトルと追加ボタン */
    .teacher-demo__heading,
    .teacher-demo__toolbar,
    .teacher-demo__summary {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
    }

    .teacher-demo__heading { margin-bottom: 24px; }
    .teacher-demo__eyebrow { margin-bottom: 8px !important; color: var(--blue); font-size: 12px; font-weight: 700; letter-spacing: .12em; }
    .teacher-demo h1 { margin: 0; font-size: clamp(28px, 4vw, 38px); }
    .teacher-demo__description { margin-top: 8px !important; color: var(--muted); font-size: 14px; }
    .teacher-demo__add { padding: 12px 18px; border: 0; border-radius: 9px; background: var(--blue); color: #fff; font-size: 14px; font-weight: 700; }

    /* 教師数のサマリー */
    .teacher-demo__summary { margin-bottom: 18px; padding: 18px 22px; border: 1px solid #eee8e0; border-radius: 12px; background: #f7f2ec; }
    .teacher-demo__count { display: block; margin-top: 3px; font-size: 26px; font-weight: 800; }
    .teacher-demo__count small,
    .teacher-demo__summary-note { color: var(--muted); font-size: 13px; }

    /* 教師一覧テーブル */
    .teacher-demo__card { overflow: hidden; border: 1px solid var(--line); border-radius: 12px; background: #fff; box-shadow: 0 10px 28px rgb(40 38 62 / 5%); }
    .teacher-demo__toolbar { padding: 16px 20px; border-bottom: 1px solid var(--line); }
    .teacher-demo__toolbar h2 { margin: 0; font-size: 16px; }
    .teacher-demo__search { padding: 9px 12px; border: 1px solid var(--line); border-radius: 8px; color: #999; font-size: 13px; }
    .teacher-demo__table-wrap { overflow-x: auto; }
    .teacher-demo table { width: 100%; margin: 0; border-collapse: collapse; text-align: left; }
    .teacher-demo th,
    .teacher-demo td { padding: 14px 16px; border: 0; border-bottom: 1px solid #f0eef0; vertical-align: middle; }
    .teacher-demo th { background: #fbfaf9; color: var(--muted); font-size: 11px; white-space: nowrap; }
    .teacher-demo td { color: #4f4d60; font-size: 13px; }
    .teacher-demo tbody tr:last-child td { border-bottom: 0; }

    /* 名前・クラス・授業の表示 */
    .teacher-demo__person { display: flex; align-items: center; gap: 10px; min-width: 150px; }
    .teacher-demo__avatar { display: grid; width: 36px; height: 36px; flex: 0 0 36px; place-items: center; border-radius: 10px; background: #e9edf8; color: var(--blue); font-weight: 800; }
    .teacher-demo__avatar--warm { background: #fae9e5; color: #b86e62; }
    .teacher-demo__name,
    .teacher-demo__subject { display: block; color: var(--ink); font-weight: 700; }
    .teacher-demo__subtext { display: block; margin-top: 3px; color: var(--muted); font-size: 11px; }
    .teacher-demo__tags,
    .teacher-demo__actions { display: flex; flex-wrap: wrap; gap: 6px; white-space: nowrap; }
    .teacher-demo__tag { padding: 4px 7px; border-radius: 5px; background: #f2f3f8; color: #626d91; font-size: 11px; }
    .teacher-demo__action { padding: 5px 9px; border: 1px solid var(--line); border-radius: 6px; background: #fff; color: var(--blue); font-size: 11px; }
    .teacher-demo__action--delete { color: #b86e62; }
    .teacher-demo__note { margin-top: 12px !important; color: var(--muted); font-size: 12px; }

    @media (max-width: 700px) {
        .teacher-demo { margin-top: 28px; padding: 0 16px 40px; }
        .teacher-demo__heading,
        .teacher-demo__toolbar,
        .teacher-demo__summary { align-items: flex-start; flex-direction: column; }
    }
</style>
@endpush

@section('content')
<main class="teacher-demo"> {{-- デザイン確認用のサンプルです。検索・追加・編集・削除は動作しません。 --}}
    <header class="teacher-demo__heading"> {{-- ページタイトルと追加ボタン --}}
        <div>
            <p class="teacher-demo__eyebrow">ADMINISTRATION / PEOPLE</p>{{-- ページタイトルと説明文 --}}
            <h1>教師一覧</h1>
            <p class="teacher-demo__description">教師と担当クラス・授業をまとめて確認できます。</p>
        </div>
        <button class="teacher-demo__add" type="button">＋ 教師を追加</button>
    </header>

    <section class="teacher-demo__summary" aria-label="教師数">
        <div>
            <span class="teacher-demo__subtext">登録されている教師</span>
            <span class="teacher-demo__count">08<small> 名</small></span>{{-- 教師数。仮置きなので人数は反映してない --}}
        </div>
        <p class="teacher-demo__summary-note">担当やクラスの割り当てを一覧で確認</p>
    </section>

    <section class="teacher-demo__card" aria-labelledby="teacher-demo-title">{{-- 教師一覧テーブル --}}
        <div class="teacher-demo__toolbar"> {{-- テーブルの上部にあるタイトルと検索欄 --}}
            <h2 id="teacher-demo-title">教師アカウント</h2>
            <span class="teacher-demo__search">⌕　名前・ログインIDで検索</span>
        </div>

        <div class="teacher-demo__table-wrap">{{-- テーブルの横スクロールを可能にするラッパー --}}
            <table>
                <thead>
                    <tr><th>教師</th><th>ログインID</th><th>受け持ちクラス</th><th>担当授業</th><th>操作</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td><div class="teacher-demo__person"><span class="teacher-demo__avatar">佐</span><span><span class="teacher-demo__name">佐藤 花子</span><span class="teacher-demo__subtext">教師アカウント</span></span></div></td>
                        <td>hanako.sato</td>
                        <td><div class="teacher-demo__tags"><span class="teacher-demo__tag">1年A組</span><span class="teacher-demo__tag">1年B組</span></div></td>
                        <td><span class="teacher-demo__subject">国語</span><span class="teacher-demo__subtext">月曜日・1〜2限</span></td>
                        <td><div class="teacher-demo__actions"><button class="teacher-demo__action" type="button">編集</button><button class="teacher-demo__action teacher-demo__action--delete" type="button">削除</button></div></td>
                    </tr>
                    <tr>
                        <td><div class="teacher-demo__person"><span class="teacher-demo__avatar teacher-demo__avatar--warm">鈴</span><span><span class="teacher-demo__name">鈴木 健</span><span class="teacher-demo__subtext">教師アカウント</span></span></div></td>
                        <td>ken.suzuki</td>
                        <td><div class="teacher-demo__tags"><span class="teacher-demo__tag">2年A組</span></div></td>
                        <td><span class="teacher-demo__subject">数学</span><span class="teacher-demo__subtext">火曜日・3〜4限</span></td>
                        <td><div class="teacher-demo__actions"><button class="teacher-demo__action" type="button">編集</button><button class="teacher-demo__action teacher-demo__action--delete" type="button">削除</button></div></td>
                    </tr>
                    <tr>
                        <td><div class="teacher-demo__person"><span class="teacher-demo__avatar">高</span><span><span class="teacher-demo__name">高橋 美咲</span><span class="teacher-demo__subtext">教師アカウント</span></span></div></td>
                        <td>misaki.takahashi</td>
                        <td><div class="teacher-demo__tags"><span class="teacher-demo__tag">未割り当て</span></div></td>
                        <td><span class="teacher-demo__subject">英語</span><span class="teacher-demo__subtext">木曜日・2〜3限</span></td>
                        <td><div class="teacher-demo__actions"><button class="teacher-demo__action" type="button">編集</button><button class="teacher-demo__action teacher-demo__action--delete" type="button">削除</button></div></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <p class="teacher-demo__note">デザイン確認用のサンプルです。検索・追加・編集・削除は動作しません。</p>
</main>
@endsection

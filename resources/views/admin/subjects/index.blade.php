@extends('layouts.test')
@section('title', '授業一覧')
@push('styles')
<style>
    :root {
        /* ボーダー・ライン色 */
        --color-border: #EAE8F0;
        --color-border-light: #F0EEF3;
        --color-border-edit: #E0E4F3;
        --color-border-delete: #F0C5BF;

        /* 背景色（テーブル・バッジ・ボタン等） */
        --color-bg-th: #FCFBF9;
        --color-bg-tr-hover: #FAF9FC;
        --color-bg-schedule: #F0F1F9;
        --color-bg-edit: #EEF0F9;
        --color-bg-unset: #FFF0ED;

        /* テキスト色（特定コンポーネント用） */
        --color-text-unset: #A84D43;
        --color-text-delete: #B85B50;

        /* 影（ボックスシャドウ） */
        --color-shadow: rgba(40, 38, 62, 0.05);
    }

    .subjects-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
        margin-bottom: 28px;
    }

    .subjects-heading {
        margin: 0 0 8px;
        font-size: 28px;
        font-weight: 700;
        letter-spacing: 0.04em;
    }

    .subjects-description {
        margin: 0;
        color: var(--color-font-light);
        font-size: 14px;
        line-height: 1.7;
    }

    .subject-add-button {
        display: inline-flex;
        justify-content: center;
        align-items: center;
        gap: 8px;
        min-height: 46px;
        padding: 12px 20px;
        border-radius: 10px;
        background: var(--color-main);
        color: var(--color-bg);
        font-size: 14px;
        font-weight: 700;
        text-decoration: none;
        border: none;
        cursor: pointer;
        transition: opacity 0.2s, transform 0.2s;
    }

    .subject-add-button:hover {
        opacity: 0.85;
        transform: translateY(-2px);
    }

    .subjects-card {
        overflow: hidden;
        background: var(--color-bg);
        border: 1px solid var(--color-border);
        border-radius: 16px;
        box-shadow: 0 5px 20px var(--color-shadow);
    }

    .subjects-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 22px 24px;
        background: var(--color-sub);
        border-bottom: 1px solid var(--color-border);
    }

    .subjects-card-title {
        margin: 0;
        font-size: 17px;
        font-weight: 700;
    }

    .subjects-count {
        color: var(--color-font-light);
        font-size: 13px;
        white-space: nowrap;
    }

    .subjects-table-wrapper {
        width: 100%;
        overflow-x: auto;
    }

    .subjects-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        font-size: 14px;
    }

    .subjects-table th {
        padding: 16px 20px;
        background: var(--color-bg-th);
        color: var(--color-font-light);
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .subjects-table td {
        padding: 20px;
        border-top: 1px solid var(--color-border-light);
        vertical-align: middle;
        line-height: 1.7;
    }

    .subjects-table tbody tr {
        transition: background 0.2s;
    }

    .subjects-table tbody tr:hover {
        background: var(--color-bg-tr-hover);
    }

    .subject-name {
        font-weight: 700;
        color: var(--color-font);
    }

    .schedule-label {
        display: inline-block;
        padding: 6px 10px;
        border-radius: 7px;
        background: var(--color-bg-schedule);
        color: var(--color-font);
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
    }

    .student-count {
        font-weight: 700;
        white-space: nowrap;
    }

    .teacher-list {
        min-width: 120px;
        overflow-wrap: anywhere;
    }

    .teacher-unset {
        display: inline-block;
        padding: 4px 9px;
        border-radius: 6px;
        background: var(--color-bg-unset);
        color: var(--color-text-unset);
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .subject-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
    }

    .subject-edit-button,
    .subject-delete-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 36px;
        padding: 8px 13px;
        border-radius: 7px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        transition: opacity 0.2s;
    }

    .subject-edit-button {
        background: var(--color-bg-edit);
        color: var(--color-font);
        border: 1px solid var(--color-border-edit);
    }

    .subject-delete-button {
        background: var(--color-bg);
        color: var(--color-text-delete);
        border: 1px solid var(--color-border-delete);
    }

    .subject-edit-button:hover,
    .subject-delete-button:hover {
        opacity: 0.7;
    }

    .subjects-page button:focus-visible {
        outline: 3px solid var(--color-accent);
        outline-offset: 3px;
    }

    .subjects-note {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        margin-top: 20px;
        padding: 18px 20px;
        border-radius: 12px;
        background: var(--color-sub);
        color: var(--color-font-light);
        font-size: 13px;
        line-height: 1.8;
    }

    .subjects-note-icon {
        flex-shrink: 0;
        color: var(--color-main);
        font-weight: 700;
    }

    .subjects-note p {
        margin: 0;
    }

    @media (max-width: 768px) {
        .subjects-page {
            padding: 20px 14px;
        }

        .subjects-header {
            align-items: stretch;
            flex-direction: column;
            gap: 16px;
        }

        .subjects-heading {
            font-size: 24px;
        }

        .subject-add-button {
            width: 100%;
        }

        .subjects-card {
            border-radius: 12px;
        }

        .subjects-card-header {
            padding: 18px 16px;
        }

        .subjects-table {
            min-width: 760px;
        }

        .subjects-table th,
        .subjects-table td {
            padding: 15px 14px;
        }

        .subjects-note {
            padding: 16px;
        }
    }
</style>
@endpush
@section('content')

<main class="main">
    <div class="content-wrapper">

        <header class="subjects-header">
            <div>
                <h1 class="subjects-heading">授業一覧</h1>
                <p class="subjects-description">
                    登録されている授業や担当教師、受講生徒数を確認できます。
                </p>
            </div>
            <a class="subject-add-button" href="{{ route('admin.subjects.create') }}">＋ 授業を追加</a>
        </header>

        <section class="subjects-card" aria-label="授業一覧">

            <div class="subjects-card-header">
                <h2 class="subjects-card-title">登録済みの授業</h2>
                <span class="subjects-count">全 {{ \App\Models\Subject::count() }} 件</span>
            </div>

            <div class="subjects-table-wrapper">
                <table class="subjects-table">
                    <thead>
                        <tr>
                            <th scope="col">曜日・時限</th>
                            <th scope="col">授業名</th>
                            <th scope="col">受講生徒数</th>
                            <th scope="col">担当の教師</th>
                            <th scope="col">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($subjects as $subject)
                            <tr>
                                <td><span class="schedule-label">{{ $subject->schedule_label }}</span></td>
                                <td><span class="subject-name">{{ $subject->name }}</span></td>
                                <td><span class="student-count">{{ $subject->students_count }}人</span></td>
                                <td>
                                    @forelse ($subject->teachers as $teacher)
                                        <div class="teacher-list">{{ $teacher->name }}@if (! $loop->last)、@endif</div>
                                    @empty
                                        <span>未設定</span>
                                    @endforelse
                                </td>
                                <td>
                                    <div class="subject-actions">
                                        <a class="subject-edit-button" href="{{ route('admin.subjects.edit', $subject) }}">編集</a>
                                        <form method="POST" action="{{ route('admin.subjects.destroy', $subject) }}" class="inline"
                                            onsubmit="return confirm('この授業を削除しますか？受講・担当の設定も外れます。')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="subject-delete-button">削除</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5">授業が登録されていません。</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <aside class="subjects-note">
            <span class="subjects-note-icon" aria-hidden="true">ⓘ</span>
            <p>
                担当の教師は「教師」の画面、受講する生徒は「生徒」の画面で設定します。
                同じ授業が週に複数回ある場合は、曜日・時限ごとに別々に登録してください。
            </p>
        </aside>
    </div>
</main>
@endsection

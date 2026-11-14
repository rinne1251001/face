
@extends('layouts.test')

@section('content')

<style>
.schedule-page {
    padding: 28px;
    background: #f5f7fb;
    min-height: 100vh;
    color: #253247;
    font-family: "Noto Sans JP", sans-serif;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 26px;
    flex-wrap: wrap;
    gap: 16px;
}

.page-title {
    font-size: 25px;
    font-weight: 700;
    margin: 0;
}

.page-description {
    color: #8490a3;
    font-size: 13px;
    margin-top: 7px;
}

.year-label {
    background: #eaf1ff;
    color: #3866bd;
    padding: 9px 15px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 13px;
}

.schedule-card {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e9edf3;
    padding: 25px;
    margin-bottom: 22px;
}

.section-heading {
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-heading::before {
    content: "";
    width: 4px;
    height: 19px;
    background: #527de5;
    border-radius: 5px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.form-group {
    margin-bottom: 18px;
}

.form-group.full {
    grid-column: 1 / -1;
}

.form-label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 8px;
    color: #45536a;
}

.required {
    color: #e15b63;
    font-size: 11px;
    margin-left: 5px;
}

.form-control {
    width: 100%;
    height: 43px;
    padding: 0 12px;
    border: 1px solid #dfe5ee;
    border-radius: 7px;
    background: #fff;
    color: #344054;
    font-size: 13px;
    box-sizing: border-box;
}

textarea.form-control {
    height: 95px;
    padding: 12px;
    resize: vertical;
}

.form-control:focus {
    outline: none;
    border-color: #6287e9;
    box-shadow: 0 0 0 3px #6287e91a;
}

.adjustment-types {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
}

.type-option {
    position: relative;
}

.type-option input {
    position: absolute;
    opacity: 0;
}

.type-card {
    border: 1px solid #e0e6ef;
    border-radius: 10px;
    padding: 18px 12px;
    cursor: pointer;
    transition: .2s;
    text-align: center;
    height: 100%;
    box-sizing: border-box;
}

.type-card:hover {
    border-color: #7c9aec;
    background: #f8faff;
}

.type-option input:checked + .type-card {
    border: 2px solid #527de5;
    background: #f1f5ff;
}

.type-icon {
    font-size: 25px;
    margin-bottom: 9px;
}

.type-name {
    font-size: 14px;
    font-weight: 700;
    margin-bottom: 5px;
}

.type-description {
    font-size: 11px;
    color: #8994a6;
}

.adjustment-panel {
    display: none;
    margin-top: 22px;
    padding: 20px;
    background: #f8faff;
    border: 1px solid #e7edf8;
    border-radius: 9px;
}

.adjustment-panel.active {
    display: block;
}

.panel-title {
    font-weight: 700;
    font-size: 14px;
    margin-bottom: 17px;
}

.date-period-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.info-box {
    padding: 13px 15px;
    border-radius: 7px;
    font-size: 12px;
    line-height: 1.8;
    margin-bottom: 18px;
}

.info-blue {
    background: #edf4ff;
    color: #4267a8;
}

.info-orange {
    background: #fff6e9;
    color: #a16b24;
}

.preview-card {
    background: #f8faff;
    border: 1px solid #e6ebf5;
    border-radius: 9px;
    padding: 18px;
}

.preview-title {
    font-size: 13px;
    font-weight: 700;
    margin-bottom: 14px;
}

.preview-flow {
    display: flex;
    align-items: center;
    gap: 12px;
}

.preview-item {
    flex: 1;
    background: #fff;
    border: 1px solid #e1e7f0;
    border-radius: 8px;
    padding: 13px;
    min-width: 0;
}

.preview-item small {
    display: block;
    font-size: 11px;
    color: #8a95a7;
    margin-bottom: 7px;
}

.preview-item strong {
    font-size: 13px;
    display: block;
    overflow-wrap: anywhere;
}

.preview-arrow {
    color: #6885d1;
    font-size: 22px;
}

.action-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 18px 0;
}

.btn {
    border: none;
    border-radius: 7px;
    padding: 12px 22px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
}

.btn-cancel {
    background: #fff;
    border: 1px solid #dfe5ee;
    color: #566174;
}

.btn-draft {
    background: #edf1f7;
    color: #4e5e76;
}

.btn-primary {
    background: #527de5;
    color: #fff;
}

.btn-primary:hover {
    background: #416bd0;
}

@media(max-width: 700px) {
    .schedule-page {
        padding: 15px;
    }

    .form-grid,
    .date-period-grid {
        grid-template-columns: 1fr;
    }

    .adjustment-types {
        grid-template-columns: 1fr;
    }

    .preview-flow {
        flex-direction: column;
    }

    .preview-arrow {
        transform: rotate(90deg);
    }

    .preview-item {
        width: 100%;
        box-sizing: border-box;
    }
}
</style>

<div class="schedule-page">

    <!-- ヘッダー -->
    <div class="page-header">
        <div>
            <h1 class="page-title">スケジュール調整</h1>
            <p class="page-description">
                休校・振替授業・授業の入れ替えなど、学校のスケジュールを調整します。
            </p>
        </div>

        <div class="year-label">
            2026年度
        </div>
    </div>

    <form id="scheduleForm">

        <!-- 調整の基本情報 -->
        <div class="schedule-card">

            <div class="section-heading">
                調整内容
            </div>

            <div class="form-group">
                <label class="form-label">
                    調整の種類<span class="required">必須</span>
                </label>

                <div class="adjustment-types">

                    <label class="type-option">
                        <input type="radio" name="adjustment_type"
                               value="closure" checked>
                        <div class="type-card">
                            <div class="type-icon">🏫</div>
                            <div class="type-name">休校</div>
                            <div class="type-description">
                                授業を休みにする
                            </div>
                        </div>
                    </label>

                    <label class="type-option">
                        <input type="radio" name="adjustment_type"
                               value="makeup">
                        <div class="type-card">
                            <div class="type-icon">🔄</div>
                            <div class="type-name">振替授業</div>
                            <div class="type-description">
                                別の日に授業を実施する
                            </div>
                        </div>
                    </label>

                    <label class="type-option">
                        <input type="radio" name="adjustment_type"
                               value="swap">
                        <div class="type-card">
                            <div class="type-icon">↔️</div>
                            <div class="type-name">授業入替</div>
                            <div class="type-description">
                                2つの授業を入れ替える
                            </div>
                        </div>
                    </label>

                </div>
            </div>

            <div class="form-grid">

                <div class="form-group">
                    <label class="form-label">
                        調整タイトル<span class="required">必須</span>
                    </label>
                    <input type="text" class="form-control"
                           placeholder="例：学校行事による休校"
                           name="title" required>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        対象クラス<span class="required">必須</span>
                    </label>
                    <select class="form-control" name="class_id">
                        <option value="">クラスを選択してください</option>
                        <option value="1">1年A組</option>
                        <option value="2">1年B組</option>
                        <option value="3">2年A組</option>
                        <option value="4">2年B組</option>
                        <option value="all">全クラス</option>
                    </select>
                </div>

            </div>
        </div>

        <!-- 休校 -->
        <div class="schedule-card adjustment-panel active"
             id="closurePanel">

            <div class="section-heading">
                休校する日時
            </div>

            <div class="info-box info-blue">
                指定した日付・時限の授業を休校として登録します。
                終日休校にする場合は「終日」を選択してください。
            </div>

            <div class="form-grid">

                <div class="form-group">
                    <label class="form-label">休校日</label>
                    <input type="date" class="form-control"
                           name="closure_date">
                </div>

                <div class="form-group">
                    <label class="form-label">休校範囲</label>
                    <select class="form-control" id="closureRange"
                            name="closure_range">
                        <option value="all">終日</option>
                        <option value="period">特定の時限のみ</option>
                    </select>
                </div>

            </div>

            <div class="form-group" id="closurePeriodGroup"
                 style="display:none;">
                <label class="form-label">対象時限</label>
                <select class="form-control" name="closure_period">
                    <option value="">時限を選択</option>
                    <option value="1">1時限目</option>
                    <option value="2">2時限目</option>
                    <option value="3">3時限目</option>
                    <option value="4">4時限目</option>
                    <option value="5">5時限目</option>
                    <option value="6">6時限目</option>
                </select>
            </div>

        </div>

        <!-- 振替授業 -->
        <div class="schedule-card adjustment-panel"
             id="makeupPanel">

            <div class="section-heading">
                振替授業の設定
            </div>

            <div class="info-box info-blue">
                元の授業と振替先の日付・時限を指定してください。
            </div>

            <div class="panel-title">① 振替元の授業</div>

            <div class="date-period-grid">

                <div class="form-group">
                    <label class="form-label">元の授業日</label>
                    <input type="date" class="form-control"
                           name="makeup_source_date">
                </div>

                <div class="form-group">
                    <label class="form-label">元の時限</label>
                    <select class="form-control"
                            name="makeup_source_period">
                        <option value="">選択してください</option>
                        <option value="1">1時限目</option>
                        <option value="2">2時限目</option>
                        <option value="3">3時限目</option>
                        <option value="4">4時限目</option>
                        <option value="5">5時限目</option>
                        <option value="6">6時限目</option>
                    </select>
                </div>

            </div>

            <div class="panel-title">② 振替先の授業</div>

            <div class="date-period-grid">

                <div class="form-group">
                    <label class="form-label">振替先の日付</label>
                    <input type="date" class="form-control"
                           name="makeup_target_date">
                </div>

                <div class="form-group">
                    <label class="form-label">振替先の時限</label>
                    <select class="form-control"
                            name="makeup_target_period">
                        <option value="">選択してください</option>
                        <option value="1">1時限目</option>
                        <option value="2">2時限目</option>
                        <option value="3">3時限目</option>
                        <option value="4">4時限目</option>
                        <option value="5">5時限目</option>
                        <option value="6">6時限目</option>
                    </select>
                </div>

            </div>

        </div>

        <!-- 授業入替 -->
        <div class="schedule-card adjustment-panel"
             id="swapPanel">

            <div class="section-heading">
                授業の入れ替え設定
            </div>

            <div class="info-box info-orange">
                2つの授業の日時を入れ替えます。
                入れ替え後の時間割を確認してから登録してください。
            </div>

            <div class="panel-title">① 入れ替える授業 A</div>

            <div class="date-period-grid">

                <div class="form-group">
                    <label class="form-label">授業日</label>
                    <input type="date" class="form-control"
                           name="swap_date_a">
                </div>

                <div class="form-group">
                    <label class="form-label">時限</label>
                    <select class="form-control" name="swap_period_a">
                        <option value="">選択してください</option>
                        <option value="1">1時限目</option>
                        <option value="2">2時限目</option>
                        <option value="3">3時限目</option>
                        <option value="4">4時限目</option>
                        <option value="5">5時限目</option>
                        <option value="6">6時限目</option>
                    </select>
                </div>

            </div>

            <div class="panel-title">② 入れ替える授業 B</div>

            <div class="date-period-grid">

                <div class="form-group">
                    <label class="form-label">授業日</label>
                    <input type="date" class="form-control"
                           name="swap_date_b">
                </div>

                <div class="form-group">
                    <label class="form-label">時限</label>
                    <select class="form-control" name="swap_period_b">
                        <option value="">選択してください</option>
                        <option value="1">1時限目</option>
                        <option value="2">2時限目</option>
                        <option value="3">3時限目</option>
                        <option value="4">4時限目</option>
                        <option value="5">5時限目</option>
                        <option value="6">6時限目</option>
                    </select>
                </div>

            </div>

        </div>

        <!-- 調整理由 -->
        <div class="schedule-card">

            <div class="section-heading">
                調整理由・備考
            </div>

            <div class="form-group">
                <label class="form-label">
                    調整理由<span class="required">必須</span>
                </label>
                <textarea class="form-control"
                          name="reason"
                          placeholder="例：学校行事の実施に伴う授業日の変更"
                          required></textarea>
            </div>

            <div class="form-grid">

                <div class="form-group">
                    <label class="form-label">登録日</label>
                    <input type="date" class="form-control"
                           name="created_date" id="createdDate">
                </div>

                <div class="form-group">
                    <label class="form-label">適用状態</label>
                    <select class="form-control" name="status">
                        <option value="draft">下書き</option>
                        <option value="applied">適用する</option>
                    </select>
                </div>

            </div>

        </div>

        <!-- 変更内容プレビュー -->
        <div class="schedule-card">

            <div class="section-heading">
                調整内容の確認
            </div>

            <div class="preview-card">
                <div class="preview-title">
                    変更内容の概要
                </div>

                <div class="preview-flow">
                    <div class="preview-item">
                        <small>調整の種類</small>
                        <strong id="previewType">休校</strong>
                    </div>

                    <div class="preview-arrow">→</div>

                    <div class="preview-item">
                        <small>対象クラス</small>
                        <strong id="previewClass">未選択</strong>
                    </div>
                </div>

                <p style="font-size:12px;color:#8994a6;margin-top:14px;">
                    ※ 実際の授業内容や変更後の時間割は、登録前に確認してください。
                </p>
            </div>

        </div>

        <!-- ボタン -->
        <div class="action-footer">
            <button type="button" class="btn btn-cancel"
                    onclick="history.back()">
                キャンセル
            </button>

            <button type="button" class="btn btn-draft"
                    onclick="saveAsDraft()">
                下書き保存
            </button>

            <button type="submit" class="btn btn-primary">
                調整内容を登録
            </button>
        </div>

    </form>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const typeInputs = document.querySelectorAll(
        'input[name="adjustment_type"]'
    );

    const panels = {
        closure: document.getElementById('closurePanel'),
        makeup: document.getElementById('makeupPanel'),
        swap: document.getElementById('swapPanel')
    };

    const typeNames = {
        closure: '休校',
        makeup: '振替授業',
        swap: '授業入替'
    };

    function updateType() {
        const selected = document.querySelector(
            'input[name="adjustment_type"]:checked'
        ).value;

        Object.keys(panels).forEach(key => {
            panels[key].classList.toggle(
                'active', key === selected
            );
        });

        document.getElementById('previewType').textContent =
            typeNames[selected];
    }

    typeInputs.forEach(input => {
        input.addEventListener('change', updateType);
    });

    // 休校範囲の切り替え
    document.getElementById('closureRange')
        .addEventListener('change', function () {

            document.getElementById(
                'closurePeriodGroup'
            ).style.display =
                this.value === 'period' ? 'block' : 'none';
        });

    // クラスのプレビュー
    document.querySelector('[name="class_id"]')
        .addEventListener('change', function () {

            const selectedText =
                this.options[this.selectedIndex].text;

            document.getElementById('previewClass')
                .textContent = this.value
                    ? selectedText
                    : '未選択';
        });

    // 登録日を本日に設定
    document.getElementById('createdDate').value =
        new Date().toISOString().split('T')[0];

    // 登録処理（画面確認用）
    document.getElementById('scheduleForm')
        .addEventListener('submit', function (e) {

            e.preventDefault();

            if (!this.reportValidity()) return;

            if (!confirm('この調整内容を登録しますか？')) {
                return;
            }

            alert('画面サンプルのため、実際の登録処理は行われません。');
        });

    updateType();
});

// 下書き保存（画面確認用）
function saveAsDraft() {
    const form = document.getElementById('scheduleForm');

    const status = form.querySelector('[name="status"]');
    status.value = 'draft';

    alert('画面サンプルのため、実際の保存処理は行われません。');
}
</script>

@endsection

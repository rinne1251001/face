@extends('layouts.test')
@section('title', '生徒追加')

@push('styles')
<style>
:root {
    --color-danger: #ef4444;
}

/* レイアウト・カード */
.form-container { max-width: 680px; margin: 30px auto; padding: 0 20px; font-family: sans-serif; color: var(--color-font); }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.page-title { font-size: 24px; font-weight: bold; margin: 0; }
.btn-back { color: var(--color-accent); text-decoration: none; font-size: 14px; }
.btn-back:hover { color: var(--color-font); }
.form-card { background: var(--color-bg); border: 1px solid var(--color-font-light); border-radius: 8px; padding: 28px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }

/* フォームパーツ */
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.form-group { margin-bottom: 20px; }
.form-label { display: block; font-size: 14px; font-weight: 600; color: var(--color-main); margin-bottom: 6px; }
.required-badge { color: var(--color-danger); font-size: 12px; margin-left: 4px; }
.form-input, .form-select { width: 100%; padding: 10px 12px; border: 1px solid var(--color-font-light); border-radius: 6px; font-size: 14px; outline: none; box-sizing: border-box; transition: border-color 0.2s; }
.form-input:focus, .form-select:focus { border-color: var(--color-main); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }

/* チェックボックス・カード */
.checkbox-card { background: var(--color-sub); border: 1px solid var(--color-font-light); border-radius: 6px; padding: 14px; }
.checkbox-item { display: flex; align-items: center; gap: 8px; font-size: 14px; cursor: pointer; margin-bottom: 8px; }
.checkbox-item:last-child { margin-bottom: 0; }
.checkbox-item input[type="checkbox"] { width: 16px; height: 16px; accent-color: var(--color-main); cursor: pointer; }

/* ボタン */
.form-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 28px; padding-top: 20px; border-top: 1px solid var(--color-font-light); }
.btn-primary, .btn-secondary { padding: 10px 18px; border-radius: 6px; font-size: 14px; font-weight: bold; cursor: pointer; }
.btn-primary { background: var(--color-main); color: var(--color-bg); border: none; }
.btn-primary:hover { background: var(--color-accent); }
.btn-secondary { background: var(--color-bg); color: var(--color-main); border: 1px solid var(--color-font-light); }
.btn-secondary:hover { background: var(--color-sub); }
</style>
@endpush

@section('content')
<main>
    <div class="form-container">
        <div class="page-header">
            <h1 class="page-title">生徒の追加</h1>
            <a href="#" class="btn-back">← 一覧に戻る</a>
        </div>

        <div class="form-card">
            <form action="#" method="POST">
                @csrf
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">名前 <span class="required-badge">*</span></label>
                        <input type="text" class="form-input" placeholder="例: 山田 太郎">
                    </div>
                    <div class="form-group">
                        <label class="form-label">学籍番号 <span class="required-badge">*</span></label>
                        <input type="text" class="form-input" placeholder="例: S0001">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">クラス <span class="required-badge">*</span></label>
                    <select class="form-select">
                        <option value="">選択してください</option>
                        <option value="1A">1年A組</option>
                        <option value="1B">1年B組</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">受講する授業（1つ以上）</label>
                    <div class="checkbox-card">
                        <label class="checkbox-item">
                            <input type="checkbox" name="subject[]" value="math">
                            <span>数学（月曜 1コマ）</span>
                        </label>
                        <label class="checkbox-item">
                            <input type="checkbox" name="subject[]" value="info">
                            <span>情報実習（水曜 3~5コマ）</span>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label class="checkbox-item">
                        <input type="checkbox" name="is_target" checked>
                        <span style="font-weight: 600;">照会の対象にする</span>
                    </label>
                </div>

                <div class="form-actions">
                    <button type="submit" name="action" value="save_only" class="btn-secondary">保存のみ</button>
                    <button type="submit" name="action" value="save_and_face" class="btn-primary">保存して顔画像を登録</button>
                </div>
            </form>
        </div>
    </div>
</main>
@endsection
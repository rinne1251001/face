@extends('layouts.app')
@section('title', '生徒を一斉に追加')

@section('content')
<style>
    .bulk-table td { padding: 2px; vertical-align: middle; }
    .bulk-table input, .bulk-table select { width: 100%; box-sizing: border-box; }
    .bulk-table .row-no { width: 3em; text-align: right; color: #6b7280; }
    .bulk-table .invalid { border-color: #b91c1c; background: #fef2f2; }
</style>

<main class="main">
    <div class="content-wrapper">
        <h1>生徒を一斉に追加</h1>

        @if ($classes->isEmpty())
            <p class="warn">先に<a href="{{ route('admin.classes.index') }}">クラス</a>を1つ以上登録してください。</p>
        @endif

        <p class="muted">
            Excel で「名前・学籍番号・クラス名」の順に並んだ範囲をコピーし、表の「名前」のマスを選んで Ctrl+V で貼り付けると一気に入力できます。<br>
            名前と学籍番号が両方とも空の行は無視されます。1人でもエラーがあると、誰も登録されません。
        </p>

        @if ($errors->any())
            <ul class="error">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        @endif

        @php
            // エラーで戻ってきたときは入力していた行を、最初は空の行を10行出す
            $rows = old('students') ?: array_fill(0, 10, []);
            $oldSubjectIds = array_map('intval', old('subject_ids', []));
        @endphp

        <form method="POST" action="{{ route('admin.students.bulk.store') }}">
            @csrf

            <p>
                <label>クラスをまとめて設定
                    <select id="bulk-class">
                        <option value="">選択してください</option>
                        @foreach ($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->name }}</option>
                        @endforeach
                    </select>
                </label>
                <button type="button" id="apply-class">全行に反映</button>
            </p>

            <table class="bulk-table">
                <thead>
                    <tr>
                        <th></th>
                        <th>名前</th>
                        <th>学籍番号</th>
                        <th>クラス</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="rows">
                    @foreach ($rows as $i => $row)
                        <tr>
                            <td class="row-no">{{ $loop->iteration }}</td>
                            <td>
                                <input name="students[{{ $i }}][name]" value="{{ $row['name'] ?? '' }}" maxlength="100"
                                    @class(['invalid' => $errors->has("students.$i.name")])>
                            </td>
                            <td>
                                <input name="students[{{ $i }}][student_number]" value="{{ $row['student_number'] ?? '' }}" maxlength="20"
                                    @class(['invalid' => $errors->has("students.$i.student_number")])>
                            </td>
                            <td>
                                <select name="students[{{ $i }}][school_class_id]"
                                        @class(['invalid' => $errors->has("students.$i.school_class_id")])>
                                    <option value="">選択してください</option>
                                    @foreach ($classes as $class)
                                        <option value="{{ $class->id }}" @selected(($row['school_class_id'] ?? '') == $class->id)>{{ $class->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td><button type="button" class="remove-row" title="この行を削除">×</button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p><button type="button" id="add-rows">＋ 10行追加</button></p>

            <fieldset>
                <legend>受講する授業（全員共通・空でも可）</legend>
                @forelse ($subjects as $subject)
                    <label style="display:block">
                        <input type="checkbox" name="subject_ids[]" value="{{ $subject->id }}"
                            @checked(in_array($subject->id, $oldSubjectIds, true))>
                        {{ $subject->name }}（{{ $subject->schedule_label }}）
                    </label>
                @empty
                    <p>授業が登録されていません。</p>
                @endforelse
            </fieldset>

            <p><label><input type="checkbox" name="is_active" value="1" @checked(session()->hasOldInput() ? old('is_active') : true)> 照合の対象にする</label></p>

            <button>まとめて保存</button>
        </form>
    </div>
</main>
@endsection

@push('scripts')
<script>
(() => {
    const MAX_ROWS = 200;
    const COLS = ['name', 'student_number', 'school_class_id']; // 貼り付けるときの列の順番
    const tbody = document.getElementById('rows');

    // name="students[3][name]" → "name"
    const fieldOf = (el) => el.name.match(/\[(\w+)\]$/)[1];
    const cellOf = (tr, field) => tr.querySelector(`[name$="[${field}]"]`);

    // 行番号と name の番号を上から 0, 1, 2… に振り直す
    function renumber() {
        [...tbody.rows].forEach((tr, i) => {
            tr.querySelector('.row-no').textContent = i + 1;
            tr.querySelectorAll('[name]').forEach((el) => {
                el.name = el.name.replace(/^students\[\d+\]/, `students[${i}]`);
            });
        });
    }

    // 空の行を1行増やして、その行を返す（1行目をコピーして中身を消す）
    function addRow() {
        if (tbody.rows.length >= MAX_ROWS) return null;
        const tr = tbody.rows[0].cloneNode(true);
        tr.querySelectorAll('input, select').forEach((el) => {
            el.value = '';
            el.classList.remove('invalid');
        });
        tbody.appendChild(tr);
        renumber();
        return tr;
    }

    // クラス名の文字からクラスを選ぶ（全角・半角や空白の違いは無視）
    const norm = (s) => s.normalize('NFKC').replace(/\s+/g, '').toLowerCase();
    function setClass(select, text) {
        const option = [...select.options].find((o) => o.value && norm(o.text) === norm(text));
        select.value = option ? option.value : '';
        select.classList.toggle('invalid', !option && text.trim() !== ''); // 見つからないクラス名は赤くする
    }

    // Excel からの貼り付け（行はタブ区切り、行と行は改行区切り）
    tbody.addEventListener('paste', (e) => {
        if (!e.target.matches('input, select')) return;
        const text = e.clipboardData.getData('text');
        const lines = text.replace(/\r\n?/g, '\n').replace(/\n$/, '').split('\n');
        if (lines.length === 1 && !lines[0].includes('\t')) return; // 1マスだけならふつうに貼り付け
        e.preventDefault();

        if (/名前|学籍番号/.test(lines[0])) lines.shift(); // 見出しの行もコピーしていたら飛ばす

        const startRow = e.target.closest('tr').sectionRowIndex;
        const startCol = COLS.indexOf(fieldOf(e.target));
        let overflow = 0;

        lines.forEach((line, r) => {
            const tr = tbody.rows[startRow + r] || addRow();
            if (!tr) { overflow++; return; }
            line.split('\t').forEach((value, c) => {
                const field = COLS[startCol + c];
                if (!field) return; // 表より右の列は無視
                const el = cellOf(tr, field);
                if (field === 'school_class_id') {
                    setClass(el, value);
                } else {
                    el.value = value.trim();
                    el.classList.remove('invalid');
                }
            });
        });

        if (overflow) alert(`一度に追加できるのは${MAX_ROWS}行までです。${overflow}行は貼り付けられませんでした。`);
    });

    // Enter・↓ で下の行、↑ で上の行へ（Enter で送信されないようにする）
    tbody.addEventListener('keydown', (e) => {
        if (!['Enter', 'ArrowDown', 'ArrowUp'].includes(e.key)) return;
        if (e.isComposing || e.keyCode === 229) return; // 日本語入力の変換中は何もしない
        if (e.target.tagName === 'SELECT' && e.key !== 'Enter') return; // セレクトの↑↓は選択肢の切り替えに使う
        e.preventDefault();
        const tr = e.target.closest('tr');
        const next = e.key === 'ArrowUp'
            ? tr.previousElementSibling
            : tr.nextElementSibling || (e.key === 'Enter' ? addRow() : null);
        next && cellOf(next, fieldOf(e.target)).focus();
    });

    // 行の削除（最後の1行は消さずに中身だけ消す）
    tbody.addEventListener('click', (e) => {
        if (!e.target.closest('.remove-row')) return;
        const tr = e.target.closest('tr');
        if (tbody.rows.length === 1) {
            tr.querySelectorAll('input, select').forEach((el) => { el.value = ''; });
        } else {
            tr.remove();
            renumber();
        }
    });

    document.getElementById('add-rows').addEventListener('click', () => {
        for (let i = 0; i < 10; i++) addRow();
    });

    document.getElementById('apply-class').addEventListener('click', () => {
        const value = document.getElementById('bulk-class').value;
        tbody.querySelectorAll('select').forEach((s) => {
            s.value = value;
            s.classList.remove('invalid');
        });
    });
})();
</script>
@endpush
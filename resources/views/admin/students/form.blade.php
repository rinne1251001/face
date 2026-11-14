@extends('layouts.test')
@section('title', ($student->exists ? '生徒の編集' : '生徒の追加').'（'.$year->label.'）')
@push('styles')
<style>
:root {
    --color-danger: #ef4444;
}

/* レイアウト・カード */
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

.delete:hover {
    color: var(--color-accent);
}
/* 読み取り専用の年度では、入力欄をまとめて押せなくする（fieldset の disabled） */
.form-fieldset { border: none; margin: 0; padding: 0; min-width: 0; }
.optional-note { color: var(--color-font-light); font-size: 12px; margin-left: 4px; font-weight: normal; }
</style>
@endpush

@section('content')
<main class="main">
    <div class="content-wrapper">
        <div class="page-header">
            <h1>{{ $student->exists ? '生徒の編集' : '生徒の追加' }}（{{ $year->label }}）</h1>
            <a href="{{ route('admin.students.index', ['year' => $year->year]) }}" class="btn-back">← 一覧に戻る</a>
        </div>

        @include('admin.partials.year-lock-notice', ['year' => $year])

        @php
            $editable = $year->isEditable(); // false なら見るだけ（入力欄も保存ボタンも使えない）
        @endphp

        <div class="form-card">
            {{-- メインフォーム（登録・更新用） --}}
            <form id="student-form" method="POST" action="{{ $student->exists ? route('admin.students.update', $student) : route('admin.students.store') }}">
                @csrf
                @if ($student->exists)
                    @method('PUT')
                @endif
                {{-- どの年度の在籍・授業を保存するか --}}
                <input type="hidden" name="year" value="{{ $year->year }}">

                <fieldset class="form-fieldset" @disabled(! $editable)>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="name">名前 <span class="required-badge">*</span></label>
                            <input id="name" name="name" value="{{ old('name', $student->name) }}" type="text" class="form-input" placeholder="例: 山田太郎" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="furigana">フリガナ <span class="required-badge">*</span></label>
                            <input id="furigana" name="furigana" value="{{ old('furigana', $student->furigana) }}" type="text" class="form-input" placeholder="例: ヤマダタロウ" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="student_number">学籍番号 <span class="required-badge">*</span></label>
                            <input id="student_number" name="student_number" value="{{ old('student_number', $student->student_number) }}" type="text" class="form-input" placeholder="例: 0000000" required>
                        </div>
                        <div class="form-group">
                            {{-- 何年で卒業するかは学科で決まる --}}
                            <label class="form-label" for="department_id">学科 <span class="required-badge">*</span></label>
                            <select id="department_id" name="department_id" class="form-select" required>
                                <option value="">選択してください</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}" @selected(old('department_id', $student->department_id) == $department->id)>
                                        {{ $department->name }}（{{ $department->years }}年制）
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- ここから下は、この年度だけの内容（年度が変わると、別の値になる） --}}
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="grade">学年 <span class="required-badge">*</span></label>
                            <select id="grade" name="grade" class="form-select" required>
                                @for ($g = 1; $g <= $maxGrade; $g++)
                                    <option value="{{ $g }}" @selected((int) old('grade', $enrollment->grade) === $g)>{{ $g }}年</option>
                                @endfor
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="school_class_id">クラス <span class="optional-note">決まっていなければ「未定」</span></label>
                            <select id="school_class_id" name="school_class_id" class="form-select">
                                <option value="">未定</option>
                                @foreach ($classes as $schoolClass)
                                    <option value="{{ $schoolClass->id }}" @selected(old('school_class_id', $enrollment->school_class_id) == $schoolClass->id)>
                                        {{ $schoolClass->name }}（{{ $schoolClass->grade }}年）
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="attend_number">出席番号 <span class="optional-note">クラスが決まってから入力</span></label>
                            <input id="attend_number" name="attend_number" value="{{ old('attend_number', $enrollment->attend_number) }}" type="text" class="form-input" placeholder="例: 14">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">受講する授業（{{ $year->label }}・未定なら空のまま）</label>
                        <div class="checkbox-card">
                            @forelse ($subjects as $subject)
                                <label class="checkbox-item">
                                    <input type="checkbox" name="subject_ids[]" value="{{ $subject->id }}"
                                        @checked(in_array($subject->id, $selectedSubjectIds, true))>
                                    {{ $subject->name }}（{{ $subject->schedule_label }}）
                                </label>
                            @empty
                                <p>{{ $year->label }}の授業が登録されていません。</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-item">
                            <input type="checkbox" name="is_active" value="1" @checked(session()->hasOldInput() ? old('is_active') : $student->is_active)>
                            <span style="font-weight: 600;">照合の対象にする</span>
                        </label>
                    </div>
                </fieldset>

                @if ($errors->any())
                    <ul class="error">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                @endif

                {{-- 顔写真は年度に関係ないので、読み取り専用の年度でも登録・削除できる --}}
                @if ($student->exists)
                    <label class="form-label">登録済みの顔写真（{{ $student->faceSamples->count() }}枚）</label>
                    <div><a class="btn-secondary" href="{{ route('admin.faces.create', $student) }}">カメラで顔写真を登録する</a></div>
                    <div class="thumbs">
                        @foreach ($student->faceSamples as $sample)
                            <figure style="display:inline-block; margin:0">
                                <img src="{{ route('admin.faces.image', $sample) }}" alt="">
                                {{-- form属性で外側のフォームと紐付け --}}
                                <button type="submit" form="delete-face-{{ $sample->id }}" class="delete" onclick="return confirm('この写真を削除しますか？')">削除</button>
                            </figure>
                        @endforeach
                    </div>
                @endif

                <div class="form-actions">
                    @if (! $editable)
                        <span>読み取り専用のため、変更できません。</span>
                    @elseif ($student->exists)
                        {{-- form属性で外側の削除フォームと紐付け --}}
                        <button type="submit" form="delete-student-form" class="btn-secondary">この生徒を削除</button>
                        <button type="submit" class="btn-primary">保存</button>
                    @else
                        {{-- どちらのボタンを押したかは name="next" の値で判別する --}}
                        <button name="next" value="faces" type="submit" class="btn-secondary">保存して顔写真を登録する</button>
                        <button name="next" value="list" type="submit" class="btn-secondary">保存のみ（顔写真は後で登録する）</button>
                    @endif
                </div>
            </form>

            {{-- 外部削除用フォーム群（ネスト回避のためにメインフォームの外へ配置） --}}
            @if ($student->exists)
                {{-- 生徒の削除用フォーム（year は、削除したあとに戻る一覧の年度） --}}
                @if ($editable)
                    <form id="delete-student-form" method="POST" action="{{ route('admin.students.destroy', [$student, 'year' => $year->year]) }}" onsubmit="return confirm('この生徒の顔写真・在籍・出席記録もすべて削除されます。削除しますか？')">
                        @csrf
                        @method('DELETE')
                    </form>
                @endif

                {{-- 顔写真の削除用フォーム --}}
                @foreach ($student->faceSamples as $sample)
                    <form id="delete-face-{{ $sample->id }}" method="POST" action="{{ route('admin.faces.destroy', $sample) }}">
                        @csrf
                        @method('DELETE')
                    </form>
                @endforeach
            @endif

        </div>
    </div>
</main>
@endsection
@extends('layouts.test')
@section('title', $student->exists ? '生徒の編集' : '生徒の追加')
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

.delete:hover {
    color: var(--color-accent);
}
</style>
@endpush

@section('content')
<main>
    <div class="form-container">
        <div class="page-header">
            <h1 class="page-title">{{ $student->exists ? '生徒の編集' : '生徒の追加' }}</h1>
            <a href="{{ route('admin.students.index') }}" class="btn-back">← 一覧に戻る</a>
        </div>

        <div class="form-card">
            <form method="POST" action="{{ $student->exists ? route('admin.students.update', $student) : route('admin.students.store') }}">
                @csrf
                @if ($student->exists)
                    @method('PUT')
                @endif

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">名前 <span class="required-badge">*</span></label>
                        <input name="name" value="{{ old('name', $student->name) }}" type="text" class="form-input" placeholder="例: 山田太郎" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">学籍番号 <span class="required-badge">*</span></label>
                        <input name="student_number" value="{{ old('student_number', $student->student_number) }}" type="text" class="form-input" placeholder="例: 0000000" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">クラス <span class="required-badge">*</span></label>
                    <select name="school_class_id" class="form-select" required>
                        <option value="">選択してください</option>
                        @foreach ($classes as $schoolClass)
                            <option value="{{ $schoolClass->id }}" @selected(old('school_class_id', $student->school_class_id) == $schoolClass->id)>
                                {{ $schoolClass->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">受講する授業（1つ以上）</label>
                    <div class="checkbox-card">

                    @forelse ($subjects as $subject)
                        <label class="checkbox-item">
                            <input type="checkbox" name="subject_ids[]" value="{{ $subject->id }}"
                                @checked(in_array($subject->id, $selectedSubjectIds, true))>
                            {{ $subject->name }}（{{ $subject->schedule_label }}）
                        </label>
                    @empty
                        <p>授業が登録されていません。</p>
                    @endforelse
                </div>

                <div class="form-group">
                    <label class="checkbox-item">
                        <input type="checkbox" name="is_target" value="1" @checked(session()->hasOldInput() ? old('is_active') : $student->is_active)>
                        <span style="font-weight: 600;">照会の対象にする</span>
                    </label>
                </div>

                @if ($errors->any())
                    <ul class="error">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                @endif

                @if ($student->exists)
                    <label class="form-label">登録済みの顔写真（{{ $student->faceSamples->count() }}枚）</label>
                    <div><a class="btn-secondary" href="{{ route('admin.faces.create', $student) }}">カメラで顔写真を登録する</a></div>
                    <div class="thumbs">
                        @foreach ($student->faceSamples as $sample)
                            <figure style="display:inline-block; margin:0">
                                <img src="{{ route('admin.faces.image', $sample) }}" alt="">
                                <form method="POST" action="{{ route('admin.faces.destroy', $sample) }}" onsubmit="return confirm('この写真を削除しますか？')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="delete">削除</button>
                                </form>
                            </figure>
                        @endforeach
                    </div>
                @endif

                <div class="form-actions">
                    @if ($student->exists)
                        <form method="POST" action="{{ route('admin.students.destroy', $student) }}" onsubmit="return confirm('この生徒の顔写真と出席記録もすべて削除されます。削除しますか？')">
                            @csrf
                            @method('DELETE')
                            <button class="btn-secondary">この生徒を削除</button>
                        </form>
                    @endif

                    @if ($student->exists)
                        <button class="btn-secondary">保存</button>
                    @else
                        {{-- どちらのボタンを押したかは name="next" の値で判別する --}}
                        <button name="next" value="faces" type="submit" class="btn-secondary">保存して顔写真を登録する</button>
                        <button name="next" value="list" type="submit" class="btn-secondary">保存のみ（顔写真は後で登録する）</button>
                    @endif
                </div>
            </form>

        </div>
   </div>
</main>
@endsection

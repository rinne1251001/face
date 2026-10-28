@extends('layouts.app')
@section('title', $student->exists ? '生徒の編集' : '生徒の追加')

@section('content')
<h1>{{ $student->exists ? '生徒の編集' : '生徒の追加' }}</h1>

@if ($classes->isEmpty() || $subjects->isEmpty())
    <p class="warn">
        生徒を登録する前に、<a href="{{ route('admin.classes.index') }}">クラス</a>と
        <a href="{{ route('admin.subjects.index') }}">授業</a>をそれぞれ1つ以上登録してください。
    </p>
@endif

<form method="POST" action="{{ $student->exists ? route('admin.students.update', $student) : route('admin.students.store') }}">
    @csrf
    @if ($student->exists)
        @method('PUT')
    @endif

    <p><label>名前<br><input name="name" value="{{ old('name', $student->name) }}" required></label></p>
    <p><label>学籍番号<br><input name="student_number" value="{{ old('student_number', $student->student_number) }}" required></label></p>

    <p><label>クラス<br>
        <select name="school_class_id" required>
            <option value="">選択してください</option>
            @foreach ($classes as $schoolClass)
                <option value="{{ $schoolClass->id }}" @selected(old('school_class_id', $student->school_class_id) == $schoolClass->id)>
                    {{ $schoolClass->name }}
                </option>
            @endforeach
        </select>
    </label></p>

    <fieldset>
        <legend>受講する授業（1つ以上）</legend>
        @forelse ($subjects as $subject)
            <label style="display:block">
                <input type="checkbox" name="subject_ids[]" value="{{ $subject->id }}"
                       @checked(in_array($subject->id, $selectedSubjectIds, true))>
                {{ $subject->name }}（{{ $subject->schedule_label }}）
            </label>
        @empty
            <p>授業が登録されていません。</p>
        @endforelse
    </fieldset>

    <p><label><input type="checkbox" name="is_active" value="1" @checked(session()->hasOldInput() ? old('is_active') : $student->is_active)> 照合の対象にする</label></p>

    @if ($errors->any())
        <ul class="error">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    @endif

    @if ($student->exists)
        <button>保存</button>
    @else
        {{-- どちらのボタンを押したかは name="next" の値で判別する --}}
        <button name="next" value="faces">保存して顔写真を登録する</button>
        <button name="next" value="list">保存のみ（顔写真は後で登録する）</button>
    @endif
</form>

@if ($student->exists)
    <h2>登録済みの顔写真（{{ $student->faceSamples->count() }}枚）</h2>
    <p><a href="{{ route('admin.faces.create', $student) }}">カメラで顔写真を登録する</a></p>
    <div class="thumbs">
        @foreach ($student->faceSamples as $sample)
            <figure style="display:inline-block; margin:0">
                <img src="{{ route('admin.faces.image', $sample) }}" alt="">
                <form method="POST" action="{{ route('admin.faces.destroy', $sample) }}" onsubmit="return confirm('この写真を削除しますか？')">
                    @csrf
                    @method('DELETE')
                    <button>削除</button>
                </form>
            </figure>
        @endforeach
    </div>

    <hr>
    <form method="POST" action="{{ route('admin.students.destroy', $student) }}" onsubmit="return confirm('この生徒の顔写真と出席記録もすべて削除されます。削除しますか？')">
        @csrf
        @method('DELETE')
        <button>この生徒を削除</button>
    </form>
@endif
@endsection

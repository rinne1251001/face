@extends('layouts.app')
@section('title', $teacher->exists ? '教師の編集' : '教師の追加')

@section('content')
<h1>{{ $teacher->exists ? '教師の編集' : '教師の追加' }}</h1>

@if ($subjects->isEmpty())
    <p class="warn">教師には担当授業が1つ以上必要です。先に<a href="{{ route('admin.subjects.create') }}">授業を登録</a>してください。</p>
@endif

<form method="POST" action="{{ $teacher->exists ? route('admin.teachers.update', $teacher) : route('admin.teachers.store') }}">
    @csrf
    @if ($teacher->exists)
        @method('PUT')
    @endif

    <p><label>名前<br><input name="name" value="{{ old('name', $teacher->name) }}" required></label></p>
    <p><label>ログインID<br><input name="login_id" value="{{ old('login_id', $teacher->login_id) }}" required></label></p>
    <p><label>パスワード（8文字以上）<br>
        <input type="password" name="password" autocomplete="new-password" @required(! $teacher->exists)>
    </label>
    @if ($teacher->exists)
        <br><span class="muted">変更しない場合は空欄のままにしてください。</span>
    @endif
    </p>

    <fieldset>
        <legend>受け持ちクラス（なしでも可）</legend>
        @forelse ($classes as $schoolClass)
            <label style="display:block">
                <input type="checkbox" name="class_ids[]" value="{{ $schoolClass->id }}"
                       @checked(in_array($schoolClass->id, $selectedClassIds, true))>
                {{ $schoolClass->name }}
            </label>
        @empty
            <p>クラスが登録されていません。</p>
        @endforelse
    </fieldset>

    <fieldset>
        <legend>担当授業（1つ以上）</legend>
        @foreach ($subjects as $subject)
            <label style="display:block">
                <input type="checkbox" name="subject_ids[]" value="{{ $subject->id }}"
                       @checked(in_array($subject->id, $selectedSubjectIds, true))>
                {{ $subject->name }}（{{ $subject->schedule_label }}）
            </label>
        @endforeach
    </fieldset>

    @if ($errors->any())
        <ul class="error">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    @endif
    <button>保存</button>
</form>
<p><a href="{{ route('admin.teachers.index') }}">教師一覧へ戻る</a></p>
@endsection

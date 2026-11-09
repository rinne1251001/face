@extends('layouts.test')
@section('title', $schoolClass->exists ? 'クラスの編集' : 'クラスの追加')

@section('content')
<main class="main">
    <div class="content-wrapper">
        <div class="page-header">
            <h1>{{ $schoolClass->exists ? 'クラスの編集' : 'クラスの追加' }}</h1>
        </div>

        <form method="POST" action="{{ $schoolClass->exists ? route('admin.classes.update', $schoolClass) : route('admin.classes.store') }}">
            @csrf
            @if ($schoolClass->exists)
                @method('PUT')
            @endif
            <p><label>クラス名<br><input name="name" value="{{ old('name', $schoolClass->name) }}" placeholder="例：1T1" required></label></p>
            <p>
                <label>ホームルームの教室<br>
                    <select name="classroom_id">
                        <option value="">未設定</option>
                        @foreach ($classrooms as $classroom)
                            <option value="{{ $classroom->id }}" @selected(old('classroom_id', $schoolClass->classroom_id) == $classroom->id)>
                                {{ $classroom->name }}（{{ $classroom->floor_label }}）
                            </option>
                        @endforeach
                    </select>
                </label>
                @if ($classrooms->isEmpty())
                    <br><small>教室がまだ登録されていません。</small>
                @endif
            </p>
            @if ($errors->any())
                <ul class="error">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            @endif
            <button>保存</button>
        </form>
        <p><a href="{{ route('admin.classes.index') }}">クラス一覧へ戻る</a></p>
    </div>
</main>
@endsection

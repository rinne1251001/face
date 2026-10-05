@extends('layouts.app')
@section('title', $schoolClass->exists ? 'クラスの編集' : 'クラスの追加')

@section('content')
<h1>{{ $schoolClass->exists ? 'クラスの編集' : 'クラスの追加' }}</h1>

<form method="POST" action="{{ $schoolClass->exists ? route('admin.classes.update', $schoolClass) : route('admin.classes.store') }}">
    @csrf
    @if ($schoolClass->exists)
        @method('PUT')
    @endif
    <p><label>クラス名<br><input name="name" value="{{ old('name', $schoolClass->name) }}" placeholder="例：1年A組" required></label></p>
    @if ($errors->any())
        <ul class="error">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    @endif
    <button>保存</button>
</form>
<p><a href="{{ route('admin.classes.index') }}">クラス一覧へ戻る</a></p>
@endsection

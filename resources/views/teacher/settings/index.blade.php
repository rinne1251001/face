@extends('layouts.test')
@section('title', '設定一覧')
@section('content')

    <main>
        <a href="{{ route('teacher.settings.password.edit') }}">パスワード設定</a>
    </main>

@endsection
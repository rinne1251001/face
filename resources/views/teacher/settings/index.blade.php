@extends('layouts.test')
@section('title', '設定一覧')
@section('content')

    <main class="main">
        <div class="content-wrapper">
            <h1>設定一覧</h1>
            <a href="{{ route('teacher.settings.password.edit') }}">パスワード設定</a>
        </div>
    </main>

@endsection
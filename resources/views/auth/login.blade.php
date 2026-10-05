{{-- 管理者・教師で共通のログイン画面（$title と $action はコントローラーから渡す） --}}
@extends('layouts.app')
@section('title', $title)

@section('content')
<h1>{{ $title }}</h1>
<form method="POST" action="{{ $action }}">
    @csrf
    <p><label>ログインID<br><input name="login_id" value="{{ old('login_id') }}" required autofocus></label></p>
    <p><label>パスワード<br><input type="password" name="password" required></label></p>
    <p><label><input type="checkbox" name="remember" value="1"> ログイン状態を保持する</label></p>
    @error('login_id')<p class="error">{{ $message }}</p>@enderror
    <button>ログイン</button>
</form>
<p><a href="{{ $otherLink['url'] }}">{{ $otherLink['label'] }}</a></p>
@endsection

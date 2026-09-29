@extends('layouts.app')
@section('title', 'ログイン')

@section('content')
<h1>管理者ログイン</h1>
<form method="POST" action="{{ url('/login') }}">
    @csrf
    <p><label>ログインID<br><input name="login_id" value="{{ old('login_id') }}" required autofocus></label></p>
    <p><label>パスワード<br><input type="password" name="password" required></label></p>
    <p><label><input type="checkbox" name="remember" value="1"> ログイン状態を保持する</label></p>
    @error('login_id')<p class="error">{{ $message }}</p>@enderror
    <button>ログイン</button>
</form>
@endsection
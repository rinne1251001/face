@extends('layouts.test')
@section('title', 'パスワード変更')
@section('content')

    <main>
        <form method="POST" action="{{ route('teacher.settings.password.update') }}">
            @csrf
            @method('PUT')
            <button>変更する</button>
        </form>
    </main>

@endsection
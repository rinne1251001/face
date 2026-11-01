@extends('layouts.test')
@section('title', 'アクセスエラー')
@section('content')

    <main>
        <p>403 | Forbidden</p>
        <p>{{ $exception->getMessage() }}</p>
    </main>

@endsection
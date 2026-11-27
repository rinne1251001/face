@extends('layouts.app')
@section('title', 'カメラの詳細')
@section('content')
    <main class="main">
        <div class="content-wrapper">
            この画面の教室名は{{ $classroom->name }}
        </div>
    </main>

@endsection
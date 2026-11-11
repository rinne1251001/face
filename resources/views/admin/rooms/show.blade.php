@extends('layouts.test')
@section('title', '教室追加')
@section('content')
    <main class="main">
        <div class="content-wrapper">
            この画面の教室名は{{ $room->name }}
        </div>
    </main>

@endsection
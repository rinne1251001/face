@extends('layouts.test')
@section('title', 'クラス')
@section('content')

    <main class="main">
        <div class="content-wrapper">
            <h2>受け持ちクラス</h2>
            @if ($classes->isEmpty())
                <p class="muted">受け持ちクラスはありません。</p>
            @else
                <ul>
                    @foreach ($classes as $schoolClass)
                        <li>
                            <a href="{{ route('teacher.classes.show', $schoolClass) }}">{{ $schoolClass->name }}</a>
                            （{{ $schoolClass->students_count }}人）
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </main>

@endsection
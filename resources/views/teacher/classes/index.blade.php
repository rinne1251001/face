@extends('layouts.test')
@section('title', 'クラス')
@section('content')

    <main class="main">
        <div class="content-wrapper">
            <div class="page-header">
                <h1>受け持ちクラス</h1>
            </div>
            <section class="teacher-card">
                @if ($classes->isEmpty())
                    <p class="muted teacher-empty">受け持ちクラスはありません。</p>
                @else
                    <div class="teacher-table-wrapper">
                        <table class="teacher-table">
                            <thead>
                                <tr><th>クラス</th><th>在籍生徒数</th><th>操作</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($classes as $schoolClass)
                                    <tr>
                                        <td>{{ $schoolClass->name }}</td>
                                        <td>{{ $schoolClass->students_count }}人</td>
                                        <td><a class="btn-action" href="{{ route('teacher.classes.show', $schoolClass) }}">出席状況</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>
    </main>

@endsection
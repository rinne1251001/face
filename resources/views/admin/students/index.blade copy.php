@extends('layouts.app')
@section('title', '生徒一覧')

@section('content')
<h1>生徒一覧</h1>
<form method="GET">
    <input name="q" value="{{ $q }}" placeholder="名前・学籍番号・クラス">
    <button>検索</button>
    <a href="{{ route('admin.students.create') }}">＋ 生徒を追加</a>
</form>

<table>
    <tr><th>学籍番号</th><th>名前</th><th>クラス</th><th>状態</th><th>顔写真</th><th>操作</th></tr>
    @forelse ($students as $student)
        <tr>
            <td>{{ $student->student_number }}</td>
            <td>{{ $student->name }}</td>
            <td>{{ $student->schoolClass->name }}</td>
            <td>{{ $student->is_active ? '有効' : '停止中' }}</td>
            <td>
                @if ($student->face_samples_count > 0)
                    {{ $student->face_samples_count }}枚
                @else
                    <span class="warn">未登録</span>
                @endif
            </td>
            <td>
                <a href="{{ route('admin.students.edit', $student) }}">編集</a> |
                <a href="{{ route('admin.faces.create', $student) }}">顔を登録</a>
            </td>
        </tr>
    @empty
        <tr><td colspan="6">該当する生徒はいません。</td></tr>
    @endforelse
</table>

<p>
    @if ($students->previousPageUrl())<a href="{{ $students->previousPageUrl() }}">前へ</a>@endif
    {{ $students->currentPage() }} / {{ $students->lastPage() }}
    @if ($students->nextPageUrl())<a href="{{ $students->nextPageUrl() }}">次へ</a>@endif
</p>
@endsection

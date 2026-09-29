@extends('layouts.app')
@section('title', '人物一覧')

@section('content')
<h1>人物一覧</h1>
<form method="GET">
    <input name="q" value="{{ $q }}" placeholder="名前・学籍番号・クラス">
    <button>検索</button>
</form>

<table>
    <tr><th>学籍番号</th><th>名前</th><th>クラス</th><th>状態</th><th>登録顔</th><th>操作</th></tr>
    @forelse ($people as $person)
        <tr>
            <td>{{ $person->student_number }}</td>
            <td>{{ $person->name }}</td>
            <td>{{ $person->class_name }}</td>
            <td>{{ $person->is_active ? '有効' : '停止中' }}</td>
            <td>{{ $person->face_samples_count }}枚</td>
            <td>
                <a href="{{ route('people.edit', $person) }}">編集</a> |
                <a href="{{ route('faces.create', $person) }}">顔を登録</a>
            </td>
        </tr>
    @empty
        <tr><td colspan="6">該当する人物はいません。</td></tr>
    @endforelse
</table>

<p>
    @if ($people->previousPageUrl())<a href="{{ $people->previousPageUrl() }}">前へ</a>@endif
    {{ $people->currentPage() }} / {{ $people->lastPage() }}
    @if ($people->nextPageUrl())<a href="{{ $people->nextPageUrl() }}">次へ</a>@endif
</p>
@endsection
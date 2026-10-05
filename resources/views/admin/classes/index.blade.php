@extends('layouts.app')
@section('title', 'クラス一覧')

@section('content')
<h1>クラス一覧</h1>
<p><a href="{{ route('admin.classes.create') }}">＋ クラスを追加</a></p>

<table>
    <tr><th>クラス名</th><th>生徒数</th><th>受け持ちの教師</th><th>操作</th></tr>
    @forelse ($classes as $schoolClass)
        <tr>
            <td>{{ $schoolClass->name }}</td>
            <td>{{ $schoolClass->students_count }}人</td>
            <td>
                @forelse ($schoolClass->teachers as $teacher)
                    {{ $teacher->name }}@if (! $loop->last)、@endif
                @empty
                    <span class="muted">なし</span>
                @endforelse
            </td>
            <td>
                <a href="{{ route('admin.classes.edit', $schoolClass) }}">編集</a>
                <form method="POST" action="{{ route('admin.classes.destroy', $schoolClass) }}" class="inline"
                      onsubmit="return confirm('このクラスを削除しますか？')">
                    @csrf
                    @method('DELETE')
                    <button>削除</button>
                </form>
            </td>
        </tr>
    @empty
        <tr><td colspan="4">クラスが登録されていません。</td></tr>
    @endforelse
</table>
<p class="muted">受け持ちの教師は「教師」の画面で設定します。</p>
@endsection

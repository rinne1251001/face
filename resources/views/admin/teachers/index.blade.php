@extends('layouts.app')
@section('title', '教師一覧')

@section('content')
<h1>教師一覧</h1>
<p><a href="{{ route('admin.teachers.create') }}">＋ 教師を追加</a></p>

<table>
    <tr><th>名前</th><th>ログインID</th><th>受け持ちクラス</th><th>担当授業</th><th>操作</th></tr>
    @forelse ($teachers as $teacher)
        <tr>
            <td>{{ $teacher->name }}</td>
            <td>{{ $teacher->login_id }}</td>
            <td>
                @forelse ($teacher->schoolClasses as $schoolClass)
                    {{ $schoolClass->name }}@if (! $loop->last)、@endif
                @empty
                    <span class="muted">なし</span>
                @endforelse
            </td>
            <td>
                @forelse ($teacher->subjects as $subject)
                    <div>{{ $subject->name }}（{{ $subject->schedule_label }}）</div>
                @empty
                    <span class="warn">未設定</span>
                @endforelse
            </td>
            <td>
                <a href="{{ route('admin.teachers.edit', $teacher) }}">編集</a>
                <form method="POST" action="{{ route('admin.teachers.destroy', $teacher) }}" class="inline"
                      onsubmit="return confirm('この教師を削除しますか？')">
                    @csrf
                    @method('DELETE')
                    <button>削除</button>
                </form>
            </td>
        </tr>
    @empty
        <tr><td colspan="5">教師が登録されていません。</td></tr>
    @endforelse
</table>
@endsection

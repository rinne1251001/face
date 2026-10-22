@extends('layouts.app')
@section('title', '授業一覧')

@section('content')
<h1>授業一覧</h1>
<p><a href="{{ route('admin.subjects.create') }}">＋ 授業を追加</a></p>

<table>
    <tr><th>曜日・時限</th><th>授業名</th><th>受講生徒数</th><th>担当の教師</th><th>操作</th></tr>
    @forelse ($subjects as $subject)
        <tr>
            <td>{{ $subject->schedule_label }}</td>
            <td>{{ $subject->name }}</td>
            <td>{{ $subject->students_count }}人</td>
            <td>
                @forelse ($subject->teachers as $teacher)
                    {{ $teacher->name }}@if (! $loop->last)、@endif
                @empty
                    <span class="warn">未設定</span>
                @endforelse
            </td>
            <td>
                <a href="{{ route('admin.subjects.edit', $subject) }}">編集</a>
                <form method="POST" action="{{ route('admin.subjects.destroy', $subject) }}" class="inline"
                      onsubmit="return confirm('この授業を削除しますか？受講・担当の設定も外れます。')">
                    @csrf
                    @method('DELETE')
                    <button>削除</button>
                </form>
            </td>
        </tr>
    @empty
        <tr><td colspan="5">授業が登録されていません。</td></tr>
    @endforelse
</table>
<p class="muted">担当の教師は「教師」の画面、受講する生徒は「生徒」の画面で設定します。同じ授業が週に複数回ある場合は、曜日・時限ごとに別々に登録してください。</p>
@endsection

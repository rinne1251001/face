@extends('layouts.app')
@section('title', '学科')

@section('content')
<main class="main">
    <div class="content-wrapper">
        <div class="page-header">
            <h1>学科</h1>
            <a class="btn" href="{{ route('admin.departments.create') }}">＋ 学科を追加</a>
        </div>

        <div>
            <table>
                <thead>
                    <tr><th>学科名</th><th>修業年限</th><th>生徒数（卒業生を含む）</th><th>操作</th></tr>
                </thead>
                <tbody>
                    @forelse ($departments as $department)
                        <tr>
                            <td>{{ $department->name }}</td>
                            <td>{{ $department->years }}年</td>
                            <td>{{ $department->students_count }}人</td>
                            <td>
                                <a href="{{ route('admin.departments.edit', $department) }}">編集</a>
                                {{-- 生徒がいる学科は削除できないので、ボタンも出さない --}}
                                @if ($department->students_count === 0)
                                    <form method="POST" action="{{ route('admin.departments.destroy', $department) }}" style="display: inline"
                                        onsubmit="return confirm('この学科を削除しますか？')">
                                        @csrf
                                        @method('DELETE')
                                        <button>削除</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4">学科が登録されていません。</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</main>
@endsection
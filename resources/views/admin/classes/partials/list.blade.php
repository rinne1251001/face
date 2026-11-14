{{-- クラス一覧の表の部分。並び替えのときは、ここだけを Ajax で入れ替える --}}
<p>全{{ $classes->count() }}件</p>
<div class="table-container">
    <table class="custom-table">
        <thead>
            <tr>
                {{-- key はコントローラーの sortColumns() のキー --}}
                @include('partials.sort-header', ['key' => 'name', 'label' => 'クラス名'])
                @include('partials.sort-header', ['key' => 'students', 'label' => '生徒数'])
                <th>受け持ちの教師</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($classes as $schoolClass)
                <tr>
                    <td><strong>{{ $schoolClass->name }}</strong></td>
                    <td>{{ $schoolClass->students_count }} 人</td>
                    <td>
                        @forelse ($schoolClass->teachers as $teacher)
                            {{ $teacher->name }}@if (! $loop->last)、@endif
                        @empty
                            なし
                        @endforelse
                    </td>
                    <td>
                        <a href="{{ route('admin.classes.edit', $schoolClass) }}" class="btn-edit">編集</a>
                        <form method="POST" action="{{ route('admin.classes.destroy', $schoolClass) }}" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button class="btn-delete">削除</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4">{{ $filtering ? '条件に合うクラスがありません。' : 'クラスが登録されていません。' }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
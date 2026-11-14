{{-- 教室一覧の表の部分。並び替えのときは、ここだけを Ajax で入れ替える --}}
<p>全{{ $rooms->count() }}件</p>
<div class="table-container">
    <table>
        <thead>
            <tr>
                {{-- key はコントローラーの sortColumns() のキー。並び替えをやめたい列は <th>教室名</th> のように戻す --}}
                @include('partials.sort-header', ['key' => 'name', 'label' => '教室名'])
                @include('partials.sort-header', ['key' => 'floor', 'label' => '階'])
                @include('partials.sort-header', ['key' => 'class', 'label' => 'クラス名'])
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rooms as $room)
                <tr>
                    <td><a href="{{ route('admin.rooms.show', $room) }}">{{ $room->name }}</a></td>
                    <td>{{ $room->floor_label }}</td>
                    <td>
                        @forelse ($room->schoolClasses as $schoolClass)
                            {{ $schoolClass->name }}
                        @empty
                            紐づくクラスはありません
                        @endforelse
                    </td>
                    <td>
                        <div style="display: flex; gap: 5px;">
                            <a href="{{ route('admin.rooms.edit', $room) }}" class="btn">編集</a>
                            <form method="POST" action="{{ route('admin.rooms.destroy', $room) }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn">削除</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center;">
                        {{ $filtering ? '条件に合う教室がありません' : '登録されている教室がありません' }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
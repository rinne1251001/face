@extends('layouts.test')
@section('title', '教室一覧')
@push('styles')
    <style>
        .table-container {
            overflow-x: auto;
            max-width: 100%;
        }
        table {
            border-collapse: collapse;
        }
        th {
            background-color: var(--color-main);
            color: var(--color-bg);
            border: 1px solid var(--color-bg);
            padding-block: 7px;
            padding-inline: 20px;
        }
        td {
            color: var(--color-main);
            border: 1px solid var(--color-main);
            padding: 10px 20px;
        }
    </style>
@endpush

@section('content')

    <main class="main">
        <div class="content-wrapper">

            <div class="page-header">
                <h1>教室一覧</h1>
                <div>
                    <a class="btn" href="{{ route('admin.rooms.create') }}">教室追加</a>
                </div>
                
            </div>

            <p>全{{ $rooms->count() }}件</p>
            <div class="table-container">
                <table>
                    <thead>
                        <tr><th>教室名</th><th>階</th><th>クラス名</th><th>操作</th></tr>
                    </thead>
                    <tbody>
                        @forelse($rooms as $room)
                            <tr>
                                <td><a href="{{ route('admin.rooms.show', $room) }}">{{ $room->name }}</a></td>
                                <td>{{ $room->floorLabel }}</td>
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
                                            <button class="btn">この教室を削除</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4"style="text-align: center;">登録されている教室がありません</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </main>

@endsection
@push('scripts')
    <script></script>
@endpush
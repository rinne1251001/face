@extends('layouts.test')
@section('title', $room->exists ? '教室の編集' : '教室の追加')
@section('content')

    <main class="main">
        <div class="content-wrapper">
            <form method="POST" action="{{ $room->exists ? route('admin.rooms.update', $room) : route('admin.rooms.store') }}">
                @csrf
                @if ($room->exists)
                    @method('PUT')
                @endif

                <div>
                    <label for="name">教室名</label>
                    <input id="name" name="name" value="{{ old('name', $room->name) }}" required>
                </div>

                <div>
                    <label for="floor">階</label>
                    <select id="floor" name="floor" required>
                        {{-- 地下1階〜20階（0階はない）。コントローラーのチェックと同じ範囲 --}}
                        @foreach (range(-1, 9) as $f)
                            @continue($f === 0)
                            <option value="{{ $f }}" @selected((int) old('floor', $room->floor ?? 1) === $f)>{{ $f < 0 ? '地下'.abs($f).'階' : $f.'階' }}</option>
                        @endforeach
                    </select>
                </div>
                
                
                        
                

                @if ($errors->any())
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif

                <button>保存</button>
            </form>
        </div>
    </main>

@endsection
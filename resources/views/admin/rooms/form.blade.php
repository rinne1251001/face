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
                        <option value="1" @selected(old('floor', $room->floor ?? '') == 1)>１階</option>
                        <option value="2" @selected(old('floor', $room->floor ?? '') == 2)>２階</option>
                        <option value="3" @selected(old('floor', $room->floor ?? '') == 3)>３階</option>
                        <option value="4" @selected(old('floor', $room->floor ?? '') == 4)>４階</option>
                        <option value="5" @selected(old('floor', $room->floor ?? '') == 5)>５階</option>
                        <option value="6" @selected(old('floor', $room->floor ?? '') == 6)>６階</option>
                        <option value="7" @selected(old('floor', $room->floor ?? '') == 7)>７階</option>
                        <option value="8" @selected(old('floor', $room->floor ?? '') == 8)>８階</option>
                        <option value="9" @selected(old('floor', $room->floor ?? '') == 9)>９階</option>
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
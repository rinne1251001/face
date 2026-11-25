@extends('layouts.app')
@section('title', $camera->exists ? 'カメラの編集' : 'カメラの追加')
@section('content')

    <main class="main">
        <div class="content-wrapper">

            <form method="POST" action="{{ $camera->exists ? route('admin.cameras.update', $camera) : route('admin.cameras.store') }}">
                @csrf
                @if ($camera->exists)
                    @method('PUT')
                @endif

                <div>
                    <label for="classroom">教室</label>
                    <select id="classroom" name="classroom_id" required>
                        @foreach ($rooms as $room)
                            <option value="{{ $room->id }}">
                                {{ $room->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label>パスワード（8文字以上）</label>
                    <input type="password" name="password" autocomplete="new-password" @required(! $camera->exists)>
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
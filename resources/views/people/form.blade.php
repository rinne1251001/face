@extends('layouts.app')
@section('title', $person->exists ? '人物の編集' : '人物の追加')

@section('content')
<h1>{{ $person->exists ? '人物の編集' : '人物の追加' }}</h1>

<form method="POST" action="{{ $person->exists ? route('people.update', $person) : route('people.store') }}">
    @csrf
    @if ($person->exists)
        @method('PUT')
    @endif
    <p><label>名前<br><input name="name" value="{{ old('name', $person->name) }}" required></label></p>
    <p><label>学籍番号<br><input name="student_number" value="{{ old('student_number', $person->student_number) }}" required></label></p>
    <p><label>クラス<br><input name="class_name" value="{{ old('class_name', $person->class_name) }}"></label></p>
    <p><label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $person->is_active))> 照合の対象にする</label></p>
    @if ($errors->any())
        <ul class="error">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    @endif
    <button>保存</button>
</form>

@if ($person->exists)
    <h2>登録済みの顔（{{ $person->faceSamples->count() }}枚）</h2>
    <p><a href="{{ route('faces.create', $person) }}">カメラで顔を追加登録する</a></p>
    <div class="thumbs">
        @foreach ($person->faceSamples as $sample)
            <figure style="display:inline-block; margin:0">
                <img src="{{ route('faces.image', $sample) }}" alt="">
                <form method="POST" action="{{ route('faces.destroy', $sample) }}" onsubmit="return confirm('この写真を削除しますか？')">
                    @csrf
                    @method('DELETE')
                    <button>削除</button>
                </form>
            </figure>
        @endforeach
    </div>

    <hr>
    <form method="POST" action="{{ route('people.destroy', $person) }}" onsubmit="return confirm('この人物と顔データをすべて削除しますか？')">
        @csrf
        @method('DELETE')
        <button>この人物を削除</button>
    </form>
@endif
@endsection
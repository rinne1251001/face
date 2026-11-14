@extends('layouts.test')
@section('title', $department->exists ? '学科の編集' : '学科の追加')

@section('content')
<main class="main">
    <div class="content-wrapper">
        <div class="page-header">
            <h1>{{ $department->exists ? '学科の編集' : '学科の追加' }}</h1>
        </div>

        <form method="POST" action="{{ $department->exists ? route('admin.departments.update', $department) : route('admin.departments.store') }}">
            @csrf
            @if ($department->exists)
                @method('PUT')
            @endif
            <p><label>学科名<br><input name="name" value="{{ old('name', $department->name) }}" placeholder="例：情報処理科" maxlength="50" required></label></p>
            <p>
                <label>修業年限（何年で卒業するか）<br>
                    <select name="years" required>
                        @for ($y = 1; $y <= 6; $y++)
                            <option value="{{ $y }}" @selected((int) old('years', $department->years ?? 2) === $y)>{{ $y }}年制</option>
                        @endfor
                    </select>
                </label>
            </p>
            @if ($errors->any())
                <ul class="error">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            @endif
            <button>保存</button>
        </form>
        <p><a href="{{ route('admin.departments.index') }}">学科一覧へ戻る</a></p>
    </div>
</main>
@endsection
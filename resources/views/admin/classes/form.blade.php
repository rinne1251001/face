@extends('layouts.test')
@section('title', ($schoolClass->exists ? 'クラスの編集' : 'クラスの追加').'（'.$year->label.'）')

@section('content')
<main class="main">
    <div class="content-wrapper">
        <div class="page-header">
            <h1>{{ $schoolClass->exists ? 'クラスの編集' : 'クラスの追加' }}（{{ $year->label }}）</h1>
        </div>

        @include('admin.partials.year-lock-notice', ['year' => $year])

        <form method="POST" action="{{ $schoolClass->exists ? route('admin.classes.update', $schoolClass) : route('admin.classes.store') }}">
            @csrf
            @if ($schoolClass->exists)
                @method('PUT')
            @else
                {{-- どの年度にクラスを作るか（編集のときは、クラスの年度のまま） --}}
                <input type="hidden" name="year" value="{{ $year->year }}">
            @endif

            {{-- 読み取り専用の年度では、入力欄をまとめて押せなくする --}}
            <fieldset @disabled(! $year->isEditable()) style="border: none; margin: 0; padding: 0;">
                <p><label>クラス名<br><input name="name" value="{{ old('name', $schoolClass->name) }}" placeholder="例：1T1" required></label></p>
                <p>
                    <label>学年<br>
                        <select name="grade" required>
                            @for ($g = 1; $g <= $maxGrade; $g++)
                                <option value="{{ $g }}" @selected((int) old('grade', $schoolClass->grade) === $g)>{{ $g }}年</option>
                            @endfor
                        </select>
                    </label>
                </p>
                <p>
                    <label>ホームルームの教室<br>
                        <select name="classroom_id">
                            <option value="">未設定</option>
                            @foreach ($classrooms as $classroom)
                                <option value="{{ $classroom->id }}" @selected(old('classroom_id', $schoolClass->classroom_id) == $classroom->id)>
                                    {{ $classroom->name }}（{{ $classroom->floor_label }}）
                                </option>
                            @endforeach
                        </select>
                    </label>
                    @if ($classrooms->isEmpty())
                        <br><small>教室がまだ登録されていません。</small>
                    @endif
                </p>
            </fieldset>
            @if ($errors->any())
                <ul class="error">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            @endif
            @if ($year->isEditable())
                <button>保存</button>
            @endif
        </form>
        <p><a href="{{ route('admin.classes.index', ['year' => $year->year]) }}">クラス一覧へ戻る</a></p>
    </div>
</main>
@endsection
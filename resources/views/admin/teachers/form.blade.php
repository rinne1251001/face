@extends('layouts.app')
@section('title', $teacher->exists ? '教師の編集' : '教師の追加')

@section('content')
<main class="main">
    <div class="content-wrapper">
        <div class="page-header">
            <h1 class="page-title">{{ $teacher->exists ? '教師の編集' : '教師の追加' }}</h1>
        </div>

        {{-- 受け持ちクラス・担当授業は年度ごとに違う。年度を切り替えると、その年度の分を表示・保存する
             （切り替えると画面を開き直すので、入力の途中なら先に保存してください） --}}
        @if ($year)
            @include('admin.partials.year-switch', ['year' => $year])
        @endif

        <form method="POST" action="{{ $teacher->exists ? route('admin.teachers.update', $teacher) : route('admin.teachers.store') }}">
            @csrf
            @if ($teacher->exists)
                @method('PUT')
            @endif
            {{-- どの年度の受け持ち・担当を保存するか --}}
            <input type="hidden" name="year" value="{{ $year?->year }}">

            <p><label>名前<br><input name="name" value="{{ old('name', $teacher->name) }}" required></label></p>
            <p><label>ログインID<br><input name="login_id" value="{{ old('login_id', $teacher->login_id) }}" required></label></p>
            <p><label>パスワード（8文字以上）<br>
                <input type="password" name="password" autocomplete="new-password" @required(! $teacher->exists)>
            </label>
            @if ($teacher->exists)
                <br><span class="muted">変更しない場合は空欄のままにしてください。</span>
            @endif
            </p>
            <p>
                <label>
                    <input type="checkbox" name="can_login" value="1" @checked(session()->hasOldInput() ? old('can_login') : $teacher->can_login)>
                    ログインできる
                </label>
                <br><span class="muted">退職などで使わなくなったアカウントは、削除せずにチェックを外すと、受け持ちの記録を残したままログインだけを止められます。</span>
            </p>

            @if ($year === null)
                <p class="muted">年度がまだ登録されていないため、受け持ちクラスと担当授業は設定できません。</p>
            @else
                @include('admin.partials.year-lock-notice', ['year' => $year])
            @endif

            {{-- 読み取り専用の年度（と、年度がないとき）は、受け持ち・担当を変更できない --}}
            <fieldset @disabled($year === null || ! $year->isEditable()) style="border: none; margin: 0; padding: 0;">

            <fieldset>
                <legend>受け持ちクラス（{{ $year?->label }}・なしでも可）</legend>
                @forelse ($classes as $schoolClass)
                    <label style="display:block">
                        <input type="checkbox" name="class_ids[]" value="{{ $schoolClass->id }}"
                            @checked(in_array($schoolClass->id, $selectedClassIds, true))>
                        {{ $schoolClass->name }}（{{ $schoolClass->grade }}年）
                    </label>
                @empty
                    <p>{{ $year?->label }}のクラスが登録されていません。</p>
                @endforelse
            </fieldset>

            <fieldset>
                <legend>担当授業（{{ $year?->label }}・なしでも可）</legend>
                @forelse ($subjects as $subject)
                    <label style="display:block">
                        <input type="checkbox" name="subject_ids[]" value="{{ $subject->id }}"
                            @checked(in_array($subject->id, $selectedSubjectIds, true))>
                        {{ $subject->name }}（{{ $subject->schedule_label }}）
                    </label>
                @empty
                    <p>{{ $year?->label }}の授業が登録されていません。担当授業は、授業を登録したあとで設定できます。</p>
                @endforelse
            </fieldset>
            </fieldset>

            @if ($errors->any())
                <ul class="error">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            @endif
            <button>保存</button>
        </form>
        <p><a href="{{ route('admin.teachers.index', ['year' => $year?->year]) }}">教師一覧へ戻る</a></p>
    </div>
</main>
@endsection

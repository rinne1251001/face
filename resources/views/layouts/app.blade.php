<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '顔認識システム')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
{{-- 管理者の画面（/admin/...）を開いていて、管理者としてログインしているとき --}}
@if (request()->routeIs('admin.*') && auth('admin')->check())
    <nav>
        <strong>管理者</strong>
        <a href="{{ route('admin.students.index') }}">生徒一覧</a>
        <a href="{{ route('admin.students.create') }}">生徒を追加</a>
        <a href="{{ route('admin.classes.index') }}">クラス</a>
        <a href="{{ route('admin.subjects.index') }}">授業</a>
        <a href="{{ route('admin.teachers.index') }}">教師</a>
        <a href="{{ route('admin.match.show') }}">照合テスト</a>
        <span style="margin-left:auto">{{ auth('admin')->user()->login_id }}</span>
        <form method="POST" action="{{ route('admin.logout') }}" class="inline">@csrf<button>ログアウト</button></form>
    </nav>
{{-- 教師の画面（/teacher/...）を開いていて、教師としてログインしているとき --}}
@elseif (request()->routeIs('teacher.*') && auth('teacher')->check())
    <nav>
        <strong>教師</strong>
        <a href="{{ route('teacher.dashboard') }}">ホーム</a>
        <span style="margin-left:auto">{{ auth('teacher')->user()->name }} 先生</span>
        <form method="POST" action="{{ route('teacher.logout') }}" class="inline">@csrf<button>ログアウト</button></form>
    </nav>
@endif

@if (session('status'))
    <p class="ok">{{ session('status') }}</p>
@endif
@if (session('error'))
    <p class="error">{{ session('error') }}</p>
@endif

@yield('content')
@stack('scripts')
</body>
</html>

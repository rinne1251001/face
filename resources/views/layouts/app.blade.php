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
@auth('admin')
    <nav>
        <a href="{{ route('people.index') }}">人物一覧</a>
        <a href="{{ route('people.create') }}">人物を追加</a>
        <a href="{{ route('match.show') }}">照合テスト</a>
        <span style="margin-left:auto">{{ auth('admin')->user()->name }}</span>
        <form method="POST" action="{{ route('logout') }}">@csrf<button>ログアウト</button></form>
    </nav>
@endauth

@if (session('status'))
    <p class="ok">{{ session('status') }}</p>
@endif

@yield('content')
@stack('scripts')
</body>
</html>
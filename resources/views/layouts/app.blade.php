<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '顔認識システム')</title>
    <style>
        body { font-family: sans-serif; max-width: 960px; margin: 0 auto; padding: 16px; }
        nav { display: flex; gap: 12px; align-items: center; border-bottom: 1px solid #ccc; padding-bottom: 8px; margin-bottom: 16px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        video { width: 100%; max-width: 640px; background: #000; }
        .thumbs img { width: 96px; height: 96px; object-fit: cover; margin: 4px; }
        .ok { color: #070; } .error { color: #c00; }
        .camera { position: relative; width: 100%; max-width: 640px; }
        .camera video { display: block; width: 100%; background: #000; }
        .camera canvas { position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; }
    </style>
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
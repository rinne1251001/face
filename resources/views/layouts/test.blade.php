<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta property="og:site_name" content="Rollin">
    <title>@yield('title', 'Rollin')</title>
    @vite(['resources/css/test.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body>

    <header></header>

    @yield('content')

    <script></script>
    @stack('scripts')
</body>
</html>
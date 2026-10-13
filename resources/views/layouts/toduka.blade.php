<!DOCTYPE html>
<html lang="ja">
    <head>
        <title>@yield('title', 'toduka')</title>
        <style>
            :root {
                --color-font: #1c79ea;
            }
            body {
                color: var(--color-font);
            }
        </style>
    </head>
    <body>
        <header>ヘッダー</header>

        @yield('content')

        <footer>フッター</footer>
    </body>
<html>
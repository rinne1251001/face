<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'marumo')</title>
    <style>
        :root {
            --color-font: #5142b4;
            --color-bg: #f7f6fb;
            --color-white: #ffffff;
        }

        body {
            font-family: sans-serif;
            color: var(--color-font);
            background-color: var(--color-bg);
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        header, footer {
            text-align: center;
            padding: 1rem;
            background-color: var(--color-white);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.03);
        }

        .nav-menu {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
        }

        .nav-menu a {
            color: var(--color-font);
            text-decoration: none;
            font-weight: bold;
            padding: 0.3rem 0.6rem;
            border-radius: 6px;
            transition: background-color 0.2s;
        }

        .nav-menu a:hover {
            background-color: #f0edff;
        }

        .profile-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem 1rem;
        }

        .profile-card {
            background-color: var(--color-white);
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            text-align: center;
            max-width: 480px;
            width: 100%;
        }

        .profile-link {
            color: var(--color-font);
            text-decoration: none;
            font-weight: bold;
            padding: 0.5rem 1rem;
            border: 1px solid var(--color-font);
            border-radius: 20px;
            transition: all 0.2s ease;
            display: inline-block;
        }

        .profile-link:hover {
            background-color: var(--color-font);
            color: var(--color-white);
        }
    </style>
</head>
<body>
    <header>
        <nav class="nav-menu">
            <a href="/">プロフィール</a>
            <a href="/dobon">🃏 ドボン</a>
        </nav>
    </header>

    @yield('content')

    <footer>
        <small>&copy; marumo app</small>
    </footer>
</body>
</html>
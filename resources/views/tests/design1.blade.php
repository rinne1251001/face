<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>レスポンシブヘッダー</title>
    @vite(['resources/css/test.css', 'resources/js/app.js'])

    <style>
        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            background: var(--color-bg);
            color: var(--color-font);
        }

        body.menu-open {
            overflow: hidden;
        }

        button:focus-visible,
        a:focus-visible,
        input:focus-visible {
            outline: 3px solid #d59b53;
            outline-offset: 4px;
        }

        /* =========================
           PCヘッダー
        ========================= */

        .pc-header {
            min-height: 150px;
            padding: 20px 4.5%;
            display: grid;
            grid-template-columns: 1fr 150px 1fr;
            align-items: center;
            gap: 20px;
            background: var(--color-bg);
            box-shadow: 0 3px 12px rgb(90 60 30 / 7%);
            position: relative;
            z-index: 10;
        }

        .pc-nav ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            gap: clamp(16px, 2vw, 36px);
        }

        .pc-nav a {
            display: inline-block;
            font-weight: 700;
            white-space: nowrap;
        }

        .pc-nav a:hover {
            opacity: 0.65;
        }

        .pc-nav-left {
            justify-self: start;
        }

        .pc-nav-left ul {
            gap: clamp(18px, 2.5vw, 45px);
        }

        .pc-header > .logo {
            justify-self: center;
        }

        .main-nav-right {
            justify-self: end;
        }

        .main-nav-right ul {
            justify-content: flex-end;
            gap: clamp(15px, 2vw, 35px);
        }

        .main-nav-right a {
            font-size: clamp(14px, 1.1vw, 20px);
        }

        /* ロゴ：画像がない場合の仮ロゴ */

        .logo {
            width: 100px;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            color: var(--color-main);
            flex-shrink: 0;
        }

        .logo-roof {
            position: absolute;
            top: 10px;
            left: 50%;
            width: 0;
            height: 0;
            transform: translateX(-50%);
            border-left: 40px solid transparent;
            border-right: 40px solid transparent;
            border-bottom: 43px solid var(--color-main);
        }

        .logo-roof::after {
            content: "";
            position: absolute;
            top: 8px;
            left: -29px;
            border-left: 29px solid transparent;
            border-right: 29px solid transparent;
            border-bottom: 30px solid var(--color-bg);
        }

        .logo-text {
            position: relative;
            z-index: 1;
            margin-top: 30px;
            font-family: serif;
            font-size: 40px;
            line-height: 1;
            font-weight: 900;
        }

        .logo-small {
            width: 65px;
            height: 65px;
        }

        .logo-small .logo-roof {
            top: 4px;
            border-left-width: 28px;
            border-right-width: 28px;
            border-bottom-width: 29px;
        }

        .logo-small .logo-roof::after {
            top: 6px;
            left: -20px;
            border-left-width: 20px;
            border-right-width: 20px;
            border-bottom-width: 20px;
        }

        .logo-small .logo-text {
            margin-top: 20px;
            font-size: 27px;
        }

        /* ログアウトボタン */

        .company-banner {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 14px;
            border: 1px solid var(--color-font-light);
            background: #fffdf5;
            white-space: nowrap;
        }

        .company-banner span {
            color: var(--color-main);
            margin-left: 12px;
        }

        /* =========================
           スマホヘッダー
        ========================= */

        .mobile-header {
            display: none;
        }

        .menu-toggle {
            width: 42px;
            height: 42px;
            border: none;
            background: transparent;
            padding: 7px;
            cursor: pointer;
            position: relative;
            z-index: 120;
        }

        .hamb-line {
            display: block;
            width: 100%;
            height: 3px;
            background: var(--color-font);
            position: absolute;
            left: 0;
            transition:
                transform 0.3s ease,
                top 0.3s ease,
                opacity 0.2s ease;
        }

        .hamb-line:nth-child(1) {
            top: 10px;
        }

        .hamb-line:nth-child(2) {
            top: 19px;
        }

        .hamb-line:nth-child(3) {
            top: 28px;
        }

        .menu-toggle.active .hamb-line:nth-child(1) {
            top: 19px;
            transform: rotate(45deg);
        }

        .menu-toggle.active .hamb-line:nth-child(2) {
            opacity: 0;
        }

        .menu-toggle.active .hamb-line:nth-child(3) {
            top: 19px;
            transform: rotate(-45deg);
        }

        /* =========================
           スマホメニュー全画面
        ========================= */

        .mobile-menu {
            position: fixed;
            inset: 0;
            z-index: 100;
            background: var(--color-bg);
            overflow-y: auto;
            overscroll-behavior: contain;
            visibility: hidden;
            opacity: 0;
            pointer-events: none;
            transform: translateY(-8px);
            transition:
                opacity 0.25s ease,
                transform 0.25s ease,
                visibility 0.25s;
        }

        .mobile-menu.open {
            visibility: visible;
            opacity: 1;
            pointer-events: auto;
            transform: translateY(0);
        }

        .mobile-menu-top {
            position: sticky;
            top: 0;
            min-height: 82px;
            padding: 8px 25px;
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            background: var(--color-bg);
            box-shadow: 0 3px 12px rgb(90 60 30 / 7%);
            z-index: 2;
        }

        .mobile-menu-top .logo {
            grid-column: 2;
        }

        .mobile-menu-links {
            list-style: none;
            margin: 0;
            padding: 38px 20px 10px;
            text-align: center;
        }

        .mobile-menu-links li {
            margin: 0;
            padding: 0;
        }

        .mobile-menu-links a {
            display: block;
            padding: 19px 10px;
            font-weight: 700;
            transition: background 0.2s;
        }

        .mobile-menu-links a:hover {
            background: rgb(135 74 44 / 7%);
        }

        /* =========================
           レスポンシブ切り替え
        ========================= */

        @media (max-width: 1100px) and (min-width: 768px) {
            .pc-header {
                padding-inline: 22px;
                grid-template-columns: 1fr 95px 1fr;
                gap: 12px;
            }

            .pc-nav ul {
                gap: 13px;
            }

            .pc-nav a {
                font-size: 14px;
            }

            .main-nav-right a {
                font-size: 14px;
            }

            .logo {
                transform: scale(0.85);
            }

            .company-banner {
                display: none;
            }
        }

        @media (max-width: 767px) {
            .pc-header {
                display: none;
            }

            .mobile-header {
                height: 82px;
                padding: 8px 25px;
                display: grid;
                grid-template-columns: 1fr auto 1fr;
                align-items: center;
                background: var(--color-bg);
                box-shadow: 0 3px 12px rgb(90 60 30 / 7%);
                position: relative;
                z-index: 110;
            }

            .mobile-header .logo {
                grid-column: 2;
            }

            .mobile-header-actions {
                justify-self: end;
                display: flex;
                align-items: center;
                gap: 22px;
            }

            .mobile-menu-top {
                padding-inline: 25px;
            }

            .mobile-menu-links a {
                font-size: 21px;
            }
        }

        @media (max-width: 380px) {
            .mobile-header {
                padding-inline: 12px;
            }

            .mobile-menu-top {
                padding-inline: 12px;
            }

            .mobile-header-actions {
                gap: 12px;
            }

            .mobile-menu-links a {
                font-size: 19px;
                padding-block: 16px;
            }
        }

        /* 激しいアニメーションや画面がグニャグニャ動く演出が、前庭障害や乗り物酔いのような体調不良（めまいや吐き気など）を引き起こす人への配慮 */
        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                scroll-behavior: auto !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>

<body>
    @php
        // ログインしていないときの初期値（未定義エラー防止）
        $mainMenu = [];
        $subMenu  = [];
        $userMenu = [];
    @endphp

    @auth('admin')
        @php
            // =========================
            // メニュー定義（ここを編集すれば全ヘッダーに反映されます）
            // =========================

            // メインメニュー（PC左側・スマホメニュー）
            $mainMenu = [
                ['label' => '生徒一覧',   'url' => '#'],
                ['label' => '生徒を追加', 'url' => '#'],
                ['label' => 'クラス',     'url' => '#'],
                ['label' => '授業',       'url' => '#'],
            ];

            // サブメニュー（PC右側・スマホメニュー）
            $subMenu = [
                ['label' => '教師',       'url' => '#company'],
                ['label' => '照合テスト', 'url' => '#sustainability'],
            ];

            // ユーザーメニュー（PC右側・スマホヘッダー）
            // banner => true のものはボタン風（.company-banner）で表示
            $userMenu = [
                ['label' => 'ユーザー名', 'url' => '#ir'],
                ['label' => 'ログアウト', 'url' => '#corporate', 'banner' => true],
            ];
        @endphp
    @elseauth('teacher')
    @endauth


    <!-- =========================
         PCヘッダー
    ========================== -->

    <header class="pc-header">

        <!-- 左側のメニュー -->
        <nav class="pc-nav pc-nav-left" aria-label="メインメニュー">
            <ul>
                @foreach ($mainMenu as $item)
                    <li><a href="{{ $item['url'] }}">{{ $item['label'] }}</a></li>
                @endforeach
            </ul>
        </nav>

        <!-- 中央のロゴ -->
        <a href="#" class="logo" aria-label="トップページ">
            <span class="logo-roof"></span>
            <span class="logo-text">角</span>
        </a>

        <!-- 右側のメニュー（横並び） -->
        <nav class="pc-nav main-nav-right" aria-label="企業情報">
            <ul>
                @foreach (array_merge($subMenu, $userMenu) as $item)
                    <li>
                        @if ($item['banner'] ?? false)
                            <a href="{{ $item['url'] }}" class="company-banner">
                                {{ $item['label'] }}
                                <span>›</span>
                            </a>
                        @else
                            <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </nav>

    </header>


    <!-- =========================
         スマホヘッダー
    ========================== -->

    <header class="mobile-header">

        <button
            type="button"
            id="menu-toggle"
            class="menu-toggle"
            aria-label="メニューを開く"
            aria-expanded="false"
            aria-controls="mobile-menu"
        >
            <span class="hamb-line"></span>
            <span class="hamb-line"></span>
            <span class="hamb-line"></span>
        </button>

        <a href="#" class="logo logo-small" aria-label="トップページ">
            <span class="logo-roof"></span>
            <span class="logo-text">角</span>
        </a>

        <div class="mobile-header-actions pc-nav main-nav-right">
            <ul>
                @foreach ($userMenu as $item)
                    <li>
                        @if ($item['banner'] ?? false)
                            <a href="{{ $item['url'] }}" class="company-banner">
                                {{ $item['label'] }}
                                <span>›</span>
                            </a>
                        @else
                            <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </header>


    <!-- =========================
         スマホメニュー
    ========================== -->

    <div
        id="mobile-menu"
        class="mobile-menu"
        aria-hidden="true"
        inert
    >

        <div class="mobile-menu-top">

            <button
                type="button"
                id="menu-close"
                class="menu-toggle"
                aria-label="メニューを閉じる"
            >
                <span class="hamb-line"></span>
                <span class="hamb-line"></span>
                <span class="hamb-line"></span>
            </button>

            <a href="#" class="logo logo-small" aria-label="トップページ">
                <span class="logo-roof"></span>
                <span class="logo-text">角</span>
            </a>
        </div>

        <nav aria-label="スマホメニュー">
            <ul class="mobile-menu-links">
                @foreach (array_merge($mainMenu, $subMenu) as $item)
                    <li><a href="{{ $item['url'] }}">{{ $item['label'] }}</a></li>
                @endforeach
            </ul>
        </nav>

    </div>


    <!-- =========================
         JavaScript
    ========================== -->

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const menu = document.getElementById('mobile-menu');
            const menuToggle = document.getElementById('menu-toggle');
            const menuClose = document.getElementById('menu-close');
            const menuLinks = menu.querySelectorAll('a');
            const searchForm = document.getElementById('site-search');

            let previousFocus = null;

            function openMenu() {
                previousFocus = document.activeElement;

                menu.classList.add('open');
                menu.removeAttribute('inert');
                menu.setAttribute('aria-hidden', 'false');

                menuToggle.classList.add('active');
                menuToggle.setAttribute('aria-expanded', 'true');
                menuToggle.setAttribute('aria-label', 'メニューを閉じる');

                menuClose.classList.add('active');

                document.body.classList.add('menu-open');

                menuClose.focus();
            }

            function closeMenu(restoreFocus = true) {
                menu.classList.remove('open');
                menu.setAttribute('inert', '');
                menu.setAttribute('aria-hidden', 'true');

                menuToggle.classList.remove('active');
                menuToggle.setAttribute('aria-expanded', 'false');
                menuToggle.setAttribute('aria-label', 'メニューを開く');

                menuClose.classList.remove('active');

                document.body.classList.remove('menu-open');

                if (restoreFocus) {
                    menuToggle.focus();
                } else if (previousFocus instanceof HTMLElement) {
                    previousFocus.focus();
                }
            }

            menuToggle.addEventListener('click', () => {
                if (menu.classList.contains('open')) {
                    closeMenu();
                } else {
                    openMenu();
                }
            });

            menuClose.addEventListener('click', () => {
                closeMenu();
            });

            // メニュー内のリンクを押したら閉じる
            menuLinks.forEach(link => {
                link.addEventListener('click', () => {
                    closeMenu(false);
                });
            });

            // Escキーで閉じる
            document.addEventListener('keydown', event => {
                if (
                    event.key === 'Escape' &&
                    menu.classList.contains('open')
                ) {
                    closeMenu();
                }

                // 簡易フォーカス制御
                if (
                    event.key === 'Tab' &&
                    menu.classList.contains('open')
                ) {
                    const focusable = menu.querySelectorAll(
                        'a[href], button:not(:disabled), input:not(:disabled)'
                    );

                    const first = focusable[0];
                    const last = focusable[focusable.length - 1];

                    if (event.shiftKey && document.activeElement === first) {
                        event.preventDefault();
                        last.focus();
                    } else if (
                        !event.shiftKey &&
                        document.activeElement === last
                    ) {
                        event.preventDefault();
                        first.focus();
                    }
                }
            });

            // PC幅に変わったらスマホメニューを閉じる
            window.addEventListener('resize', () => {
                if (window.innerWidth >= 768 && menu.classList.contains('open')) {
                    closeMenu(false);
                }
            });

            // 検索フォーム（フォームがあるページだけ動作）
            // 実際の検索URLに合わせて変更してください
            if (searchForm) {
                searchForm.addEventListener('submit', event => {
                    event.preventDefault();

                    const keyword = searchForm.elements.q.value.trim();

                    if (!keyword) {
                        searchForm.elements.q.focus();
                        return;
                    }

                    const searchUrl = new URL('/search', window.location.origin);
                    searchUrl.searchParams.set('q', keyword);

                    window.location.href = searchUrl.toString();
                });
            }
        });
    </script>

</body>
</html>
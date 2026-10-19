
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#fcf8e9">
    <title>レスポンシブヘッダー</title>

    @vite(['resources/css/test.css', 'resources/js/app.js'])

    <style>
        :root {
            --header-height: 150px;
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            background: var(--color-bg);
            color: var(--color-font);
            font-family:
                "Noto Sans JP",
                "Yu Gothic",
                "Meiryo",
                sans-serif;
        }

        body.menu-open {
            overflow: hidden;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button,
        input {
            font: inherit;
        }

        button {
            color: inherit;
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
    font-size: clamp(14px, 1.15vw, 21px);
    font-weight: 700;
    white-space: nowrap;
}

        .pc-nav a:hover,
        .utility-nav a:hover {
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


        .pc-logo {
            justify-self: center;
        }

        .pc-right {
            justify-self: end;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 22px;
        }

        .utility-nav {
            display: flex;
            align-items: center;
            gap: 28px;
            font-size: 14px;
            white-space: nowrap;
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

        /* カート・検索・Instagram */

        .icon-link {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .icon-link svg {
            width: 100%;
            height: 100%;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .instagram-icon {
            color: #e6004f;
        }

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
    font-size: 20px;
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

        .mobile-menu-actions {
            justify-self: end;
            display: flex;
            align-items: center;
            gap: 22px;
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
            font-size: 23px;
            font-weight: 700;
            transition: background 0.2s;
        }

        .mobile-menu-links a:hover {
            background: rgb(135 74 44 / 7%);
        }

        .mobile-menu-subnav {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 14px 30px;
            margin: 12px 10px 0;
        }

        .mobile-menu-subnav a {
            font-size: 19px;
            padding: 8px 0;
        }

        .mobile-menu-extra {
            margin: 38px auto 0;
            padding: 0 28px 30px;
            max-width: 760px;
            text-align: center;
        }

        .mobile-banner-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 22px;
            margin: 32px 0;
        }

        .mobile-search {
            display: flex;
            align-items: center;
            width: 100%;
            min-height: 62px;
            padding: 0 22px;
            background: #fff;
            border-radius: 40px;
        }

        .mobile-search input {
            min-width: 0;
            width: 100%;
            border: none;
            outline: none;
            background: transparent;
            color: var(--color-font);
            font-size: 19px;
        }

        .mobile-search input::placeholder {
            color: #b9b9b9;
        }

        .mobile-search button {
            border: none;
            background: transparent;
            width: 30px;
            height: 30px;
            padding: 3px;
            cursor: pointer;
            flex-shrink: 0;
        }

        .mobile-search button svg {
            width: 100%;
            height: 100%;
            fill: none;
            stroke: #cba67e;
            stroke-width: 2;
        }

        .follow-us {
            margin: 25px 0 12px;
            font-size: 22px;
            font-weight: 500;
            letter-spacing: 1px;
        }

        .social-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 15px 0;
        }

        .social-links .icon-link {
            width: 35px;
            height: 35px;
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

            .utility-nav {
                gap: 12px;
                font-size: 12px;
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

            .mobile-header .icon-link {
                width: 32px;
                height: 32px;
            }

            .mobile-menu-top {
                padding-inline: 25px;
            }

            .mobile-menu-links a {
                font-size: 21px;
            }

            .mobile-menu-subnav a {
                font-size: 17px;
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

            .mobile-menu-extra {
                padding-inline: 18px;
            }
        }

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

    <!-- =========================
         PCヘッダー
    ========================== -->

    
<header class="pc-header">

    <!-- 左側のメニュー -->
    <nav class="pc-nav pc-nav-left" aria-label="メインメニュー">
        <ul>
            <li><a href="#products">生徒一覧</a></li>
            <li><a href="#commitment">生徒を追加</a></li>
            <li><a href="#enjoy">クラス</a></li>
            <li><a href="#recipe">授業</a></li>
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
            <li><a href="#company">教師</a></li>
            <li><a href="#sustainability">照合テスト</a></li>
            <li><a href="#ir">ユーザー名</a></li>
            <li>
                <a href="#corporate" class="company-banner">
                    ログアウト
                    <span>›</span>
                </a>
            </li>
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

        <div class="mobile-header-actions">

            <a href="#search" class="icon-link" aria-label="検索">
                <svg viewBox="0 0 32 32">
                    <circle cx="13.5" cy="13.5" r="9"></circle>
                    <path d="m20 20 8 8"></path>
                </svg>
            </a>

            <a
                href="https://www.instagram.com/"
                class="icon-link instagram-icon"
                aria-label="Instagram"
                target="_blank"
                rel="noopener noreferrer"
            >
                <svg viewBox="0 0 32 32">
                    <rect x="4" y="4" width="24" height="24" rx="7"></rect>
                    <circle cx="16" cy="16" r="6"></circle>
                    <circle cx="23.5" cy="8.8" r="1" fill="currentColor"></circle>
                </svg>
            </a>

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

            <div class="mobile-menu-actions">

                <a href="#search" class="icon-link" aria-label="検索">
                    <svg viewBox="0 0 32 32">
                        <circle cx="13.5" cy="13.5" r="9"></circle>
                        <path d="m20 20 8 8"></path>
                    </svg>
                </a>

                <a
                    href="https://www.instagram.com/"
                    class="icon-link instagram-icon"
                    aria-label="Instagram"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <svg viewBox="0 0 32 32">
                        <rect x="4" y="4" width="24" height="24" rx="7"></rect>
                        <circle cx="16" cy="16" r="6"></circle>
                        <circle cx="23.5" cy="8.8" r="1" fill="currentColor"></circle>
                    </svg>
                </a>

            </div>
        </div>

        <nav aria-label="スマホメニュー">
            <ul class="mobile-menu-links">
                <li><a href="#products">商品一覧</a></li>
                <li><a href="#commitment">こだわり</a></li>
                <li><a href="#enjoy">Enjoyかどや</a></li>
                <li><a href="#recipe">かどやレシピ</a></li>
                <li><a href="#sustainability">サステナビリティ</a></li>
                <li><a href="#company">会社概要</a></li>
                <li><a href="#ir">IR情報</a></li>
            </ul>
        </nav>

        <nav class="mobile-menu-subnav" aria-label="その他のメニュー">
            <a href="#recruit">採用情報</a>
            <a href="#contact">お問い合わせ</a>
            <a href="#english">English</a>
        </nav>

        <div class="mobile-menu-extra">

            <div class="mobile-banner-wrap">

                <a href="#cart" class="icon-link" aria-label="カート">
                    <svg viewBox="0 0 32 32">
                        <path d="M3 5h4l4 16h14l4-12H9"></path>
                        <circle cx="13" cy="26" r="1.5"></circle>
                        <circle cx="24" cy="26" r="1.5"></circle>
                    </svg>
                </a>

                <a href="#corporate" class="company-banner">
                    カドヤ食品株式会社
                    <span>›</span>
                </a>

            </div>

            <form class="mobile-search" id="site-search" role="search">
                <input
                    type="search"
                    name="q"
                    placeholder="サイト内検索"
                    aria-label="サイト内検索"
                >

                <button type="submit" aria-label="検索する">
                    <svg viewBox="0 0 32 32">
                        <circle cx="13.5" cy="13.5" r="9"></circle>
                        <path d="m20 20 8 8"></path>
                    </svg>
                </button>
            </form>

            <p class="follow-us">FOLLOW US</p>

            <div class="social-links">
                <a
                    href="https://www.instagram.com/"
                    class="icon-link instagram-icon"
                    aria-label="Instagram"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <svg viewBox="0 0 32 32">
                        <rect x="4" y="4" width="24" height="24" rx="7"></rect>
                        <circle cx="16" cy="16" r="6"></circle>
                        <circle cx="23.5" cy="8.8" r="1" fill="currentColor"></circle>
                    </svg>
                </a>
            </div>

        </div>

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

            // 検索フォーム
            // 実際の検索URLに合わせて変更してください
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
        });
    </script>

</body>
</html>

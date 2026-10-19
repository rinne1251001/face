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
    @php
            // =========================
            // メニュー定義（ここを編集すれば全ヘッダーに反映されます）
            // =========================

            // メインメニュー（PC左側・スマホメニュー）
            $mainMenu = [
                ['label' => 'home',   'url' => '#'],
                ['label' => '設定', 'url' => '#'],
               
            ];

            // ユーザーメニュー（PC右側・スマホヘッダー）
            // banner => true のものはボタン風（.company-banner）で表示
            $userMenu = [
                ['label' => 'ユーザー名', 'url' => '#ir'],
                ['label' => 'ログアウト', 'url' => '#corporate', 'banner' => true],
            ];
        @endphp
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

    @yield('content')

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

    @stack('scripts')
</body>
</html>
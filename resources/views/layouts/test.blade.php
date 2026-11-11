<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Rollin')</title>
    <meta name="description" content="顔認証出席確認アプリ">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta property="og:site_name" content="Rollin">
    <meta property="og:image" content="{{ asset('images/ogp.png') }}">
    <link rel="icon" href="{{ asset('images/favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">
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
 
    @if (request()->routeIs('admin.*') && auth('admin')->check())
        @php
            // =========================
            // adminのメニュー定義（ここを編集すれば全ヘッダーに反映されます）
            // =========================
 
            // メインメニュー（PC左側・スマホメニュー）
            $mainMenu = [
                ['label' => '生徒',        'url' => route('admin.students.index')],
                ['label' => 'クラス',      'url' => route('admin.classes.index')],
                ['label' => '授業',        'url' => route('admin.subjects.index')],
                ['label' => '教室',        'url' => route('admin.rooms.index')],
                ['label' => 'スケジュール', 'url' => route('admin.schedules.index')],
            ];
 
            // サブメニュー（PC右側・スマホメニュー）
            $subMenu = [
                ['label' => '教師',       'url' => route('admin.teachers.index')],
                ['label' => '照合テスト', 'url' => route('admin.match.show')],
            ];
 
            // ユーザーメニュー（PC右側・スマホヘッダー）
            $userMenu = [
                ['label' => auth('admin')->user()->login_id, 'url' => '#'],
                ['label' => 'ログアウト', 'url' => route('admin.logout'), 'logout' => true],
            ];
        @endphp
    @elseif (request()->routeIs('teacher.*') && auth('teacher')->check())
        @php
            // =========================
            // teacherのメニュー定義（ここを編集すれば全ヘッダーに反映されます）
            // =========================
 
            // メインメニュー（PC左側・スマホメニュー）
            $mainMenu = [
                ['label' => 'home',   'url' => route('teacher.dashboard')],
                ['label' => 'クラス',   'url' => route('teacher.classes.index')],
                ['label' => '分析', 'url' => route('teacher.analyses.index')],
                ['label' => '設定', 'url' => route('teacher.settings.index')],
            ];
 
            // ユーザーメニュー（PC右側・スマホヘッダー）
            $userMenu = [
                ['label' => auth('teacher')->user()->name . ' 先生', 'url' => route('teacher.dashboard')],
                ['label' => 'ログアウト', 'url' => route('teacher.logout'), 'logout' => true],
            ];
        @endphp
    @endif
 
 
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
        <a href="{{ auth('admin')->check() ? '#' : (auth('teacher')->check() ? route('teacher.dashboard') : '#') }}" class="logo" aria-label="トップページ">
            <img src="{{ asset('images/logo.png') }}" alt="Rollin">
        </a>
 
        <!-- 右側のメニュー（横並び） -->
        <nav class="pc-nav main-nav-right">
            <ul>
                @foreach (array_merge($subMenu, $userMenu) as $item)
                    <li>
                        @if ($item['logout'] ?? false)
                            <form method="POST" action="{{ $item['url'] }}">
                                @csrf
                                <button type="submit" class="company-banner">
                                    {{ $item['label'] }}
                                    <span>›</span>
                                </button>
                            </form>
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
 
        <a href="{{ auth('admin')->check() ? '#' : (auth('teacher')->check() ? route('teacher.dashboard') : '#') }}" class="logo logo-small" aria-label="トップページ">
           <img src="{{ asset('images/logo.png') }}" alt="Rollin">
        </a>
 
        <div class="mobile-header-actions pc-nav main-nav-right">
            <ul>
                @foreach ($userMenu as $item)
                    <li>
                        @if ($item['logout'] ?? false)
                            <form method="POST" action="{{ $item['url'] }}">
                                @csrf
                                <button type="submit" class="company-banner">
                                    {{ $item['label'] }}
                                    <span>›</span>
                                </button>
                            </form>
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
        <nav aria-label="スマホメニュー">
            <ul class="mobile-menu-links">
                @foreach (array_merge($mainMenu, $subMenu) as $item)
                    <li><a href="{{ $item['url'] }}">{{ $item['label'] }}</a></li>
                @endforeach
            </ul>
        </nav>
    </div> 

    @yield('content')

    @if (session('status') || session('error'))
        <dialog id="flash-modal" @class(['flash-modal', 'is-error' => session('error')])>
            <div class="flash-modal-content" tabindex="-1" autofocus>
                <form method="dialog">
                    <button class="flash-modal-close" aria-label="閉じる">×</button>
                </form>
                @if (session('status'))
                    <p>{{ session('status') }}</p>
                @endif
                @if (session('error'))
                    <p>{{ session('error') }}</p>
                @endif
            </div>
        </dialog>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const flashModal = document.getElementById('flash-modal');

                // ページを開いたらすぐに表示する（showModal で開くと、背景が暗くなり Esc キーでも閉じられる）
                flashModal.showModal();

                // 背景を押したら閉じる（背景を押すと、中身ではなく dialog 自体がクリックされたことになる）
                flashModal.addEventListener('click', (e) => {
                    if (e.target === flashModal) {
                        flashModal.close();
                    }
                });
            });
        </script>
    @endif
 
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const menu = document.getElementById('mobile-menu');
            const menuToggle = document.getElementById('menu-toggle');
            const menuLinks = menu.querySelectorAll('a');

            let previousFocus = null;

            function openMenu() {
                previousFocus = document.activeElement;

                menu.classList.add('open');
                menu.removeAttribute('inert');
                menu.setAttribute('aria-hidden', 'false');

                menuToggle.classList.add('active');
                menuToggle.setAttribute('aria-expanded', 'true');
                menuToggle.setAttribute('aria-label', 'メニューを閉じる');

                document.body.classList.add('menu-open');
                // フォーカスは開閉ボタン（＝閉じるボタン）に残したままでOK
            }

            function closeMenu(restoreFocus = true) {
                menu.classList.remove('open');
                menu.setAttribute('inert', '');
                menu.setAttribute('aria-hidden', 'true');

                menuToggle.classList.remove('active');
                menuToggle.setAttribute('aria-expanded', 'false');
                menuToggle.setAttribute('aria-label', 'メニューを開く');

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

            // メニュー内のリンクを押したら閉じる
            menuLinks.forEach(link => {
                link.addEventListener('click', () => {
                    closeMenu(false);
                });
            });

            document.addEventListener('keydown', event => {
                if (!menu.classList.contains('open')) return;

                // Escキーで閉じる
                if (event.key === 'Escape') {
                    closeMenu();
                }

                // 開閉ボタン＋メニュー内リンクの中でフォーカスを循環させる
                if (event.key === 'Tab') {
                    const focusable = [menuToggle, ...menuLinks];
                    const index = focusable.indexOf(document.activeElement);

                    event.preventDefault();

                    const next = event.shiftKey
                        ? (index <= 0 ? focusable.length - 1 : index - 1)
                        : (index + 1) % focusable.length;

                    focusable[next].focus();
                }
            });

            // PC幅に変わったらスマホメニューを閉じる
            window.addEventListener('resize', () => {
                if (window.innerWidth >= 768 && menu.classList.contains('open')) {
                    closeMenu(false);
                }
            });
        });
    </script>

    @stack('scripts')

</body>
</html>
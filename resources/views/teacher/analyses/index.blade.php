@extends('layouts.test')
@section('title', '分析一覧')
@push('styles')
    <style>
        .table-container {
            overflow-x: auto;
            max-width: 100%;
        }
        .table-container table {
            border-collapse: collapse;
        }
        .table-container th {
            background-color: var(--color-main);
            color: var(--color-bg);
            border: 1px solid var(--color-bg);
            padding-block: 7px;
            padding-inline: 20px;
        }
        .table-container td {
            color: var(--color-main);
            border: 1px solid var(--color-main);
            padding: 10px 20px;
        }

        /* =========================================================
         折れ線グラフ（CSSのみ）
         - 値はすべて HTML の style 属性（CSS変数）で渡す
         - 線分は「前の点 → この点」を ::before で描画
           角度 = atan2()、長さ = hypot() で計算
         ========================================================= */
        .line-chart {
            /* ---- 設定値（HTML側の style 属性で上書き） ---- */
            --max: 100;     /* Y軸の最大値 */
            --count: 2;     /* データ点の数 */
            --y-grid: 6;    /* 横グリッドの分割数（90 ÷ 6 = 15刻み） */
            --x-grid: 10;   /* 縦グリッドの分割数（1〜11月 = 10区間） */
            --line: 3px;    /* 線の太さ */
            --dot-size: 12px; /* 点の大きさ */
            --grid-color: #e6e6e6;

            max-width: 520px;
            flex: 1 1 520px;
            padding: 36px 32px 40px 60px;   /* 上:値ラベル / 左:Y軸 / 下:X軸 の余白 */
            color: var(--color-font-light);
        }

        .line-chart ul {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        /* 描画エリア：ここを基準（コンテナ）に cqw / cqh で計算する */
        .lc-plot {
            position: relative;
            aspect-ratio: 366 / 250;
            container-type: size;
            border-bottom: 1px solid var(--grid-color);
            background:
                repeating-linear-gradient(to bottom,
                    var(--grid-color) 0 1px, transparent 1px calc(100% / var(--y-grid)));
        }

        /* ---- Y軸ラベル ---- */
        .lc-y {
            position: absolute;
            inset: 0;
        }

        .lc-y li {
            position: absolute;
            right: calc(100% + 20px);
            bottom: calc(var(--t) / var(--max) * 100%);
            translate: 0 50%;
            font-size: 10px;
            line-height: 1;
            white-space: nowrap;
        }

        /* ---- X軸ラベル ---- */
        .lc-x {
            position: absolute;
            inset: 0;
        }

        .lc-x li {
            position: absolute;
            top: calc(100% + 15px);
            left: calc(var(--i) / (var(--count) - 1) * 100%);
            translate: -50% 0;
            font-size: 10px;
            line-height: 1;
            white-space: nowrap;
        }

        /* ---- 系列 ---- */
        .lc-series {
            position: absolute;
            inset: 0;
        }

        /* データ点（幅0・高さ0の基準点） */
        .lc-series li {
            position: absolute;
            left: calc(var(--i) / (var(--count) - 1) * 100%);
            bottom: calc(var(--v) / var(--max) * 100%);
        }

        /* 前の点からこの点までの線分 */
        .lc-series li::before {
            --dx: calc((var(--pi) - var(--i)) * 100cqw / (var(--count) - 1));    /* 1つ前に打った点（--pi 番目）までの横の距離。記録のない月をとばして線を引けるように --pi を使う */
            --dy: calc((var(--v) - var(--pv)) / var(--max) * 100cqh);

            content: "";
            position: absolute;
            left: 0;
            top: calc(var(--line) / -2);
            width: hypot(var(--dx), var(--dy));
            height: var(--line);
            background: var(--color);
            transform-origin: 0 50%;
            rotate: atan2(var(--dy), var(--dx));
        }

        .lc-series li:first-child::before {
            content: none;
        }

        /* 折れ目のすき間を埋める小さな丸 */
        .lc-series li::after {
            content: "";
            position: absolute;
            left: calc(var(--dot-size) / -2);
            top: calc(var(--dot-size) / -2);
            width: var(--dot-size);
            height: var(--dot-size);
            border-radius: 50%;
            background: var(--color);
        }

        /* 値ラベル */
        .lc-series li span {
            position: absolute;
            z-index: 1;
            left: 0;
            bottom: 10px;
            translate: -50% -5%;
            font-size: 12px;
            line-height: 1;
            white-space: nowrap;
            color: var(--color);
            font-weight: 900;
        }

        /* =========================================================
            ドーナツグラフ（CSSのみ / JavaScript不使用）
            - 件数を CSS 変数（--attend など）で渡すだけで描画されます
            - 割合（%）は CSS の calc() で自動計算します
        ========================================================= */
        .donut-chart {
            /* 色 */
            --color-attend:   var(--color-main); /* 出席 */
            --color-late:     color-mix(var(--color-main), var(--color-bg) 15%); /* 遅刻 */
            --color-official: color-mix(var(--color-main), var(--color-bg) 30%); /* 公欠 */
            --color-absent:   var(--color-accent); /* 欠席 */
        
            /* サイズ */
            --size: 305px;       /* 外側の直径 */
            --hole: 50%;         /* 穴の大きさ（直径に対する割合） */
        
            /* 合計と、各区切り位置（累積%）を計算 */
            --total: calc(var(--attend) + var(--late) + var(--official) + var(--absent));
            --stop1: calc(var(--attend) / var(--total) * 100%);
            --stop2: calc((var(--attend) + var(--late)) / var(--total) * 100%);
            --stop3: calc((var(--attend) + var(--late) + var(--official)) / var(--total) * 100%);
        
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 28px;
            max-width: 520px;
            min-width: 0;
            padding-inline: 20px;
        }
        
        /* 円グラフ本体 */
        .donut-chart__ring {
            width: var(--size);
            max-width: 100%;
            aspect-ratio: 1;
            border-radius: 50%;
            /* 12時の位置から時計回りに塗り分け（「0」で前の位置から始める＝境界がくっきり） */
            background: conic-gradient(
            var(--color-attend)   0 var(--stop1),
            var(--color-late)     0 var(--stop2),
            var(--color-official) 0 var(--stop3),
            var(--color-absent)   0
            );
            /* 中央をくり抜いてドーナツにする（背景色に関係なく透明な穴になる） */
            -webkit-mask: radial-gradient(circle closest-side, transparent var(--hole), #000 calc(var(--hole) + 0.5px));
                    mask: radial-gradient(circle closest-side, transparent var(--hole), #000 calc(var(--hole) + 0.5px));
        }
        
        /* 凡例 */
        .donut-chart__legend {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 8px 34px;
            margin: 0;
            padding: 0;
            list-style: none;
            color: #595959;
            font-size: 16px;
        }
        .donut-chart__legend li {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .donut-chart__legend li::before {
            content: "";
            width: 11px;
            height: 11px;
            background: var(--color);
        }
        .donut-chart__legend .is-attend   { --color: var(--color-attend); }
        .donut-chart__legend .is-late     { --color: var(--color-late); }
        .donut-chart__legend .is-official { --color: var(--color-official); }
        .donut-chart__legend .is-absent   { --color: var(--color-absent); }
    </style>
@endpush
@section('content')

    <main class="main">
        <div class="content-wrapper">

            <div style="display: grid; gap: 100px; grid-template-columns: minmax(0, 1fr);">
                <div>
                    @if($classes->isNotEmpty())
                        <div style="display: grid; gap: 40px;">
                            @foreach($classes as $class)
                                @php
                                    $today = $classCharts[$class->id]['today']; // 今日の出席・遅刻・公欠・欠席の数と出席率
                                @endphp
                                <div>
                                    <div class="page-header">
                                        <h1>{{ $class->name }}</h1>
                                        <div>
                                            <a href="{{ route('teacher.analyses.class', $class) }}">詳しく見る</a>
                                        </div>
                                    </div>

                                    <div style="display: grid; row-gap: 40px; grid-template-columns: repeat(auto-fit, minmax(min(300px, 100%), 1fr));">
                                        @include('teacher.analyses.partials.monthly-chart', ['points' => $classCharts[$class->id]['points']])

                                        @if ($today['total'] > 0)
                                            {{-- 件数を CSS 変数で渡すと、割合は CSS が計算して塗り分ける --}}
                                            <figure class="donut-chart" style="--attend: {{ $today['present'] }}; --late: {{ $today['late'] }}; --official: {{ $today['excused'] }}; --absent: {{ $today['absent'] }};">
                                                <div style="color: var(--color-main); font-weight: bold; font-size: 1.4em; transform: translateY(40%);">今日の出席率</div>

                                                <div style="position: relative; max-width: 100%;">
                                                    <div class="donut-chart__ring" role="img" aria-label="出欠の内訳：出席 {{ $today['present'] }}件、遅刻 {{ $today['late'] }}件、公欠 {{ $today['excused'] }}件、欠席 {{ $today['absent'] }}件"></div>
                                                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: var(--color-main); font-weight: bold; display: flex; gap: 5px; align-items: baseline;">
                                                        {{-- 全員が公欠のときは、出席率を出せないので「—」 --}}
                                                        <span style="font-size: 2em;">{{ $today['rate'] ?? '—' }}</span>@if ($today['rate'] !== null)<span style="font-size: 1.5em;">%</span>@endif
                                                    </div>
                                                </div>

                                                <ul class="donut-chart__legend">
                                                    <li class="is-attend">出席</li>
                                                    <li class="is-late">遅刻</li>
                                                    <li class="is-official">公欠</li>
                                                    <li class="is-absent">欠席</li>
                                                </ul>
                                            </figure>
                                        @else
                                            {{-- 今日の授業がまだない（または休み）とき。0件だと円グラフの割合を計算できないので出さない --}}
                                            <figure class="donut-chart">
                                                <div style="color: var(--color-main); font-weight: bold; font-size: 1.4em;">今日の出席率</div>
                                                <p>今日はまだ出席の記録がありません。</p>
                                            </figure>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="page-header">
                            <h1>クラス分析</h1>
                        </div>
                        <p>受け持ちクラスはありません</p>
                    @endif
                </div>

                <div>
                    <div class="page-header">
                        <h1>授業分析</h1>
                    </div>
                    @if($subjects->isNotEmpty())
                        <div style="display: grid; row-gap: 40px; grid-template-columns: repeat(auto-fit, minmax(min(300px, 100%), 1fr));">
                            @foreach($subjects as $subject)
                                <div style="min-width: 0;">
                                    <div>
                                        <a href="{{ route('teacher.analyses.subject', $subject) }}">{{ $subject->name }}</a>
                                        <span style="font-size: 0.85em;">（{{ $subject->schedule_label }}）</span>
                                    </div>

                                    <div>
                                        @include('teacher.analyses.partials.monthly-chart', ['points' => $subjectCharts[$subject->id]])
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p>受け持ち授業はありません</p>
                    @endif
                </div>
            </div>

            @include('teacher.analyses.partials.note')

        </div>
    </main>

@endsection
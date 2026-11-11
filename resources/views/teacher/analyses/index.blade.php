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
            --dx: calc(-100cqw / (var(--count) - 1));
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
                                <div>
                                    <div class="page-header">
                                        <h1>{{ $class->name }}</h1>
                                        <div>
                                            <a href="{{ route('teacher.analyses.class', $class) }}">詳しく見る</a>
                                        </div>
                                    </div>

                                    <div style="display: grid; row-gap: 40px; grid-template-columns: repeat(auto-fit, minmax(min(300px, 100%), 1fr)); padding-right: 20px;">
                                        <figure class="line-chart" style="--max: 100; --count: 12; --y-grid: 5; --x-grid: 11;" aria-label="月別推移グラフ">
                                            <div style="color: var(--color-main); font-weight: bold; font-size: 1.4em; transform: translateY(-20px);">月別出席率</div>
                                            <div class="lc-plot">

                                                <ul class="lc-y" aria-hidden="true">
                                                    <li style="--t: 20">20%</li>
                                                    <li style="--t: 40">40%</li>
                                                    <li style="--t: 60">60%</li>
                                                    <li style="--t: 80">80%</li>
                                                    <li style="--t: 100">100%</li>
                                                </ul>

                                                <ul class="lc-x">
                                                    <li style="--i: 0">4月</li>
                                                    <li style="--i: 1">5月</li>
                                                    <li style="--i: 2">6月</li>
                                                    <li style="--i: 3">7月</li>
                                                    <li style="--i: 4">8月</li>
                                                    <li style="--i: 5">9月</li>
                                                    <li style="--i: 6">10月</li>
                                                    <li style="--i: 7">11月</li>
                                                    <li style="--i: 8">12月</li>
                                                    <li style="--i: 9">1月</li>
                                                    <li style="--i: 10">2月</li>
                                                    <li style="--i: 11">3月</li>
                                                </ul>

                                                <!-- 青の系列 -->
                                                <ul class="lc-series" style="--color: var(--color-main)" aria-label="系列A">
                                                    <li style="--i: 0;  --v: 14; --pv: 14"><span>14%</span></li>
                                                    <li style="--i: 1;  --v: 27; --pv: 14"><span>27%</span></li>
                                                    <li style="--i: 2;  --v: 41; --pv: 27"><span>41%</span></li>
                                                    <li style="--i: 3;  --v: 35; --pv: 41"><span>35%</span></li>
                                                    <li style="--i: 4;  --v: 30; --pv: 35"><span>30%</span></li>
                                                    <li style="--i: 5;  --v: 43; --pv: 30"><span>43%</span></li>
                                                    <li style="--i: 6;  --v: 54; --pv: 43"><span>54%</span></li>
                                                    <li style="--i: 7;  --v: 62; --pv: 54"><span>62%</span></li>
                                                    <li style="--i: 8;  --v: 69; --pv: 62"><span>69%</span></li>
                                                    <li style="--i: 9;  --v: 64; --pv: 69"><span>64%</span></li>
                                                    <li style="--i: 10; --v: 58; --pv: 64"><span>58%</span></li>
                                                    <li style="--i: 11; --v: 60; --pv: 58"><span>60%</span></li>
                                                </ul>

                                            </div>
                                        </figure>

                                        <figure class="donut-chart" style="--attend: 112; --late: 50; --official: 14; --absent: 24;">
                                            <div style="color: var(--color-main); font-weight: bold; font-size: 1.4em; transform: translateY(40%);">今日の出席率</div>

                                            <div style="position: relative; max-width: 100%;">
                                                <div class="donut-chart__ring" role="img" aria-label="出欠の内訳：出席 112件、遅刻 50件、公欠 14件、欠席 24件"></div>
                                                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: var(--color-main); font-weight: bold; display: flex; gap: 5px; align-items: baseline;"><span style="font-size: 2em;">56</span><span style="font-size: 1.5em;">%</span></div>
                                            </div>
                                        
                                            <ul class="donut-chart__legend">
                                            <li class="is-attend">出席</li>
                                            <li class="is-late">遅刻</li>
                                            <li class="is-official">公欠</li>
                                            <li class="is-absent">欠席</li>
                                            </ul>
                                        </figure>
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
                                    </div>

                                    <div>
                                        <figure class="line-chart" style="--max: 100; --count: 12; --y-grid: 5; --x-grid: 11;" aria-label="月別推移グラフ">
                                            <div style="color: var(--color-main); font-weight: bold; font-size: 1.4em; transform: translateY(-20px);">月別出席率</div>
                                            <div class="lc-plot">

                                                <ul class="lc-y" aria-hidden="true">
                                                    <li style="--t: 20">20%</li>
                                                    <li style="--t: 40">40%</li>
                                                    <li style="--t: 60">60%</li>
                                                    <li style="--t: 80">80%</li>
                                                    <li style="--t: 100">100%</li>
                                                </ul>

                                                <ul class="lc-x">
                                                    <li style="--i: 0">4月</li>
                                                    <li style="--i: 1">5月</li>
                                                    <li style="--i: 2">6月</li>
                                                    <li style="--i: 3">7月</li>
                                                    <li style="--i: 4">8月</li>
                                                    <li style="--i: 5">9月</li>
                                                    <li style="--i: 6">10月</li>
                                                    <li style="--i: 7">11月</li>
                                                    <li style="--i: 8">12月</li>
                                                    <li style="--i: 9">1<span>月</span></li>
                                                    <li style="--i: 10">2月</li>
                                                    <li style="--i: 11">3月</li>
                                                </ul>

                                                <!-- 青の系列 -->
                                                <ul class="lc-series" style="--color: var(--color-main)" aria-label="系列A">
                                                    <li style="--i: 0;  --v: 14; --pv: 14"><span>14%</span></li>
                                                    <li style="--i: 1;  --v: 27; --pv: 14"><span>27%</span></li>
                                                    <li style="--i: 2;  --v: 41; --pv: 27"><span>41%</span></li>
                                                    <li style="--i: 3;  --v: 35; --pv: 41"><span>35%</span></li>
                                                    <li style="--i: 4;  --v: 30; --pv: 35"><span>30%</span></li>
                                                    <li style="--i: 5;  --v: 43; --pv: 30"><span>43%</span></li>
                                                    <li style="--i: 6;  --v: 54; --pv: 43"><span>54%</span></li>
                                                    <li style="--i: 7;  --v: 62; --pv: 54"><span>62%</span></li>
                                                    <li style="--i: 8;  --v: 69; --pv: 62"><span>69%</span></li>
                                                    <li style="--i: 9;  --v: 64; --pv: 69"><span>64%</span></li>
                                                    <li style="--i: 10; --v: 58; --pv: 64"><span>58%</span></li>
                                                    <li style="--i: 11; --v: 60; --pv: 58"><span>60%</span></li>
                                                </ul>

                                            </div>
                                        </figure>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p>受け持ち授業はありません</p>
                    @endif
                </div>
            </div>

        </div>
    </main>

@endsection
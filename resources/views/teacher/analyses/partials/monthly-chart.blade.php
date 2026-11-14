{{-- 月別の出席率の折れ線グラフ（CSS は一覧の画面の .line-chart）
     $points … AttendanceStats::monthlyPoints() の戻り値（[['label' => '4月', 'rate' => 92], ['label' => '5月', 'rate' => null], ...]）
     記録がない月（夏休みや、まだ来ていない月）は点を打たず、その前後の点を線でつなぐ --}}
@php
    $count = count($points);
    $hasData = collect($points)->contains(fn ($point) => $point['rate'] !== null);
    $previous = null; // 1つ前に打った点（線をその点から引く）
@endphp
<figure class="line-chart" style="--max: 100; --count: {{ $count }}; --y-grid: 5; --x-grid: {{ $count - 1 }};" aria-label="月別の出席率の推移">
    <div style="color: var(--color-main); font-weight: bold; font-size: 1.4em; transform: translateY(-20px);">月別出席率</div>
    <div class="lc-plot">

        <ul class="lc-y" aria-hidden="true">
            @foreach ([20, 40, 60, 80, 100] as $tick)
                <li style="--t: {{ $tick }}">{{ $tick }}%</li>
            @endforeach
        </ul>

        <ul class="lc-x">
            @foreach ($points as $i => $point)
                <li style="--i: {{ $i }}">{{ $point['label'] }}</li>
            @endforeach
        </ul>

        {{-- --pi・--pv … 1つ前に打った点の位置と値（最初の点は自分と同じ） --}}
        <ul class="lc-series" style="--color: var(--color-main)" aria-label="出席率">
            @foreach ($points as $i => $point)
                @continue($point['rate'] === null)
                <li style="--i: {{ $i }}; --v: {{ $point['rate'] }}; --pi: {{ $previous['i'] ?? $i }}; --pv: {{ $previous['rate'] ?? $point['rate'] }}"><span>{{ $point['rate'] }}%</span></li>
                @php $previous = ['i' => $i, 'rate' => $point['rate']]; @endphp
            @endforeach
        </ul>

    </div>
    @unless ($hasData)
        <figcaption style="margin-top: 40px;">まだ出席の記録がありません。</figcaption>
    @endunless
</figure>
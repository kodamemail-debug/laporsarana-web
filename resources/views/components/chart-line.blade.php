@props(['labels', 'values'])
@php
    $n = count($values);
    $W = 420; $H = 300; $L = 34; $R = 16; $T = 24;
    $B = $n > 5 ? 80 : 50;
    $max = max(max($values ?: [0]), 1);
    $step = max(1, (int) ceil($max / 4));
    $top = $step * 4;
    $plotW = $W - $L - $R;
    $plotH = $H - $T - $B;
    $base = $T + $plotH;
    $titik = [];
    foreach ($values as $i => $v) {
        $x = $n > 1 ? $L + 12 + ($plotW - 24) * $i / ($n - 1) : $L + $plotW / 2;
        $y = $base - $plotH * $v / $top;
        $titik[] = [round($x, 1), round($y, 1), $v, $labels[$i]];
    }
    $garis = implode(' ', array_map(fn ($p) => $p[0] . ',' . $p[1], $titik));
    $area = $n > 1
        ? $titik[0][0] . ',' . $base . ' ' . $garis . ' ' . $titik[$n - 1][0] . ',' . $base
        : '';
@endphp
<svg class="chart" viewBox="0 0 {{ $W }} {{ $H }}" role="img" aria-label="Grafik garis">
    @for ($i = 0; $i <= 4; $i++)
        @php $y = round($base - ($plotH * $i / 4), 1); @endphp
        <line class="grid" x1="{{ $L }}" x2="{{ $W - $R }}" y1="{{ $y }}" y2="{{ $y }}" />
        <text x="{{ $L - 7 }}" y="{{ $y + 4 }}" text-anchor="end">{{ $step * $i }}</text>
    @endfor
    <line class="axis" x1="{{ $L }}" x2="{{ $W - $R }}" y1="{{ $base }}" y2="{{ $base }}" />

    @if ($n > 1)
        <polygon class="area" points="{{ $area }}" />
        <polyline class="line" points="{{ $garis }}" />
    @endif

    @foreach ($titik as [$x, $y, $v, $lab])
        <circle class="dot" cx="{{ $x }}" cy="{{ $y }}" r="4.5" />
        <text class="val" x="{{ $x }}" y="{{ $y - 10 }}" text-anchor="middle">{{ $v }}</text>
        @if ($n > 5)
            <text x="{{ $x }}" y="{{ $base + 16 }}" text-anchor="end" transform="rotate(-38 {{ $x }} {{ $base + 16 }})">{{ $lab }}</text>
        @else
            <text x="{{ $x }}" y="{{ $base + 22 }}" text-anchor="middle">{{ $lab }}</text>
        @endif
    @endforeach
</svg>

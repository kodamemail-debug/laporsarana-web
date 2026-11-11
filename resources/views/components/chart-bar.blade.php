@props(['labels', 'values', 'classes' => []])
@php
    $n = count($values);
    $W = 420; $H = 300; $R = 10; $T = 24;
    $L = $n > 5 ? 60 : 34;
    $B = $n > 5 ? 96 : 50;
    $max = max(max($values ?: [0]), 1);
    $step = max(1, (int) ceil($max / 4));
    $top = $step * 4;
    $plotW = $W - $L - $R;
    $plotH = $H - $T - $B;
    $slot = $n > 0 ? $plotW / $n : $plotW;
    $bw = min(48, $slot * 0.62);
    $base = $T + $plotH;
@endphp
<svg class="chart" viewBox="0 0 {{ $W }} {{ $H }}" role="img" aria-label="Grafik batang">
    @for ($i = 0; $i <= 4; $i++)
        @php $y = round($base - ($plotH * $i / 4), 1); @endphp
        <line class="grid" x1="{{ $L }}" x2="{{ $W - $R }}" y1="{{ $y }}" y2="{{ $y }}" />
        <text x="{{ $L - 7 }}" y="{{ $y + 4 }}" text-anchor="end">{{ $step * $i }}</text>
    @endfor

    @foreach ($values as $i => $v)
        @php
            $h = round($plotH * $v / $top, 1);
            $x = round($L + $slot * $i + ($slot - $bw) / 2, 1);
            $y = round($base - $h, 1);
            $cx = round($x + $bw / 2, 1);
            $teks = \Illuminate\Support\Str::limit($labels[$i], 14, '…');
        @endphp
        <rect class="col {{ $classes[$i] ?? '' }}" x="{{ $x }}" y="{{ $y }}" width="{{ round($bw, 1) }}" height="{{ $h }}" rx="3" />
        <text class="val" x="{{ $cx }}" y="{{ $y - 6 }}" text-anchor="middle">{{ $v }}</text>
        @if ($n > 5)
            <text x="{{ $cx }}" y="{{ $base + 16 }}" text-anchor="end" transform="rotate(-38 {{ $cx }} {{ $base + 16 }})">{{ $teks }}</text>
        @else
            <text x="{{ $cx }}" y="{{ $base + 22 }}" text-anchor="middle">{{ $teks }}</text>
        @endif
    @endforeach

    <line class="axis" x1="{{ $L }}" x2="{{ $W - $R }}" y1="{{ $base }}" y2="{{ $base }}" />
</svg>

@props(['user', 'size' => 36])
<span class="avatar" style="width: {{ $size }}px; height: {{ $size }}px; font-size: {{ (int) round($size * 0.38) }}px;">
    @if ($user->foto_url)
        <img src="{{ $user->foto_url }}" alt="">
    @else
        {{ $user->inisial }}
    @endif
</span>

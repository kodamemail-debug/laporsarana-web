@props(['status'])
@php
    $langkah = ['Menunggu', 'Diverifikasi', 'Dikerjakan', 'Selesai'];
    $posisi = array_search($status, $langkah, true);
    $posisi = $posisi === false ? -1 : $posisi;
@endphp
@if ($status === 'Ditolak')
    <div class="stepper-tolak">
        <x-icon name="x" :size="18" />
        Laporan ini ditolak oleh admin
    </div>
@else
    <ol class="stepper">
        @foreach ($langkah as $i => $nama)
            @php
                $kelas = $status === 'Selesai' || $i < $posisi ? 'done' : ($i === $posisi ? 'now' : '');
            @endphp
            <li class="{{ $kelas }}">
                <span class="dot">
                    @if ($kelas === 'done')
                        <x-icon name="check" :size="14" />
                    @else
                        {{ $i + 1 }}
                    @endif
                </span>
                <span class="lbl">{{ $nama }}</span>
            </li>
        @endforeach
    </ol>
@endif

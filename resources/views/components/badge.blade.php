@props(['status'])
@php
    $kelas = match ($status) {
        'Menunggu', 'Ditugaskan' => 'st-menunggu',
        'Diverifikasi' => 'st-verif',
        'Dikerjakan' => 'st-kerja',
        'Selesai' => 'st-selesai',
        'Ditolak' => 'st-tolak',
        default => 'st-menunggu',
    };
@endphp
<span class="badge {{ $kelas }}">{{ $status }}</span>

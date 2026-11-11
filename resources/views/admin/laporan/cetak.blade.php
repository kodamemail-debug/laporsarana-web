<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Pengaduan Sarana dan Prasarana</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ @filemtime(public_path('css/app.css')) }}">
</head>
<body class="print-page">
@php
    $kelasStatus = ['Menunggu' => 'k-menunggu', 'Diverifikasi' => 'k-verif', 'Dikerjakan' => 'k-kerja', 'Selesai' => 'k-selesai', 'Ditolak' => 'k-tolak'];
    $fmt = fn ($tgl) => $tgl ? \Carbon\Carbon::parse($tgl)->translatedFormat('d F Y') : null;
    $periode = ($filter['dari'] ?? null) || ($filter['sampai'] ?? null)
        ? ($fmt($filter['dari'] ?? null) ?: 'awal') . ' s.d. ' . ($fmt($filter['sampai'] ?? null) ?: 'sekarang')
        : 'Semua tanggal';
@endphp

<div class="print-bar">
    <span>Pratinjau cetak. Di jendela cetak, pilih <strong>Simpan sebagai PDF</strong> pada kolom Printer.</span>
    <span style="display:flex; gap:8px;">
        <a href="{{ route('admin.laporan.index', request()->except('auto')) }}" class="btn btn-outline">Kembali</a>
        <button type="button" class="btn" onclick="window.print()">Cetak / Simpan PDF</button>
    </span>
</div>

<div class="sheet">
    <div class="kop">
        <img src="{{ asset('img/logo-mark.png') }}" alt="">
        <div>
            <h1>Laporan Pengaduan Sarana dan Prasarana</h1>
            <p>SMK Negeri 5 Surakarta &middot; Sistem LaporSarana</p>
        </div>
    </div>

    <div class="meta">
        <div><b>Periode</b>: {{ $periode }}</div>
        <div><b>Status</b>: {{ $filter['status'] ?? '' ?: 'Semua' }} &nbsp;&middot;&nbsp; <b>Kategori</b>: {{ $filter['kategori'] ?? '' ?: 'Semua' }}</div>
        <div><b>Dicetak</b>: {{ now()->translatedFormat('d F Y, H:i') }} WIB oleh {{ auth()->user()->name }}</div>
    </div>

    <div class="sum">
        <div><b>{{ $total }}</b><span>Jumlah laporan</span></div>
        <div><b>{{ $perStatus['Selesai'] ?? 0 }}</b><span>Selesai</span></div>
        <div>
            <b>
                @if ($rataJam === null) &mdash;
                @elseif ($rataJam >= 48) {{ number_format($rataJam / 24, 1, ',', '.') }} hari
                @else {{ number_format($rataJam, 0, ',', '.') }} jam
                @endif
            </b>
            <span>Rata-rata penyelesaian</span>
        </div>
        <div><b>{{ $rataRating !== null ? number_format($rataRating, 1, ',', '.') : '—' }}</b><span>Rata-rata penilaian (1 sampai 5)</span></div>
    </div>

    @if ($total > 0)
        <div class="charts">
            <div class="box">
                <h3>Laporan per status</h3>
                <x-chart-bar :labels="array_keys($perStatus)" :values="array_values($perStatus)" :classes="array_values($kelasStatus)" />
            </div>
            <div class="box">
                <h3>Laporan per kategori</h3>
                <x-chart-bar :labels="array_keys($perKategori)" :values="array_values($perKategori)" />
            </div>
        </div>
        @if (count($trenNilai) > 0)
            <div class="charts" style="margin-top: 16px;">
                <div class="box">
                    <h3>Tren laporan per bulan</h3>
                    <x-chart-line :labels="$trenLabel" :values="$trenNilai" />
                </div>
                <div></div>
            </div>
        @endif
    @endif

    <h2>Rincian laporan @if ($pengaduan->count() >= 1000)<small>(1.000 data terbaru)</small>@endif</h2>
    <table>
        <thead>
            <tr>
                <th style="width:28px;">No</th><th>Kode</th><th>Dilaporkan (WIB)</th><th>Pelapor</th><th>Kategori</th>
                <th>Judul dan lokasi</th><th>Status</th><th>Teknisi</th><th>Selesai (WIB)</th><th>Nilai</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pengaduan as $i => $p)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $p->kode_tiket }}</td>
                    <td>{{ $p->created_at->translatedFormat('d M Y') }}<br>{{ $p->created_at->format('H:i') }}</td>
                    <td>{{ $p->pelapor?->name }}<br>{{ $p->pelapor?->jenis_pengguna }}</td>
                    <td>{{ $p->kategori }}</td>
                    <td>{{ $p->judul }}<br><i>{{ $p->lokasi }}</i></td>
                    <td>{{ $p->status }}</td>
                    <td>{{ $p->penugasan->map(fn ($t) => $t->teknisi?->name)->filter()->implode(', ') ?: '—' }}</td>
                    <td>@if ($p->tanggal_selesai){{ $p->tanggal_selesai->translatedFormat('d M Y') }}<br>{{ $p->tanggal_selesai->format('H:i') }}@else — @endif</td>
                    <td>{{ $p->rating ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="10" style="text-align:center;">Tidak ada data pada penyaringan ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="sign">
        <div>
            Surakarta, {{ now()->translatedFormat('d F Y') }}<br>
            Kepala Sarana dan Prasarana
            <div class="gap"></div>
            ( ............................................ )
        </div>
    </div>
</div>

@if ($auto)
    <script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 500); });</script>
@endif
</body>
</html>

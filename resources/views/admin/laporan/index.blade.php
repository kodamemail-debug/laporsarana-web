@extends('layouts.app')

@section('title', 'Rekap Laporan')

@section('content')
@php
    $kelasStatus = ['Menunggu' => 'k-menunggu', 'Diverifikasi' => 'k-verif', 'Dikerjakan' => 'k-kerja', 'Selesai' => 'k-selesai', 'Ditolak' => 'k-tolak'];
@endphp

<div class="page-head">
    <div>
        <div class="eyebrow">Laporan</div>
        <h1>Rekap Pengaduan</h1>
        <p>Saring berdasarkan tanggal, status, atau kategori. Hasilnya bisa diunduh sebagai CSV atau dicetak menjadi PDF.</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.laporan.cetak', array_merge(request()->query(), ['auto' => 1])) }}" target="_blank" rel="noopener" class="btn">
            <x-icon name="file" :size="16" /> Cetak / PDF
        </a>
        <a href="{{ route('admin.laporan.export', request()->query()) }}" class="btn btn-outline">
            <x-icon name="download" :size="16" /> Unduh CSV
        </a>
    </div>
</div>

<div class="card" style="margin-bottom: 18px;">
    <form method="GET" action="{{ route('admin.laporan.index') }}" class="toolbar">
        <div>
            <label class="small muted" for="dari" style="display:block;">Dari tanggal</label>
            <input type="date" id="dari" name="dari" value="{{ $filter['dari'] ?? '' }}">
        </div>
        <div>
            <label class="small muted" for="sampai" style="display:block;">Sampai tanggal</label>
            <input type="date" id="sampai" name="sampai" value="{{ $filter['sampai'] ?? '' }}">
        </div>
        <div>
            <label class="small muted" for="status" style="display:block;">Status</label>
            <select id="status" name="status">
                <option value="">Semua</option>
                @foreach (\App\Models\Pengaduan::STATUS as $s)
                    <option value="{{ $s }}" @selected(($filter['status'] ?? '') === $s)>{{ $s }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="small muted" for="kategori" style="display:block;">Kategori</label>
            <select id="kategori" name="kategori">
                <option value="">Semua</option>
                @foreach (\App\Models\Pengaduan::KATEGORI as $k)
                    <option value="{{ $k }}" @selected(($filter['kategori'] ?? '') === $k)>{{ $k }}</option>
                @endforeach
            </select>
        </div>
        <div style="align-self:flex-end; display:flex; gap:8px;">
            <button type="submit" class="btn btn-outline">Terapkan</button>
            <a href="{{ route('admin.laporan.index') }}" class="btn btn-ghost">Atur ulang</a>
        </div>
    </form>
</div>

<div class="stats">
    <div class="stat s-total"><div class="lbl">Jumlah laporan</div><div class="num">{{ $total }}</div><div class="hint">Sesuai penyaringan</div></div>
    <div class="stat s-selesai"><div class="lbl">Selesai</div><div class="num">{{ $perStatus['Selesai'] ?? 0 }}</div><div class="hint">Sudah tuntas</div></div>
    <div class="stat s-proses">
        <div class="lbl">Rata-rata penyelesaian</div>
        <div class="num">
            @if ($rataJam === null) &mdash;
            @elseif ($rataJam >= 48) {{ number_format($rataJam / 24, 1, ',', '.') }}<span style="font-size:16px;"> hari</span>
            @else {{ number_format($rataJam, 0, ',', '.') }}<span style="font-size:16px;"> jam</span>
            @endif
        </div>
        <div class="hint">Dari laporan masuk sampai selesai</div>
    </div>
    <div class="stat"><div class="lbl">Rata-rata penilaian</div><div class="num">{{ $rataRating !== null ? number_format($rataRating, 1, ',', '.') : '—' }}</div><div class="hint">Skala 1 sampai 5</div></div>
</div>

<div class="grid grid-3" style="margin-bottom: 18px;">
    <div class="card">
        <div class="card-head"><h2>Laporan per status</h2></div>
        <div class="card-body">
            @if ($total === 0)
                <div class="chart-empty">Belum ada data.</div>
            @else
                <x-chart-bar :labels="array_keys($perStatus)" :values="array_values($perStatus)" :classes="array_values($kelasStatus)" />
            @endif
        </div>
    </div>
    <div class="card">
        <div class="card-head"><h2>Laporan per kategori</h2></div>
        <div class="card-body">
            @if ($total === 0)
                <div class="chart-empty">Belum ada data.</div>
            @else
                <x-chart-bar :labels="array_keys($perKategori)" :values="array_values($perKategori)" />
            @endif
        </div>
    </div>
    <div class="card">
        <div class="card-head"><h2>Tren per bulan</h2></div>
        <div class="card-body">
            @if (count($trenNilai) === 0)
                <div class="chart-empty">Belum ada data.</div>
            @else
                <x-chart-line :labels="$trenLabel" :values="$trenNilai" />
            @endif
        </div>
    </div>
</div>

<div class="card">
    <div class="card-head"><h2>Rincian laporan</h2><span class="sub">Waktu dalam WIB</span></div>
    @if ($pengaduan->isEmpty())
        <div class="empty">Tidak ada laporan pada penyaringan ini.</div>
    @else
        <div class="table-wrap">
            <table class="table table-cards">
                <thead><tr><th>Laporan</th><th>Pelapor</th><th>Kategori</th><th>Status</th><th>Teknisi</th><th>Dilaporkan</th><th>Selesai</th></tr></thead>
                <tbody>
                    @foreach ($pengaduan as $p)
                        <tr>
                            <td data-label="Laporan">
                                <a class="title" href="{{ route('pengaduan.show', $p) }}">{{ $p->judul }}</a>
                                <span class="code mono">{{ $p->kode_tiket }} &middot; {{ $p->lokasi }}</span>
                            </td>
                            <td data-label="Pelapor">{{ $p->pelapor?->name }}</td>
                            <td data-label="Kategori">{{ $p->kategori }}</td>
                            <td data-label="Status"><x-badge :status="$p->status" /></td>
                            <td data-label="Teknisi">{{ $p->penugasan->map(fn ($t) => $t->teknisi?->name)->filter()->implode(', ') ?: '—' }}</td>
                            <td data-label="Dilaporkan"><span class="dt">{{ $p->created_at->translatedFormat('d M Y') }}<small>{{ $p->created_at->format('H:i') }} WIB</small></span></td>
                            <td data-label="Selesai">
                                @if ($p->tanggal_selesai)
                                    <span class="dt">{{ $p->tanggal_selesai->translatedFormat('d M Y') }}<small>{{ $p->tanggal_selesai->format('H:i') }} WIB</small></span>
                                @else
                                    <span class="muted">&mdash;</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $pengaduan->links() }}
    @endif
</div>
@endsection

@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
    $teknisi = $user->isTeknisi();
    $kartu = [
        ['total', $teknisi ? 'Total tugas' : 'Total laporan', 's-total', $teknisi ? 'Seluruh penugasan' : ($user->isAdmin() ? 'Dari semua pelapor' : 'Yang pernah kamu kirim')],
        ['menunggu', $teknisi ? 'Belum dimulai' : ($user->isAdmin() ? 'Perlu verifikasi' : 'Menunggu'), 's-menunggu', $teknisi ? 'Status Ditugaskan' : 'Belum diverifikasi'],
        ['proses', $teknisi ? 'Dikerjakan' : 'Dalam proses', 's-proses', $teknisi ? 'Sedang kamu tangani' : 'Diverifikasi atau dikerjakan'],
        ['selesai', 'Selesai', 's-selesai', 'Sudah tuntas'],
    ];
@endphp

<div class="page-head">
    <div>
        <div class="eyebrow">{{ now()->translatedFormat('l, d F Y') }}</div>
        <h1>Halo, {{ \Illuminate\Support\Str::words($user->name, 2, '') }}</h1>
        <p>
            @if ($user->isAdmin())
                Ringkasan seluruh pengaduan sarana sekolah.
            @elseif ($teknisi)
                Ringkasan tugas perbaikan yang diberikan kepadamu.
            @else
                Ringkasan laporan kerusakan yang pernah kamu kirim.
            @endif
        </p>
    </div>
</div>

<div class="stats">
    @foreach ($kartu as [$kunci, $label, $kelas, $hint])
        <div class="stat {{ $kelas }}">
            <div class="lbl">{{ $label }}</div>
            <div class="num" data-stat="{{ $kunci }}">{{ $angka[$kunci] }}</div>
            <div class="hint">{{ $hint }}</div>
        </div>
    @endforeach
</div>

{{-- ============ PELAPOR ============ --}}
@if ($user->isPelapor())
    <div class="grid grid-main">
        <div class="stack">
            <div class="card">
                <div class="card-head">
                    <h2>Laporan yang sedang berjalan</h2>
                    <span class="sub">Posisi terkini</span>
                </div>
                @forelse ($aktif as $p)
                    <div class="card-body" style="border-bottom: 1px solid var(--line);">
                        <div style="display:flex; justify-content:space-between; gap:12px; margin-bottom:18px; flex-wrap:wrap;">
                            <div>
                                <a href="{{ route('pengaduan.show', $p) }}" style="font-weight:600; color:var(--ink);">{{ $p->judul }}</a>
                                <div class="sub muted small">{{ $p->kode_tiket }} &middot; {{ $p->lokasi }}</div>
                            </div>
                            <x-badge :status="$p->status" />
                        </div>
                        <x-stepper :status="$p->status" />
                    </div>
                @empty
                    <div class="empty">
                        <strong>Tidak ada laporan yang sedang berjalan</strong>
                        Laporan yang baru dikirim akan tampil di sini beserta posisinya.
                    </div>
                @endforelse
            </div>

            <div class="card">
                <div class="card-head"><h2>Riwayat terbaru</h2></div>
                @if ($terbaru->isEmpty())
                    <div class="empty">Belum ada laporan.</div>
                @else
                    <div class="table-wrap">
                        <table class="table table-cards">
                            <thead><tr><th>Laporan</th><th>Kategori</th><th>Status</th><th>Tanggal</th></tr></thead>
                            <tbody>
                                @foreach ($terbaru as $p)
                                    <tr>
                                        <td data-label="Laporan">
                                            <a class="title" href="{{ route('pengaduan.show', $p) }}">{{ $p->judul }}</a>
                                            <span class="code mono">{{ $p->kode_tiket }}</span>
                                        </td>
                                        <td data-label="Kategori">{{ $p->kategori }}</td>
                                        <td data-label="Status"><x-badge :status="$p->status" /></td>
                                        <td data-label="Tanggal"><span class="dt">{{ $p->created_at->translatedFormat('d M Y') }}<small>{{ $p->created_at->format('H:i') }} WIB</small></span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        @include('dashboard-notifikasi')
    </div>
@endif

{{-- ============ ADMIN ============ --}}
@if ($user->isAdmin())
    @if ($tanpaTeknisi > 0)
        <div class="alert alert-warn" style="margin-bottom: 18px;">
            <x-icon name="alert" :size="18" />
            <span>{{ $tanpaTeknisi }} laporan yang sedang diproses belum memiliki teknisi. Buka laporannya untuk menugaskan teknisi.</span>
        </div>
    @endif

    <div class="grid grid-main">
        <div class="stack">
            <div class="card">
                <div class="card-head">
                    <h2>Menunggu verifikasi</h2>
                    <span class="sub">Terlama lebih dulu</span>
                </div>
                @if ($antrian->isEmpty())
                    <div class="empty"><strong>Antrian kosong</strong>Semua laporan masuk sudah diperiksa.</div>
                @else
                    <div class="table-wrap">
                        <table class="table table-cards">
                            <thead><tr><th>Laporan</th><th>Pelapor</th><th>Masuk</th></tr></thead>
                            <tbody>
                                @foreach ($antrian as $p)
                                    <tr>
                                        <td data-label="Laporan">
                                            <a class="title" href="{{ route('pengaduan.show', $p) }}">{{ $p->judul }}</a>
                                            <span class="sub">{{ $p->kategori }} &middot; {{ $p->lokasi }}</span>
                                        </td>
                                        <td data-label="Pelapor">{{ $p->pelapor?->name }}</td>
                                        <td class="nowrap" data-label="Masuk">{{ $p->created_at->diffForHumans() }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="card">
                <div class="card-head"><h2>Laporan per kategori</h2></div>
                <div class="card-body">
                    @if ($perKategori->isEmpty())
                        <span class="muted">Belum ada data.</span>
                    @else
                        @php $maks = max($perKategori->max(), 1); @endphp
                        <div class="bars">
                            @foreach ($perKategori as $nama => $n)
                                <div class="bar-row">
                                    <div class="top"><span>{{ $nama }}</span><strong class="mono">{{ $n }}</strong></div>
                                    <div class="bar"><i style="width: {{ round($n / $maks * 100) }}%"></i></div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="stack">
            <div class="card">
                <div class="card-head"><h2>Beban teknisi</h2><span class="sub">Tugas aktif</span></div>
                @if ($bebanTeknisi->isEmpty())
                    <div class="empty">Belum ada teknisi aktif.</div>
                @else
                    <ul class="list">
                        @foreach ($bebanTeknisi as $t)
                            <li>
                                <div class="person">
                                    <x-avatar :user="$t" :size="34" />
                                    <div class="info">
                                        <strong>{{ $t->name }}</strong>
                                        <span class="sub">{{ $t->keahlian ?: 'Umum' }}</span>
                                    </div>
                                    <span class="tag mono">{{ $t->tugas_aktif }} tugas</span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            @include('dashboard-notifikasi')
        </div>
    </div>
@endif

{{-- ============ TEKNISI ============ --}}
@if ($teknisi)
    <div class="grid grid-main">
        <div class="card">
            <div class="card-head"><h2>Tugas yang perlu dikerjakan</h2></div>
            @if ($tugasAktif->isEmpty())
                <div class="empty"><strong>Tidak ada tugas aktif</strong>Tugas baru dari admin akan muncul di sini.</div>
            @else
                <div class="table-wrap">
                    <table class="table table-cards">
                        <thead><tr><th>Laporan</th><th>Lokasi</th><th>Status</th></tr></thead>
                        <tbody>
                            @foreach ($tugasAktif as $t)
                                <tr>
                                    <td data-label="Laporan">
                                        <a class="title" href="{{ route('pengaduan.show', $t->pengaduan_id) }}">{{ $t->pengaduan->judul }}</a>
                                        <span class="code mono">{{ $t->pengaduan->kode_tiket }}</span>
                                    </td>
                                    <td data-label="Lokasi">{{ $t->pengaduan->lokasi }}</td>
                                    <td data-label="Status"><x-badge :status="$t->status" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        @include('dashboard-notifikasi')
    </div>
@endif
@endsection

@extends('layouts.app')

@section('title', auth()->user()->isAdmin() ? 'Pengaduan' : 'Pengaduan Saya')

@section('content')
@php
    $admin = auth()->user()->isAdmin();
    $linkUrut = fn ($kolom) => request()->fullUrlWithQuery([
        'urut' => $kolom,
        'arah' => ($urut === $kolom && $arah === 'asc') ? 'desc' : 'asc',
        'page' => null,
    ]);
    $panah = fn ($kolom) => $urut === $kolom ? ($arah === 'asc' ? '↑' : '↓') : '';
@endphp

<div class="page-head">
    <div>
        <div class="eyebrow">{{ $admin ? 'Seluruh pengaduan' : 'Riwayat laporan' }}</div>
        <h1>{{ $admin ? 'Daftar Pengaduan' : 'Pengaduan Saya' }}</h1>
        <p>{{ $admin ? 'Periksa laporan masuk, verifikasi, lalu tugaskan teknisi.' : 'Pantau status setiap laporan yang pernah kamu kirim.' }}</p>
    </div>
</div>

<div class="card">
    <form method="GET" action="{{ route('pengaduan.index') }}" class="toolbar">
        <div class="search">
            <x-icon name="search" :size="16" />
            <input type="search" name="q" value="{{ $q }}" placeholder="{{ $admin ? 'Cari judul, kode, lokasi, atau nama pelapor' : 'Cari judul, kode, atau lokasi' }}">
        </div>
        <select name="status" aria-label="Filter status" onchange="this.form.submit()">
            <option value="">Semua status</option>
            @foreach (\App\Models\Pengaduan::STATUS as $s)
                <option value="{{ $s }}" @selected($status === $s)>{{ $s }}</option>
            @endforeach
        </select>
        <select name="kategori" aria-label="Filter kategori" onchange="this.form.submit()">
            <option value="">Semua kategori</option>
            @foreach (\App\Models\Pengaduan::KATEGORI as $k)
                <option value="{{ $k }}" @selected($kategori === $k)>{{ $k }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-outline">Terapkan</button>
        @if ($q || $status || $kategori)
            <a href="{{ route('pengaduan.index') }}" class="btn btn-ghost">Atur ulang</a>
        @endif
    </form>

    @if ($pengaduan->isEmpty())
        <div class="empty">
            <strong>{{ ($q || $status || $kategori) ? 'Tidak ada laporan yang cocok' : 'Belum ada pengaduan' }}</strong>
            @if (! $admin && ! ($q || $status || $kategori))
                Kalau melihat sarana yang rusak, laporkan agar segera ditangani.
                <div><a href="{{ route('pengaduan.create') }}" class="btn">Buat pengaduan</a></div>
            @endif
        </div>
    @else
        <div class="table-wrap">
            <table class="table table-cards">
                <thead>
                    <tr>
                        <th><a href="{{ $linkUrut('judul') }}">Laporan {{ $panah('judul') }}</a></th>
                        @if ($admin) <th>Pelapor</th> @endif
                        <th><a href="{{ $linkUrut('kategori') }}">Kategori {{ $panah('kategori') }}</a></th>
                        <th><a href="{{ $linkUrut('status') }}">Status {{ $panah('status') }}</a></th>
                        @if ($admin) <th>Teknisi</th> @endif
                        <th><a href="{{ $linkUrut('created_at') }}">Tanggal {{ $panah('created_at') }}</a></th>
                        <th class="right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pengaduan as $p)
                        <tr>
                            <td data-label="Laporan">
                                <a class="title" href="{{ route('pengaduan.show', $p) }}">{{ $p->judul }}</a>
                                <span class="code mono">{{ $p->kode_tiket }} &middot; {{ $p->lokasi }}</span>
                            </td>
                            @if ($admin)
                                <td data-label="Pelapor">
                                    {{ $p->pelapor?->name }}
                                    <span class="sub" style="display:block;">{{ $p->pelapor?->jenis_pengguna }}</span>
                                </td>
                            @endif
                            <td data-label="Kategori">{{ $p->kategori }}</td>
                            <td data-label="Status"><x-badge :status="$p->status" /></td>
                            @if ($admin)
                                <td data-label="Teknisi">
                                    @forelse ($p->teknisi as $t)
                                        <span class="sub" style="display:block;">{{ $t->name }}</span>
                                    @empty
                                        <span class="muted">&mdash;</span>
                                    @endforelse
                                </td>
                            @endif
                            <td data-label="Tanggal"><span class="dt">{{ $p->created_at->translatedFormat('d M Y') }}<small>{{ $p->created_at->format('H:i') }} WIB</small></span></td>
                            <td class="right" data-label="Aksi">
                                <a href="{{ route('pengaduan.show', $p) }}" class="btn btn-outline btn-sm">
                                    {{ $admin && $p->status === 'Menunggu' ? 'Verifikasi' : 'Detail' }}
                                </a>
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

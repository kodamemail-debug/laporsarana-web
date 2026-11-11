@extends('layouts.app')

@section('title', 'Teknisi')

@section('content')
<div class="page-head">
    <div>
        <div class="eyebrow">Pengelolaan</div>
        <h1>Teknisi</h1>
        <p>Kelola akun teknisi dan pantau siapa mengerjakan laporan yang mana.</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.teknisi.create') }}" class="btn"><x-icon name="plus" :size="16" /> Tambah teknisi</a>
    </div>
</div>

<div class="stack">
    <div class="card">
        <div class="card-head"><h2>Daftar teknisi</h2></div>
        @if ($teknisi->isEmpty())
            <div class="empty"><strong>Belum ada teknisi</strong>Tambahkan akun teknisi agar laporan dapat ditugaskan.</div>
        @else
            <div class="table-wrap">
                <table class="table table-cards">
                    <thead>
                        <tr><th>Teknisi</th><th>Keahlian</th><th>Kontak</th><th>Tugas aktif</th><th>Selesai</th><th>Status</th><th class="right">Aksi</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($teknisi as $t)
                            <tr>
                                <td data-label="Teknisi">
                                    <div class="person">
                                        <x-avatar :user="$t" :size="32" />
                                        <div class="info">
                                            <strong>{{ $t->name }}</strong>
                                            <span class="sub">{{ $t->email }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Keahlian">{{ $t->keahlian ?: '—' }}</td>
                                <td data-label="Kontak">{{ $t->no_hp ?: '—' }}</td>
                                <td class="mono" data-label="Tugas aktif">{{ $t->tugas_aktif }}</td>
                                <td class="mono" data-label="Selesai">{{ $t->tugas_selesai }}</td>
                                <td data-label="Status">
                                    @if ($t->aktif)
                                        <span class="badge st-selesai">Aktif</span>
                                    @else
                                        <span class="badge st-tolak">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="right nowrap" data-label="Aksi">
                                    <a href="{{ route('admin.teknisi.edit', $t) }}" class="btn btn-outline btn-sm">Ubah</a>
                                    <form method="POST" action="{{ route('admin.teknisi.toggle', $t) }}" style="display:inline;"
                                          data-confirm="{{ $t->aktif ? 'Nonaktifkan teknisi ini? Ia tidak bisa masuk dan tidak bisa ditugaskan.' : 'Aktifkan kembali teknisi ini?' }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn {{ $t->aktif ? 'btn-danger' : 'btn-outline' }} btn-sm">{{ $t->aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="card">
        <div class="card-head"><h2>Penugasan berjalan</h2><span class="sub">20 terbaru</span></div>
        @if ($berjalan->isEmpty())
            <div class="empty">Tidak ada penugasan yang sedang berjalan.</div>
        @else
            <div class="table-wrap">
                <table class="table table-cards">
                    <thead><tr><th>Laporan</th><th>Teknisi</th><th>Status tugas</th><th>Ditugaskan</th></tr></thead>
                    <tbody>
                        @foreach ($berjalan as $b)
                            <tr>
                                <td data-label="Laporan">
                                    <a class="title" href="{{ route('pengaduan.show', $b->pengaduan_id) }}">{{ $b->pengaduan->judul }}</a>
                                    <span class="code mono">{{ $b->pengaduan->kode_tiket }}</span>
                                </td>
                                <td data-label="Teknisi">{{ $b->teknisi->name }}</td>
                                <td data-label="Status tugas"><x-badge :status="$b->status" /></td>
                                <td class="nowrap" data-label="Ditugaskan">{{ $b->created_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection

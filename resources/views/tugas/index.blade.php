@extends('layouts.app')

@section('title', 'Tugas Saya')

@section('content')
<div class="page-head">
    <div>
        <div class="eyebrow">Penugasan</div>
        <h1>Tugas Saya</h1>
        <p>Laporan yang ditugaskan admin kepadamu. Buka tugas untuk melihat detail dan memperbarui pengerjaan.</p>
    </div>
</div>

<div class="card">
    <form method="GET" action="{{ route('tugas.index') }}" class="toolbar">
        <div class="search">
            <x-icon name="search" :size="16" />
            <input type="search" name="q" value="{{ $q }}" placeholder="Cari judul, kode, atau lokasi">
        </div>
        <select name="status" aria-label="Filter status" onchange="this.form.submit()">
            <option value="">Semua status</option>
            @foreach (['Ditugaskan', 'Dikerjakan', 'Selesai'] as $s)
                <option value="{{ $s }}" @selected($status === $s)>{{ $s }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-outline">Terapkan</button>
        @if ($q || $status)
            <a href="{{ route('tugas.index') }}" class="btn btn-ghost">Atur ulang</a>
        @endif
    </form>

    @if ($tugas->isEmpty())
        <div class="empty"><strong>Tidak ada tugas</strong>Tugas yang diberikan admin akan muncul di sini.</div>
    @else
        <div class="table-wrap">
            <table class="table table-cards">
                <thead>
                    <tr><th>Laporan</th><th>Lokasi</th><th>Kategori</th><th>Tugasmu</th><th>Ditugaskan</th><th class="right">Aksi</th></tr>
                </thead>
                <tbody>
                    @foreach ($tugas as $t)
                        <tr>
                            <td data-label="Laporan">
                                <a class="title" href="{{ route('pengaduan.show', $t->pengaduan_id) }}">{{ $t->pengaduan->judul }}</a>
                                <span class="code mono">{{ $t->pengaduan->kode_tiket }}</span>
                            </td>
                            <td data-label="Lokasi">{{ $t->pengaduan->lokasi }}</td>
                            <td data-label="Kategori">{{ $t->pengaduan->kategori }}</td>
                            <td data-label="Tugasmu"><x-badge :status="$t->status" /></td>
                            <td data-label="Ditugaskan"><span class="dt">{{ $t->created_at->translatedFormat('d M Y') }}<small>{{ $t->created_at->format('H:i') }} WIB</small></span></td>
                            <td class="right" data-label="Aksi">
                                <a href="{{ route('pengaduan.show', $t->pengaduan_id) }}" class="btn btn-outline btn-sm">
                                    {{ $t->status === 'Selesai' ? 'Lihat' : 'Kerjakan' }}
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $tugas->links() }}
    @endif
</div>
@endsection

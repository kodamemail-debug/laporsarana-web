@extends('layouts.app')

@section('title', 'Data Pengguna')

@section('content')
<div class="page-head">
    <div>
        <div class="eyebrow">Data master</div>
        <h1>Data Pengguna</h1>
        <p>Siswa hanya dapat mendaftar bila NIS-nya ada di sini, guru bila NIP-nya ada. Nama pada akun diambil dari data ini.</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.identitas.create') }}" class="btn"><x-icon name="plus" :size="16" /> Tambah data</a>
    </div>
</div>

<div class="stack">
    <div class="card">
        <div class="card-head"><h2>Import dari CSV</h2><span class="sub">Kolom: jenis, nomor, nama, kelas</span></div>
        <form method="POST" action="{{ route('admin.identitas.import') }}" enctype="multipart/form-data" class="card-body" data-loading
              style="display:flex; gap:12px; align-items:flex-start; flex-wrap:wrap;">
            @csrf
            <div style="flex:1 1 280px;">
                <input type="file" name="berkas" accept=".csv,.txt" required
                       style="width:100%; padding:7px 10px; border:1px solid var(--line-strong); border-radius:6px; background:var(--surface); color:var(--ink);">
                <div class="help" style="margin-top:6px;">
                    Contoh baris: <span class="mono">Siswa;2526010;Nama Siswa;XI RPL A</span> atau <span class="mono">Guru;123456789012000;Nama Guru;</span>.
                    Baris judul boleh ada. Data yang sudah dipakai akun tidak ditimpa.
                </div>
                @error('berkas') <div class="field-error">{{ $message }}</div> @enderror
            </div>
            <button type="submit" class="btn btn-outline"><x-icon name="upload" :size="16" /> Import</button>
        </form>
    </div>

    <div class="card">
        <form method="GET" action="{{ route('admin.identitas.index') }}" class="toolbar">
            <div class="search">
                <x-icon name="search" :size="16" />
                <input type="search" name="q" value="{{ $q }}" placeholder="Cari nama atau nomor">
            </div>
            <select name="jenis" aria-label="Filter jenis" onchange="this.form.submit()">
                <option value="">Siswa dan guru</option>
                <option value="Siswa" @selected($jenis === 'Siswa')>Siswa (NIS)</option>
                <option value="Guru" @selected($jenis === 'Guru')>Guru (NIP)</option>
            </select>
            <button type="submit" class="btn btn-outline">Cari</button>
            @if ($q || $jenis)
                <a href="{{ route('admin.identitas.index') }}" class="btn btn-ghost">Atur ulang</a>
            @endif
        </form>

        @if ($data->isEmpty())
            <div class="empty"><strong>Data tidak ditemukan</strong></div>
        @else
            <div class="table-wrap">
                <table class="table table-cards">
                    <thead><tr><th>Nama</th><th>Jenis</th><th>Nomor</th><th>Kelas</th><th>Akun</th><th>Status</th><th class="right">Aksi</th></tr></thead>
                    <tbody>
                        @foreach ($data as $d)
                            @php $punyaAkun = in_array($d->nomor_identitas, $terdaftar, true); @endphp
                            <tr>
                                <td data-label="Nama"><strong>{{ $d->nama_lengkap }}</strong></td>
                                <td data-label="Jenis">{{ $d->jenis_pengguna }}</td>
                                <td class="mono" data-label="Nomor"><span class="muted small">{{ \App\Models\IdentitasSekolah::labelNomor($d->jenis_pengguna) }}</span> {{ $d->nomor_identitas }}</td>
                                <td data-label="Kelas">{{ $d->kelas ?: '—' }}</td>
                                <td data-label="Akun">{!! $punyaAkun ? '<span class="badge st-selesai">Terdaftar</span>' : '<span class="muted">Belum</span>' !!}</td>
                                <td data-label="Status">{!! $d->aktif ? '<span class="badge st-verif">Aktif</span>' : '<span class="badge st-tolak">Nonaktif</span>' !!}</td>
                                <td class="right nowrap" data-label="Aksi">
                                    <a href="{{ route('admin.identitas.edit', $d) }}" class="btn btn-outline btn-sm">Ubah</a>
                                    <form method="POST" action="{{ route('admin.identitas.toggle', $d) }}" style="display:inline;">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn-outline btn-sm">{{ $d->aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                    </form>
                                    @unless ($punyaAkun)
                                        <form method="POST" action="{{ route('admin.identitas.destroy', $d) }}" style="display:inline;" data-confirm="Hapus data {{ $d->nama_lengkap }}?">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                        </form>
                                    @endunless
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $data->links() }}
        @endif
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', $identitas->exists ? 'Ubah Data' : 'Tambah Data')

@section('content')
<a href="{{ route('admin.identitas.index') }}" class="back"><x-icon name="back" :size="15" /> Kembali</a>

<div class="page-head">
    <div>
        <div class="eyebrow">Data master</div>
        <h1>{{ $identitas->exists ? 'Ubah data' : 'Tambah data siswa / guru' }}</h1>
    </div>
</div>

<div class="card" style="max-width: 640px;">
    <form method="POST" class="card-body" data-validate data-loading
          action="{{ $identitas->exists ? route('admin.identitas.update', $identitas) : route('admin.identitas.store') }}">
        @csrf
        @if ($identitas->exists) @method('PUT') @endif

        @php $jenis = old('jenis_pengguna', $identitas->jenis_pengguna ?: 'Siswa'); @endphp

        <div class="field">
            <span class="label">Jenis</span>
            <div class="segmented">
                <input type="radio" name="jenis_pengguna" id="j-siswa" value="Siswa" @checked($jenis === 'Siswa')>
                <label for="j-siswa">Siswa (NIS)</label>
                <input type="radio" name="jenis_pengguna" id="j-guru" value="Guru" @checked($jenis === 'Guru')>
                <label for="j-guru">Guru (NIP)</label>
            </div>
            @error('jenis_pengguna') <div class="field-error">{{ $message }}</div> @enderror
        </div>

        <div class="field @error('nomor_identitas') has-error @enderror">
            <label for="nomor_identitas">NIS / NIP <span class="req">*</span></label>
            <input type="text" id="nomor_identitas" name="nomor_identitas" value="{{ old('nomor_identitas', $identitas->nomor_identitas) }}" required maxlength="50">
            <div class="help">NIP boleh diketik dengan spasi, akan disimpan sebagai angka saja (18 digit).</div>
            @error('nomor_identitas') <div class="field-error">{{ $message }}</div> @enderror
        </div>

        <div class="field @error('nama_lengkap') has-error @enderror">
            <label for="nama_lengkap">Nama lengkap <span class="req">*</span></label>
            <input type="text" id="nama_lengkap" name="nama_lengkap" value="{{ old('nama_lengkap', $identitas->nama_lengkap) }}" required maxlength="255">
            @error('nama_lengkap') <div class="field-error">{{ $message }}</div> @enderror
        </div>

        <div class="field @error('kelas') has-error @enderror">
            <label for="kelas">Kelas <span class="muted" style="font-weight:400;">(untuk siswa)</span></label>
            <input type="text" id="kelas" name="kelas" value="{{ old('kelas', $identitas->kelas) }}" maxlength="30" placeholder="Contoh: XII RPL C">
            @error('kelas') <div class="field-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-actions">
            <button type="submit" class="btn">Simpan</button>
            <a href="{{ route('admin.identitas.index') }}" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>
@endsection

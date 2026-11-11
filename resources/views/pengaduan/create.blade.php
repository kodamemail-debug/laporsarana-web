@extends('layouts.app')

@section('title', 'Buat Pengaduan')

@section('content')
<div class="page-head">
    <div>
        <div class="eyebrow">Laporan baru</div>
        <h1>Buat Pengaduan</h1>
        <p>Jelaskan kerusakan sarana yang kamu temukan. Semakin jelas, semakin cepat ditangani.</p>
    </div>
</div>

<form method="POST" action="{{ route('pengaduan.store') }}" enctype="multipart/form-data" data-validate data-loading>
    @csrf

    <div class="grid grid-main">
        <div class="card">
            <div class="card-body">
                <div class="field @error('judul') has-error @enderror">
                    <label for="judul">Judul laporan <span class="req">*</span></label>
                    <input type="text" id="judul" name="judul" value="{{ old('judul') }}" maxlength="150" required
                           placeholder="Contoh: Atap koridor lantai 2 bocor">
                    @error('judul') <div class="field-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-grid">
                    <div class="field @error('kategori') has-error @enderror">
                        <label for="kategori">Kategori <span class="req">*</span></label>
                        <select id="kategori" name="kategori" required>
                            <option value="">Pilih kategori</option>
                            @foreach ($kategoriList as $k)
                                <option value="{{ $k }}" @selected(old('kategori') === $k)>{{ $k }}</option>
                            @endforeach
                        </select>
                        @error('kategori') <div class="field-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="field @error('lokasi') has-error @enderror">
                        <label for="lokasi">Lokasi <span class="req">*</span></label>
                        <select id="lokasi" name="lokasi" required>
                            <option value="">Pilih lokasi</option>
                            @foreach ($lokasiList as $grup => $daftar)
                                <optgroup label="{{ $grup }}">
                                    @foreach ($daftar as $l)
                                        <option value="{{ $l }}" @selected(old('lokasi') === $l)>{{ $l }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        @error('lokasi') <div class="field-error">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="field @error('detail_lokasi') has-error @enderror">
                    <label for="detail_lokasi">Detail lokasi <span class="muted" style="font-weight:400;">(opsional)</span></label>
                    <input type="text" id="detail_lokasi" name="detail_lokasi" value="{{ old('detail_lokasi') }}" maxlength="100"
                           placeholder="Contoh: dekat pintu masuk, meja nomor 5">
                    <div class="help">Bantu teknisi menemukan tempatnya dengan tepat.</div>
                    @error('detail_lokasi') <div class="field-error">{{ $message }}</div> @enderror
                </div>

                <div class="field @error('deskripsi') has-error @enderror">
                    <label for="deskripsi">Uraian kerusakan <span class="req">*</span></label>
                    <textarea id="deskripsi" name="deskripsi" required minlength="10" maxlength="3000"
                              placeholder="Apa yang rusak, sejak kapan, dan apakah mengganggu kegiatan belajar?">{{ old('deskripsi') }}</textarea>
                    @error('deskripsi') <div class="field-error">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="stack">
            <div class="card">
                <div class="card-head"><h2>Foto bukti <span class="req" style="color:var(--danger)">*</span></h2></div>
                <div class="card-body">
                    <x-dropzone name="foto" label="Unggah foto kerusakan" :maks="5" />
                </div>
            </div>

            <button type="submit" class="btn btn-block">Kirim pengaduan</button>
            <a href="{{ route('pengaduan.index') }}" class="btn btn-outline btn-block">Batal</a>
        </div>
    </div>
</form>
@endsection

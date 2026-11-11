@extends('layouts.app')

@section('title', $teknisi->exists ? 'Ubah Teknisi' : 'Tambah Teknisi')

@section('content')
<a href="{{ route('admin.teknisi.index') }}" class="back"><x-icon name="back" :size="15" /> Kembali</a>

<div class="page-head">
    <div>
        <div class="eyebrow">Teknisi</div>
        <h1>{{ $teknisi->exists ? 'Ubah data teknisi' : 'Tambah teknisi' }}</h1>
    </div>
</div>

<div class="card" style="max-width: 640px;">
    <form method="POST" class="card-body" data-validate data-loading
          action="{{ $teknisi->exists ? route('admin.teknisi.update', $teknisi) : route('admin.teknisi.store') }}">
        @csrf
        @if ($teknisi->exists) @method('PUT') @endif

        <div class="field @error('name') has-error @enderror">
            <label for="name">Nama lengkap <span class="req">*</span></label>
            <input type="text" id="name" name="name" value="{{ old('name', $teknisi->name) }}" required maxlength="100">
            @error('name') <div class="field-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-grid">
            <div class="field @error('email') has-error @enderror">
                <label for="email">Email <span class="req">*</span></label>
                <input type="email" id="email" name="email" value="{{ old('email', $teknisi->email) }}" required>
                @error('email') <div class="field-error">{{ $message }}</div> @enderror
            </div>
            <div class="field @error('no_hp') has-error @enderror">
                <label for="no_hp">Nomor HP</label>
                <input type="tel" id="no_hp" name="no_hp" value="{{ old('no_hp', $teknisi->no_hp) }}">
                @error('no_hp') <div class="field-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="field @error('keahlian') has-error @enderror">
            <label for="keahlian">Keahlian</label>
            <input type="text" id="keahlian" name="keahlian" value="{{ old('keahlian', $teknisi->keahlian) }}" maxlength="100"
                   placeholder="Contoh: Listrik &amp; Penerangan">
            @error('keahlian') <div class="field-error">{{ $message }}</div> @enderror
        </div>

        <div class="field @error('password') has-error @enderror">
            <label for="password">Password {!! $teknisi->exists ? '<span class="muted" style="font-weight:400;">(kosongkan bila tidak diganti)</span>' : '<span class="req">*</span>' !!}</label>
            <input type="password" id="password" name="password" minlength="8" autocomplete="new-password" {{ $teknisi->exists ? '' : 'required' }}>
            @error('password') <div class="field-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-actions">
            <button type="submit" class="btn">Simpan</button>
            <a href="{{ route('admin.teknisi.index') }}" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>
@endsection

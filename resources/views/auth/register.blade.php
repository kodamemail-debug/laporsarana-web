@extends('layouts.guest')

@section('title', 'Daftar')

@section('content')
    <h1>Buat akun pelapor</h1>
    <p class="lead">Siswa mendaftar dengan NIS, guru dengan NIP. Nama terisi otomatis dari data sekolah.</p>

    <form method="POST" action="{{ route('register.process') }}" id="form-daftar"
          data-lookup="{{ route('identitas.lookup') }}" data-validate data-loading>
        @csrf

        @php $jenis = old('jenis_pengguna', 'Siswa'); @endphp

        <div class="field">
            <span class="label">Saya mendaftar sebagai</span>
            <div class="segmented">
                <input type="radio" name="jenis_pengguna" id="j-siswa" value="Siswa" @checked($jenis === 'Siswa')>
                <label for="j-siswa">Siswa</label>
                <input type="radio" name="jenis_pengguna" id="j-guru" value="Guru" @checked($jenis === 'Guru')>
                <label for="j-guru">Guru</label>
            </div>
            @error('jenis_pengguna') <div class="field-error">{{ $message }}</div> @enderror
        </div>

        <div class="field @error('nomor_identitas') has-error @enderror">
            <label for="nomor_identitas"><span id="label-nomor">NIS</span> <span class="req">*</span></label>
            <input type="text" id="nomor_identitas" name="nomor_identitas" value="{{ old('nomor_identitas') }}"
                   required inputmode="numeric" autocomplete="off">
            <div class="help" id="hint-nomor"></div>
            <div id="info-nomor" class="field-ok"></div>
            @error('nomor_identitas') <div class="field-error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <span class="label">Nama lengkap</span>
            <div class="name-box empty-name" id="nama-otomatis">Nama akan muncul otomatis setelah nomor diisi</div>
        </div>

        <div class="field @error('email') has-error @enderror">
            <label for="email">Email <span class="req">*</span></label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="email">
            @error('email') <div class="field-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-grid">
            <div class="field @error('password') has-error @enderror">
                <label for="password">Password <span class="req">*</span></label>
                <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password">
                @error('password') <div class="field-error">{{ $message }}</div> @enderror
            </div>
            <div class="field">
                <label for="password_confirmation">Ulangi password <span class="req">*</span></label>
                <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8" autocomplete="new-password">
            </div>
        </div>

        <button type="submit" class="btn btn-block">Daftar</button>
    </form>

    <p class="alt">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></p>
@endsection

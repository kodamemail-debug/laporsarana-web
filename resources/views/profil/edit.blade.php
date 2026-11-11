@extends('layouts.app')

@section('title', 'Profil')

@section('content')
<div class="page-head">
    <div>
        <div class="eyebrow">Akun</div>
        <h1>Profil Saya</h1>
        <p>Nama dan nomor identitas berasal dari data sekolah sehingga tidak dapat diubah.</p>
    </div>
</div>

<div class="grid grid-main">
    <div class="card">
        <div class="profile-head">
            <x-avatar :user="$user" :size="64" />
            <div>
                <h2>{{ $user->name }}</h2>
                <span class="muted">{{ $user->isPelapor() ? $user->jenis_pengguna : ($user->isAdmin() ? 'Kepala Sarpras' : 'Teknisi') }}</span>
            </div>
        </div>

        <form method="POST" action="{{ route('profil.update') }}" enctype="multipart/form-data" class="card-body" data-validate data-loading>
            @csrf
            @method('PUT')

            <div class="form-grid">
                <div class="field">
                    <label>Nama lengkap <span class="locked"><x-icon name="lock" :size="12" /> tidak dapat diubah</span></label>
                    <input type="text" value="{{ $user->name }}" readonly>
                </div>

                @if ($user->nomor_identitas)
                    <div class="field">
                        <label>{{ $user->label_identitas }} <span class="locked"><x-icon name="lock" :size="12" /> tidak dapat diubah</span></label>
                        <input type="text" value="{{ $user->nomor_identitas }}" readonly>
                    </div>
                @endif
            </div>

            <div class="form-grid">
                <div class="field @error('email') has-error @enderror">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                    @error('email') <div class="field-error">{{ $message }}</div> @enderror
                </div>

                <div class="field @error('no_hp') has-error @enderror">
                    <label for="no_hp">Nomor HP</label>
                    <input type="tel" id="no_hp" name="no_hp" value="{{ old('no_hp', $user->no_hp) }}" placeholder="08xxxxxxxxxx">
                    @error('no_hp') <div class="field-error">{{ $message }}</div> @enderror
                </div>
            </div>

            @if ($user->jenis_pengguna === 'Siswa')
                <div class="field @error('kelas') has-error @enderror">
                    <label for="kelas">Kelas</label>
                    <input type="text" id="kelas" name="kelas" value="{{ old('kelas', $user->kelas) }}" maxlength="30" placeholder="Contoh: XII RPL C">
                    <div class="help">Perbarui saat naik kelas.</div>
                    @error('kelas') <div class="field-error">{{ $message }}</div> @enderror
                </div>
            @endif

            @if ($user->isTeknisi() && $user->keahlian)
                <div class="field">
                    <label>Keahlian <span class="locked"><x-icon name="lock" :size="12" /> diatur admin</span></label>
                    <input type="text" value="{{ $user->keahlian }}" readonly>
                </div>
            @endif

            <div class="field">
                <span class="label">Foto profil</span>
                <x-dropzone name="foto" label="Unggah foto profil" :maks="2" />
                @if ($user->foto)
                    <label class="choice" style="margin-top: 10px !important;">
                        <input type="checkbox" name="hapus_foto" value="1">
                        <span>Hapus foto saat ini</span>
                    </label>
                @endif
            </div>

            <div class="form-actions">
                <button type="submit" class="btn">Simpan perubahan</button>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-head"><h2>Ganti password</h2></div>
        <form method="POST" action="{{ route('profil.password') }}" class="card-body" data-validate data-loading>
            @csrf
            @method('PUT')

            <div class="field @error('password_lama') has-error @enderror">
                <label for="password_lama">Password saat ini</label>
                <input type="password" id="password_lama" name="password_lama" required autocomplete="current-password">
                @error('password_lama') <div class="field-error">{{ $message }}</div> @enderror
            </div>
            <div class="field @error('password') has-error @enderror">
                <label for="password">Password baru</label>
                <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password">
                @error('password') <div class="field-error">{{ $message }}</div> @enderror
            </div>
            <div class="field">
                <label for="password_confirmation">Ulangi password baru</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8" autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn-outline btn-block">Ganti password</button>
        </form>
    </div>
</div>
@endsection

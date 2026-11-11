@extends('layouts.guest')

@section('title', 'Masuk')

@section('content')
    <h1>Masuk</h1>
    <p class="lead">Gunakan akun yang terdaftar di LaporSarana.</p>

    <form method="POST" action="{{ route('login.process') }}" data-validate data-loading>
        @csrf

        <div class="field @error('email') has-error @enderror">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
            @error('email') <div class="field-error">{{ $message }}</div> @enderror
        </div>

        <div class="field @error('password') has-error @enderror">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">
            @error('password') <div class="field-error">{{ $message }}</div> @enderror
        </div>

        <label class="choice" style="margin-bottom: 18px !important;">
            <input type="checkbox" name="ingat" value="1">
            <span>Ingat saya di perangkat ini</span>
        </label>

        <button type="submit" class="btn btn-block">Masuk</button>
    </form>

    <p class="alt">Belum punya akun? <a href="{{ route('register') }}">Daftar sebagai siswa atau guru</a></p>
@endsection

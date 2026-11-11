<!DOCTYPE html>
<html lang="id">
<head>
    @include('layouts.head')
    <title>@yield('title') &middot; LaporSarana</title>
</head>
<body>
@include('layouts.toasts')

<div class="auth">
    <section class="auth-side">
        <a href="{{ route('home') }}" class="brand">
            <img class="brand-logo" src="{{ asset('img/logo-mark-dark.png') }}" alt="">
            <span class="brand-text">
                <strong>Lapor<span class="gold">Sarana</span></strong>
                <span>SMK Negeri 5 Surakarta</span>
            </span>
        </a>

        <h2>Sarana yang rusak, dilaporkan dan dipantau sampai tuntas.</h2>
        <p>Sistem pengaduan sarana dan prasarana untuk siswa dan guru, dikelola oleh bagian Sarana dan Prasarana sekolah.</p>
        <ul>
            <li><x-icon name="check" :size="16" /> Laporan disertai foto bukti kerusakan</li>
            <li><x-icon name="check" :size="16" /> Status dapat dipantau dari awal sampai selesai</li>
            <li><x-icon name="check" :size="16" /> Berdiskusi langsung dengan admin sarpras</li>
        </ul>

        <div class="foot">&copy; {{ date('Y') }} SMK Negeri 5 Surakarta</div>
    </section>

    <section class="auth-main">
        <button type="button" class="icon-btn auth-theme" data-theme-toggle aria-label="Ganti tema">
            <span class="theme-moon"><x-icon name="moon" /></span>
            <span class="theme-sun"><x-icon name="sun" /></span>
        </button>
        <div class="auth-box">
            @yield('content')
        </div>
    </section>
</div>

<script src="{{ asset('js/app.js') }}?v={{ @filemtime(public_path('js/app.js')) }}" defer></script>
</body>
</html>

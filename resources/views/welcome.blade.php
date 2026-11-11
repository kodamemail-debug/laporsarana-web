<!DOCTYPE html>
<html lang="id">
<head>
    @include('layouts.head')
    <title>LaporSarana &middot; Pengaduan Sarana SMK Negeri 5 Surakarta</title>
</head>
<body>
<header class="site-head">
    <a href="{{ route('home') }}" class="brand">
        <img class="brand-logo logo-light" src="{{ asset('img/logo-mark.png') }}" alt="">
            <img class="brand-logo logo-dark" src="{{ asset('img/logo-mark-dark.png') }}" alt="">
        <span class="brand-text">
            <strong>Lapor<span class="gold">Sarana</span></strong>
            <span>SMK Negeri 5 Surakarta</span>
        </span>
    </a>
    <div class="site-nav">
        <button type="button" class="icon-btn" data-theme-toggle aria-label="Ganti tema">
            <span class="theme-moon"><x-icon name="moon" /></span>
            <span class="theme-sun"><x-icon name="sun" /></span>
        </button>
        <a href="{{ route('login') }}" class="btn btn-outline btn-sm">Masuk</a>
        <a href="{{ route('register') }}" class="btn btn-sm">Daftar</a>
    </div>
</header>

<section class="hero">
    <div>
        <div class="eyebrow">Sistem Pengaduan Sarana dan Prasarana</div>
        <h1>Ada yang rusak di sekolah? Laporkan, lalu pantau sampai selesai.</h1>
        <p class="lead">
            Siswa dan guru dapat melaporkan kerusakan sarana lengkap dengan foto, mengikuti perkembangan
            penanganannya, dan berdiskusi langsung dengan petugas sarana dan prasarana.
        </p>
        <div class="cta">
            <a href="{{ route('register') }}" class="btn">Buat akun pelapor</a>
            <a href="{{ route('login') }}" class="btn btn-outline">Sudah punya akun</a>
        </div>
    </div>

    <div class="card ticket" aria-hidden="true">
        <div class="t-head">
            <span class="t-code mono">LS-261103-0007</span>
            <span class="badge st-kerja">Dikerjakan</span>
        </div>
        <h3>Atap koridor lantai 2 bocor</h3>
        <div class="t-meta">Gedung B, koridor depan Lab RPL &middot; Bangunan &amp; Atap</div>
        <x-stepper status="Dikerjakan" />
    </div>
</section>

<section class="steps">
    <h2>Alur penanganan laporan</h2>
    <div class="steps-grid">
        <div class="card step-card">
            <div class="n">1</div>
            <h3>Laporkan</h3>
            <p>Isi judul, lokasi, dan uraian kerusakan, lalu unggah foto sebagai bukti.</p>
        </div>
        <div class="card step-card">
            <div class="n">2</div>
            <h3>Diverifikasi</h3>
            <p>Kepala Sarpras memeriksa laporan dan menugaskan satu atau lebih teknisi.</p>
        </div>
        <div class="card step-card">
            <div class="n">3</div>
            <h3>Dikerjakan</h3>
            <p>Teknisi memperbaiki dan mencatat tindakan yang dilakukan beserta foto hasilnya.</p>
        </div>
        <div class="card step-card">
            <div class="n">4</div>
            <h3>Selesai</h3>
            <p>Kamu menerima pemberitahuan dan dapat memberi penilaian atas hasil perbaikan.</p>
        </div>
    </div>
</section>

<footer class="site-foot">&copy; {{ date('Y') }} SMK Negeri 5 Surakarta &middot; LaporSarana</footer>
<script src="{{ asset('js/app.js') }}?v={{ @filemtime(public_path('js/app.js')) }}" defer></script>
</body>
</html>

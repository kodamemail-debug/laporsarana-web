@php
    $user = auth()->user();

    // [route, label, ikon, pola route aktif, kunci lencana]
    $menu = match ($user->role) {
        'Admin' => [
            ['dashboard', 'Dashboard', 'dashboard', ['dashboard'], null],
            ['pengaduan.index', 'Pengaduan', 'clipboard', ['pengaduan.*'], null],
            ['pesan.index', 'Pesan', 'chat', ['pesan.*'], 'pesan'],
            ['admin.teknisi.index', 'Teknisi', 'wrench', ['admin.teknisi.*'], null],
            ['admin.identitas.index', 'Data Pengguna', 'users', ['admin.identitas.*'], null],
            ['admin.laporan.index', 'Rekap Laporan', 'chart', ['admin.laporan.*'], null],
        ],
        'Teknisi' => [
            ['dashboard', 'Dashboard', 'dashboard', ['dashboard'], null],
            ['tugas.index', 'Tugas Saya', 'wrench', ['tugas.*', 'pengaduan.show'], null],
        ],
        default => [
            ['dashboard', 'Dashboard', 'dashboard', ['dashboard'], null],
            ['pengaduan.create', 'Buat Pengaduan', 'plus', ['pengaduan.create'], null],
            ['pengaduan.index', 'Pengaduan Saya', 'clipboard', ['pengaduan.index', 'pengaduan.show'], null],
            ['pesan.index', 'Pesan', 'chat', ['pesan.*'], 'pesan'],
        ],
    };

    $badge = ['pesan' => $badgePesan ?? 0, 'notif' => $badgeNotif ?? 0];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    @include('layouts.head')
    <title>@yield('title', 'LaporSarana') &middot; LaporSarana</title>
</head>
<body data-ringkasan="{{ route('ringkasan') }}">
@include('layouts.toasts')

<div class="app">
    <aside class="sidebar" id="sidebar">
        <a href="{{ route('dashboard') }}" class="brand">
            <img class="brand-logo logo-light" src="{{ asset('img/logo-mark.png') }}" alt="">
            <img class="brand-logo logo-dark" src="{{ asset('img/logo-mark-dark.png') }}" alt="">
            <span class="brand-text">
                <strong>Lapor<span class="gold">Sarana</span></strong>
                <span>SMK Negeri 5 Surakarta</span>
            </span>
        </a>

        <div class="nav-label">Menu</div>
        <nav class="nav" aria-label="Menu utama">
            @foreach ($menu as [$rute, $label, $ikon, $pola, $kunci])
                <a href="{{ route($rute) }}" class="{{ request()->routeIs(...$pola) ? 'active' : '' }}">
                    <x-icon :name="$ikon" />
                    <span>{{ $label }}</span>
                    @if ($kunci)
                        <span class="count" data-badge="{{ $kunci }}" @if (($badge[$kunci] ?? 0) < 1) hidden @endif>{{ ($badge[$kunci] ?? 0) > 99 ? '99+' : ($badge[$kunci] ?? 0) }}</span>
                    @endif
                </a>
            @endforeach
        </nav>

        <div class="side-foot">
            <a href="{{ route('profil.edit') }}" class="me" title="Profil saya">
                <x-avatar :user="$user" :size="34" />
                <div>
                    <strong>{{ $user->name }}</strong>
                    <span>{{ $user->role === 'Pelapor' ? ($user->jenis_pengguna ?? 'Pelapor') : ($user->role === 'Admin' ? 'Kepala Sarpras' : 'Teknisi') }}</span>
                </div>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-ghost btn-sm">
                    <x-icon name="logout" :size="16" /> Keluar
                </button>
            </form>
        </div>
    </aside>

    <div class="main">
        <header class="topbar">
            <button type="button" class="icon-btn" id="menu-toggle" aria-label="Buka menu">
                <x-icon name="menu" />
            </button>
            <span class="topbar-title">@yield('title')</span>

            <div class="topbar-actions">
                <button type="button" class="icon-btn" data-theme-toggle aria-label="Ganti tema terang/gelap" title="Ganti tema">
                    <span class="theme-moon"><x-icon name="moon" /></span>
                    <span class="theme-sun"><x-icon name="sun" /></span>
                </button>
                <a href="{{ route('notifikasi.index') }}" class="icon-btn" aria-label="Notifikasi" title="Notifikasi">
                    <x-icon name="bell" />
                    <span class="dot" data-badge="notif" @if ($badge['notif'] < 1) hidden @endif>{{ $badge['notif'] > 99 ? '99+' : $badge['notif'] }}</span>
                </a>
            </div>
        </header>

        <main class="content">
            @yield('content')
        </main>
    </div>
</div>

<script src="{{ asset('js/app.js') }}?v={{ @filemtime(public_path('js/app.js')) }}" defer></script>
@stack('scripts')
</body>
</html>

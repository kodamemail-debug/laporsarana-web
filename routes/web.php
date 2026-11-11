<?php

use App\Http\Controllers\Admin\IdentitasController;
use App\Http\Controllers\Admin\LaporanController;
use App\Http\Controllers\Admin\PengaduanAdminController;
use App\Http\Controllers\Admin\TeknisiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\PengaduanController;
use App\Http\Controllers\PesanController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\TugasController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Halaman depan & autentikasi
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return Auth::check() ? redirect()->route('dashboard') : view('welcome');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.process');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1')->name('register.process');

    Route::get('/identitas-sekolah/lookup', [AuthController::class, 'lookupIdentity'])
        ->middleware('throttle:30,1')
        ->name('identitas.lookup');
});

/*
|--------------------------------------------------------------------------
| Area setelah login (semua peran)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:Admin,Teknisi,Pelapor'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/ringkasan', [DashboardController::class, 'ringkasan'])->name('ringkasan');

    // Profil
    Route::get('/profil', [ProfilController::class, 'edit'])->name('profil.edit');
    Route::put('/profil', [ProfilController::class, 'update'])->name('profil.update');
    Route::put('/profil/password', [ProfilController::class, 'password'])->name('profil.password');

    // Notifikasi
    Route::get('/notifikasi', [NotifikasiController::class, 'index'])->name('notifikasi.index');
    Route::post('/notifikasi/baca-semua', [NotifikasiController::class, 'bacaSemua'])->name('notifikasi.baca-semua');
    Route::get('/notifikasi/{notifikasi}/buka', [NotifikasiController::class, 'buka'])
        ->whereNumber('notifikasi')
        ->name('notifikasi.buka');

    // Detail pengaduan dipakai bersama; isinya menyesuaikan peran.
    Route::get('/pengaduan/{pengaduan}', [PengaduanController::class, 'show'])
        ->whereNumber('pengaduan')
        ->name('pengaduan.show');
});

/*
|--------------------------------------------------------------------------
| Pelapor (siswa/guru) dan Admin
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:Pelapor,Admin'])->group(function () {
    Route::get('/pengaduan', [PengaduanController::class, 'index'])->name('pengaduan.index');

    Route::get('/pesan', [PesanController::class, 'index'])->name('pesan.index');
    Route::get('/pesan/{pengaduan}', [PesanController::class, 'show'])->whereNumber('pengaduan')->name('pesan.show');
    Route::get('/pesan/{pengaduan}/baru', [PesanController::class, 'baru'])->whereNumber('pengaduan')->name('pesan.baru');
    Route::post('/pesan/{pengaduan}', [PesanController::class, 'kirim'])->whereNumber('pengaduan')->name('pesan.kirim');
});

/*
|--------------------------------------------------------------------------
| Pelapor
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:Pelapor'])->group(function () {
    Route::get('/pengaduan/buat', [PengaduanController::class, 'create'])->name('pengaduan.create');
    Route::post('/pengaduan', [PengaduanController::class, 'store'])->name('pengaduan.store');
    Route::post('/pengaduan/{pengaduan}/penilaian', [PengaduanController::class, 'feedback'])
        ->whereNumber('pengaduan')
        ->name('pengaduan.feedback');
});

/*
|--------------------------------------------------------------------------
| Teknisi
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:Teknisi'])->prefix('tugas')->name('tugas.')->group(function () {
    Route::get('/', [TugasController::class, 'index'])->name('index');
    Route::post('/{penugasan}/mulai', [TugasController::class, 'mulai'])->whereNumber('penugasan')->name('mulai');
    Route::post('/{penugasan}/progres', [TugasController::class, 'progres'])->whereNumber('penugasan')->name('progres');
    Route::post('/{penugasan}/selesai', [TugasController::class, 'selesai'])->whereNumber('penugasan')->name('selesai');
});

/*
|--------------------------------------------------------------------------
| Admin (Kepala Sarpras)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:Admin'])->prefix('admin')->name('admin.')->group(function () {
    // Verifikasi & penugasan teknisi (satu laporan bisa ditangani beberapa teknisi)
    Route::post('/pengaduan/{pengaduan}/verifikasi', [PengaduanAdminController::class, 'verifikasi'])->name('pengaduan.verifikasi');
    Route::post('/pengaduan/{pengaduan}/tolak', [PengaduanAdminController::class, 'tolak'])->name('pengaduan.tolak');
    Route::post('/pengaduan/{pengaduan}/penugasan', [PengaduanAdminController::class, 'tambahTeknisi'])->name('penugasan.store');
    Route::delete('/pengaduan/{pengaduan}/penugasan/{penugasan}', [PengaduanAdminController::class, 'hapusTeknisi'])->name('penugasan.destroy');

    // Kelola akun teknisi
    Route::get('/teknisi', [TeknisiController::class, 'index'])->name('teknisi.index');
    Route::get('/teknisi/tambah', [TeknisiController::class, 'create'])->name('teknisi.create');
    Route::post('/teknisi', [TeknisiController::class, 'store'])->name('teknisi.store');
    Route::get('/teknisi/{teknisi}/ubah', [TeknisiController::class, 'edit'])->name('teknisi.edit');
    Route::put('/teknisi/{teknisi}', [TeknisiController::class, 'update'])->name('teknisi.update');
    Route::patch('/teknisi/{teknisi}/status', [TeknisiController::class, 'toggle'])->name('teknisi.toggle');

    // Data master siswa (NIS) & guru (NIP)
    Route::get('/pengguna', [IdentitasController::class, 'index'])->name('identitas.index');
    Route::get('/pengguna/tambah', [IdentitasController::class, 'create'])->name('identitas.create');
    Route::post('/pengguna', [IdentitasController::class, 'store'])->name('identitas.store');
    Route::post('/pengguna/import', [IdentitasController::class, 'import'])->name('identitas.import');
    Route::get('/pengguna/{identitas}/ubah', [IdentitasController::class, 'edit'])->name('identitas.edit');
    Route::put('/pengguna/{identitas}', [IdentitasController::class, 'update'])->name('identitas.update');
    Route::patch('/pengguna/{identitas}/status', [IdentitasController::class, 'toggle'])->name('identitas.toggle');
    Route::delete('/pengguna/{identitas}', [IdentitasController::class, 'destroy'])->name('identitas.destroy');

    // Rekap laporan
    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/export', [LaporanController::class, 'export'])->name('laporan.export');
    Route::get('/laporan/cetak', [LaporanController::class, 'cetak'])->name('laporan.cetak');
});

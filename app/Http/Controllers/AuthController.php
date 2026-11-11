<?php

namespace App\Http\Controllers;

use App\Models\IdentitasSekolah;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    /**
     * Mencari nama pada data master berdasarkan NIS (siswa) atau NIP (guru),
     * dipakai formulir pendaftaran untuk menampilkan nama otomatis.
     */
    public function lookupIdentity(Request $request)
    {
        $data = $request->validate([
            'nomor_identitas' => ['required', 'string', 'max:50'],
            'jenis_pengguna' => ['required', Rule::in(['Siswa', 'Guru'])],
        ]);

        $nomor = IdentitasSekolah::normalisasi($data['nomor_identitas']);
        $label = IdentitasSekolah::labelNomor($data['jenis_pengguna']);

        $identitas = IdentitasSekolah::query()
            ->where('nomor_identitas', $nomor)
            ->where('jenis_pengguna', $data['jenis_pengguna'])
            ->where('aktif', true)
            ->first();

        if (! $identitas) {
            return response()->json([
                'success' => false,
                'message' => $label . ' tidak ditemukan atau tidak aktif. Hubungi admin sekolah.',
            ], 404);
        }

        if (User::where('nomor_identitas', $nomor)->exists()) {
            return response()->json([
                'success' => false,
                'message' => $label . ' ini sudah dipakai untuk membuat akun.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'nama_lengkap' => $identitas->nama_lengkap,
            'kelas' => $identitas->kelas,
        ]);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'jenis_pengguna' => ['required', Rule::in(['Siswa', 'Guru'])],
            'nomor_identitas' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'email.unique' => 'Email sudah digunakan akun lain.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $label = IdentitasSekolah::labelNomor($data['jenis_pengguna']);
        $nomor = IdentitasSekolah::normalisasi($data['nomor_identitas']);

        // Nama diambil dari data master, bukan dari isian pengguna.
        $identitas = IdentitasSekolah::query()
            ->where('nomor_identitas', $nomor)
            ->where('jenis_pengguna', $data['jenis_pengguna'])
            ->where('aktif', true)
            ->first();

        if (! $identitas) {
            return back()
                ->withErrors(['nomor_identitas' => $label . ' tidak terdaftar atau tidak aktif.'])
                ->withInput($request->except(['password', 'password_confirmation']));
        }

        if (User::where('nomor_identitas', $nomor)->exists()) {
            return back()
                ->withErrors(['nomor_identitas' => $label . ' ini sudah dipakai untuk membuat akun.'])
                ->withInput($request->except(['password', 'password_confirmation']));
        }

        User::create([
            'name' => $identitas->nama_lengkap,
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'Pelapor',
            'jenis_pengguna' => $identitas->jenis_pengguna,
            'nomor_identitas' => $identitas->nomor_identitas,
            'kelas' => $identitas->kelas,
            'aktif' => true,
        ]);

        return redirect()
            ->route('login')
            ->with('success', 'Pendaftaran berhasil. Silakan masuk dengan email dan password kamu.');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('ingat'))) {
            return back()
                ->withErrors(['email' => 'Email atau password salah.'])
                ->onlyInput('email');
        }

        $user = Auth::user();

        if (! $user->aktif) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Akun kamu dinonaktifkan. Hubungi admin sekolah.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Kamu sudah keluar.');
    }
}

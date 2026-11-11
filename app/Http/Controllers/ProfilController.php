<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfilController extends Controller
{
    public function edit(Request $request)
    {
        return view('profil.edit', ['user' => $request->user()]);
    }

    /**
     * Nama dan nomor identitas (NIS/NIP) sengaja tidak bisa diubah dari sini,
     * karena berasal dari data master sekolah.
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $aturan = [
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'no_hp' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s]{8,20}$/'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];

        if ($user->jenis_pengguna === 'Siswa') {
            $aturan['kelas'] = ['nullable', 'string', 'max:30'];
        }

        $data = $request->validate($aturan, [
            'email.unique' => 'Email sudah digunakan akun lain.',
            'no_hp.regex' => 'Nomor HP hanya boleh berisi angka, spasi, tanda + atau -.',
            'foto.max' => 'Ukuran foto maksimal 2 MB.',
            'foto.mimes' => 'Format foto harus JPG, PNG, atau WEBP.',
        ]);

        $user->email = $data['email'];
        $user->no_hp = $data['no_hp'] ?? null;

        if (array_key_exists('kelas', $data)) {
            $user->kelas = $data['kelas'];
        }

        if ($request->hasFile('foto')) {
            $lama = $user->foto;
            $user->foto = $request->file('foto')->store('profil', 'public');

            if ($lama) {
                Storage::disk('public')->delete($lama);
            }
        } elseif ($request->boolean('hapus_foto') && $user->foto) {
            Storage::disk('public')->delete($user->foto);
            $user->foto = null;
        }

        $user->save();

        return redirect()->route('profil.edit')->with('success', 'Profil berhasil diperbarui.');
    }

    public function password(Request $request)
    {
        $data = $request->validate([
            'password_lama' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password_lama.required' => 'Masukkan password saat ini.',
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        $user = $request->user();

        if (! Hash::check($data['password_lama'], $user->password)) {
            return back()->withErrors(['password_lama' => 'Password saat ini salah.']);
        }

        $user->password = $data['password'];
        $user->save();

        return redirect()->route('profil.edit')->with('success', 'Password berhasil diganti.');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IdentitasSekolah;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Data master siswa (NIS) dan guru (NIP). Hanya nomor yang ada di sini
 * yang bisa dipakai untuk mendaftar akun pelapor.
 */
class IdentitasController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $jenis = $request->query('jenis');

        $query = IdentitasSekolah::query()->orderBy('jenis_pengguna')->orderBy('nama_lengkap');

        if ($q !== '') {
            $nomor = IdentitasSekolah::normalisasi($q);

            $query->where(function ($w) use ($q, $nomor) {
                $w->where('nama_lengkap', 'like', '%' . $q . '%');

                if ($nomor !== '') {
                    $w->orWhere('nomor_identitas', 'like', '%' . $nomor . '%');
                }
            });
        }

        if (in_array($jenis, ['Siswa', 'Guru'], true)) {
            $query->where('jenis_pengguna', $jenis);
        }

        $data = $query->paginate(15)->withQueryString();

        $terdaftar = User::whereIn('nomor_identitas', $data->pluck('nomor_identitas'))
            ->pluck('nomor_identitas')
            ->all();

        return view('admin.identitas.index', compact('data', 'terdaftar', 'q', 'jenis'));
    }

    public function create()
    {
        return view('admin.identitas.form', ['identitas' => new IdentitasSekolah(['aktif' => true])]);
    }

    private function aturan(?IdentitasSekolah $identitas = null): array
    {
        return [
            'jenis_pengguna' => ['required', Rule::in(['Siswa', 'Guru'])],
            'nomor_identitas' => ['required', 'string', 'max:50'],
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'kelas' => ['nullable', 'string', 'max:30'],
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->aturan());
        $data['nomor_identitas'] = IdentitasSekolah::normalisasi($data['nomor_identitas']);

        if ($data['nomor_identitas'] === '') {
            return back()->withInput()->withErrors(['nomor_identitas' => 'Nomor identitas tidak valid.']);
        }

        if (IdentitasSekolah::where('nomor_identitas', $data['nomor_identitas'])->exists()) {
            return back()->withInput()->withErrors(['nomor_identitas' => 'Nomor ini sudah ada di data master.']);
        }

        IdentitasSekolah::create($data + ['aktif' => true]);

        return redirect()->route('admin.identitas.index')->with('success', 'Data berhasil ditambahkan.');
    }

    public function edit(IdentitasSekolah $identitas)
    {
        return view('admin.identitas.form', compact('identitas'));
    }

    public function update(Request $request, IdentitasSekolah $identitas)
    {
        $data = $request->validate($this->aturan($identitas));
        $data['nomor_identitas'] = IdentitasSekolah::normalisasi($data['nomor_identitas']);

        $dipakai = IdentitasSekolah::where('nomor_identitas', $data['nomor_identitas'])
            ->where('id', '!=', $identitas->id)
            ->exists();

        if ($dipakai) {
            return back()->withInput()->withErrors(['nomor_identitas' => 'Nomor ini sudah ada di data master.']);
        }

        $punyaAkun = User::where('nomor_identitas', $identitas->nomor_identitas)->first();

        if ($punyaAkun) {
            // Akun sudah terbentuk: nomor, jenis, dan nama sudah menempel pada akun.
            // Hanya kelas yang boleh dikoreksi agar data akun tetap sinkron.
            $identitas->update(['kelas' => $data['kelas'] ?? null]);

            if ($punyaAkun->jenis_pengguna === 'Siswa') {
                $punyaAkun->update(['kelas' => $data['kelas'] ?? null]);
            }

            return redirect()->route('admin.identitas.index')
                ->with('success', 'Kelas diperbarui. Nama dan nomor tidak diubah karena akun sudah terdaftar.');
        }

        $identitas->update($data);

        return redirect()->route('admin.identitas.index')->with('success', 'Data berhasil diperbarui.');
    }

    public function toggle(IdentitasSekolah $identitas)
    {
        $identitas->update(['aktif' => ! $identitas->aktif]);

        return back()->with('success', $identitas->aktif ? 'Data diaktifkan.' : 'Data dinonaktifkan.');
    }

    public function destroy(IdentitasSekolah $identitas)
    {
        if (User::where('nomor_identitas', $identitas->nomor_identitas)->exists()) {
            return back()->with('error', 'Data ini sudah dipakai untuk akun. Nonaktifkan saja, jangan dihapus.');
        }

        $identitas->delete();

        return back()->with('success', 'Data dihapus.');
    }

    /**
     * Import CSV dengan kolom: jenis, nomor, nama, kelas (kelas boleh kosong).
     * Pemisah koma atau titik koma sama-sama dikenali.
     */
    public function import(Request $request)
    {
        $request->validate([
            'berkas' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ], [
            'berkas.required' => 'Pilih berkas CSV terlebih dahulu.',
            'berkas.mimes' => 'Berkas harus berformat CSV.',
        ]);

        $handle = fopen($request->file('berkas')->getRealPath(), 'r');

        if (! $handle) {
            return back()->with('error', 'Berkas tidak dapat dibaca.');
        }

        $baris = 0;
        $baru = 0;
        $diperbarui = 0;
        $dilewati = 0;
        $pemisah = null;

        while (($line = fgets($handle)) !== false) {
            $line = trim(preg_replace('/^\xEF\xBB\xBF/', '', $line));

            if ($line === '') {
                continue;
            }

            if ($pemisah === null) {
                $pemisah = substr_count($line, ';') > substr_count($line, ',') ? ';' : ',';
            }

            $kolom = array_map('trim', str_getcsv($line, $pemisah));
            $baris++;

            // Baris judul dilewati.
            if ($baris === 1 && in_array(strtolower($kolom[0] ?? ''), ['jenis', 'jenis_pengguna'], true)) {
                continue;
            }

            $jenis = ucfirst(strtolower($kolom[0] ?? ''));
            $nomor = IdentitasSekolah::normalisasi($kolom[1] ?? '');
            $nama = $kolom[2] ?? '';
            $kelas = ($kolom[3] ?? '') !== '' ? $kolom[3] : null;

            if (! in_array($jenis, ['Siswa', 'Guru'], true) || $nomor === '' || $nama === '') {
                $dilewati++;
                continue;
            }

            $ada = IdentitasSekolah::where('nomor_identitas', $nomor)->first();

            if ($ada) {
                // Data yang sudah dipakai akun tidak ditimpa.
                if (User::where('nomor_identitas', $nomor)->exists()) {
                    $dilewati++;
                    continue;
                }

                $ada->update(['jenis_pengguna' => $jenis, 'nama_lengkap' => $nama, 'kelas' => $kelas]);
                $diperbarui++;
            } else {
                IdentitasSekolah::create([
                    'nomor_identitas' => $nomor,
                    'nama_lengkap' => $nama,
                    'jenis_pengguna' => $jenis,
                    'kelas' => $kelas,
                    'aktif' => true,
                ]);
                $baru++;
            }
        }

        fclose($handle);

        return back()->with(
            'success',
            "Import selesai: {$baru} data baru, {$diperbarui} diperbarui, {$dilewati} dilewati."
        );
    }
}

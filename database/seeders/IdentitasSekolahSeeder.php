<?php

namespace Database\Seeders;

use App\Models\IdentitasSekolah;
use Illuminate\Database\Seeder;

/**
 * Data contoh. Ganti dengan data NIS siswa dan NIP guru yang sebenarnya
 * lewat menu Admin > Data Pengguna (bisa import CSV).
 */
class IdentitasSekolahSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['2526001', 'Ahmad Fauzi', 'Siswa', 'XII RPL C'],
            ['2526002', 'Siti Nurhaliza', 'Siswa', 'XI TKJ A'],
            ['2526003', 'Rizky Pratama', 'Siswa', 'X DKV 1'],
            ['198503122010012005', 'Sri Wahyuni, S.Pd.', 'Guru', null],
            ['197911222005011003', 'Bambang Hermawan, S.Kom.', 'Guru', null],
        ];

        foreach ($data as [$nomor, $nama, $jenis, $kelas]) {
            IdentitasSekolah::updateOrCreate(
                ['nomor_identitas' => $nomor],
                [
                    'nama_lengkap' => $nama,
                    'jenis_pengguna' => $jenis,
                    'kelas' => $kelas,
                    'aktif' => true,
                ]
            );
        }
    }
}

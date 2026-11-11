<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdentitasSekolah extends Model
{
    protected $table = 'identitas_sekolah';

    protected $fillable = [
        'nomor_identitas',
        'nama_lengkap',
        'jenis_pengguna',
        'kelas',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    /**
     * Menyeragamkan penulisan nomor: NIP boleh diketik dengan spasi atau tanda baca
     * (12.345.678.9-012.345), NIS boleh memakai spasi.
     */
    public static function normalisasi(?string $nomor): string
    {
        return strtoupper(preg_replace('/[^0-9A-Za-z]/', '', (string) $nomor));
    }

    public static function labelNomor(string $jenis): string
    {
        return $jenis === 'Guru' ? 'NIP' : 'NIS';
    }
}

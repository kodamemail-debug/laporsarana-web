<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pengaduan extends Model
{
    public const STATUS = ['Menunggu', 'Diverifikasi', 'Dikerjakan', 'Selesai', 'Ditolak'];

    public const KATEGORI = [
        'Listrik & Penerangan',
        'Air & Sanitasi',
        'Bangunan & Atap',
        'Furniture & Mebel',
        'Perangkat Elektronik',
        'Jaringan & Internet',
        'Kebersihan & Lingkungan',
        'Lainnya',
    ];

    /**
     * Pilihan lokasi pada formulir pengaduan, dikelompokkan agar mudah dicari.
     * Ubah daftar ini sesuai nama ruang yang ada di sekolah.
     */
    public const LOKASI = [
        'Laboratorium dan Bengkel' => [
            'Lab ASM 1',
            'Lab ASM 2',
            'Lab RPL 1',
            'Lab RPL 2',
            'Lab TKJ 1',
            'Lab TKJ 2',
            'Lab Multimedia / DKV',
            'Lab Bahasa',
            'Bengkel Praktik',
        ],
        'Ruang Kelas dan Kantor' => [
            'Ruang Kelas X',
            'Ruang Kelas XI',
            'Ruang Kelas XII',
            'Ruang Guru',
            'Ruang TU / Administrasi',
            'Ruang Kepala Sekolah',
            'Ruang BK',
            'Perpustakaan',
            'Ruang UKS',
        ],
        'Fasilitas Umum' => [
            'Aula',
            'Musala',
            'Kantin',
            'Toilet',
            'Koridor / Tangga',
            'Lapangan / Halaman',
            'Area Parkir',
            'Gerbang / Pagar',
            'Lainnya',
        ],
    ];

    /**
     * Daftar lokasi tanpa pengelompokan (dipakai untuk validasi).
     */
    public static function lokasiDaftar(): array
    {
        return array_merge(...array_values(self::LOKASI));
    }

    protected $table = 'pengaduan';

    protected $fillable = [
        'kode_tiket',
        'user_id',
        'judul',
        'kategori',
        'lokasi',
        'deskripsi',
        'foto',
        'status',
        'catatan_admin',
        'diverifikasi_pada',
        'tanggal_selesai',
        'rating',
        'komentar_feedback',
        'feedback_pada',
    ];

    protected function casts(): array
    {
        return [
            'diverifikasi_pada' => 'datetime',
            'tanggal_selesai' => 'datetime',
            'feedback_pada' => 'datetime',
        ];
    }

    public function pelapor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function penugasan(): HasMany
    {
        return $this->hasMany(Penugasan::class, 'pengaduan_id');
    }

    public function teknisi(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'penugasan', 'pengaduan_id', 'teknisi_id')
            ->withPivot(['status'])
            ->withTimestamps();
    }

    public function riwayat(): HasMany
    {
        return $this->hasMany(RiwayatPengaduan::class, 'pengaduan_id');
    }

    public function pesan(): HasMany
    {
        return $this->hasMany(Pesan::class, 'pengaduan_id');
    }

    public function pesanTerakhir(): HasOne
    {
        return $this->hasOne(Pesan::class, 'pengaduan_id')->latestOfMany();
    }

    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto ? asset('storage/' . $this->foto) : null;
    }

    public function getFinalAttribute(): bool
    {
        return in_array($this->status, ['Selesai', 'Ditolak'], true);
    }

    /**
     * Posisi pada progress tracker (1-4). Ditolak = 0.
     */
    public function getTahapAttribute(): int
    {
        return match ($this->status) {
            'Menunggu' => 1,
            'Diverifikasi' => 2,
            'Dikerjakan' => 3,
            'Selesai' => 4,
            default => 0,
        };
    }
}

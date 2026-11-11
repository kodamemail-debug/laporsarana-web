<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'email',
    'password',
    'role',
    'jenis_pengguna',
    'nomor_identitas',
    'kelas',
    'no_hp',
    'keahlian',
    'foto',
    'aktif',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'aktif' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'Admin';
    }

    public function isTeknisi(): bool
    {
        return $this->role === 'Teknisi';
    }

    public function isPelapor(): bool
    {
        return $this->role === 'Pelapor';
    }

    public function pengaduan(): HasMany
    {
        return $this->hasMany(Pengaduan::class, 'user_id');
    }

    public function penugasan(): HasMany
    {
        return $this->hasMany(Penugasan::class, 'teknisi_id');
    }

    public function notifikasi(): HasMany
    {
        return $this->hasMany(Notifikasi::class, 'user_id');
    }

    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto ? asset('storage/' . $this->foto) : null;
    }

    public function getInisialAttribute(): string
    {
        $kata = preg_split('/\s+/', trim((string) $this->name)) ?: [];
        $huruf = '';

        foreach (array_slice($kata, 0, 2) as $k) {
            $huruf .= mb_strtoupper(mb_substr($k, 0, 1));
        }

        return $huruf !== '' ? $huruf : '?';
    }

    /**
     * Label nomor identitas sesuai jenis pengguna.
     */
    public function getLabelIdentitasAttribute(): string
    {
        return match ($this->jenis_pengguna) {
            'Siswa' => 'NIS',
            'Guru' => 'NIP',
            default => 'ID',
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Penugasan extends Model
{
    protected $table = 'penugasan';

    protected $fillable = [
        'pengaduan_id',
        'teknisi_id',
        'ditugaskan_oleh',
        'status',
        'catatan_tindakan',
        'foto_hasil',
        'mulai_pada',
        'selesai_pada',
    ];

    protected function casts(): array
    {
        return [
            'mulai_pada' => 'datetime',
            'selesai_pada' => 'datetime',
        ];
    }

    public function pengaduan(): BelongsTo
    {
        return $this->belongsTo(Pengaduan::class, 'pengaduan_id');
    }

    public function teknisi(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teknisi_id');
    }

    public function getFotoHasilUrlAttribute(): ?string
    {
        return $this->foto_hasil ? asset('storage/' . $this->foto_hasil) : null;
    }
}

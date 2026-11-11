<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatPengaduan extends Model
{
    protected $table = 'riwayat_pengaduan';

    protected $fillable = [
        'pengaduan_id',
        'user_id',
        'judul',
        'keterangan',
        'status',
    ];

    public function pengaduan(): BelongsTo
    {
        return $this->belongsTo(Pengaduan::class, 'pengaduan_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

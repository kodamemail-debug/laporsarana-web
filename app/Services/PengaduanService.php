<?php

namespace App\Services;

use App\Models\Notifikasi;
use App\Models\Pengaduan;
use App\Models\Pesan;
use App\Models\RiwayatPengaduan;
use App\Models\User;

/**
 * Menyatukan pencatatan riwayat dan pengiriman notifikasi,
 * supaya controller tidak mengulang kode yang sama.
 */
class PengaduanService
{
    public function catat(
        Pengaduan $pengaduan,
        string $judul,
        ?string $keterangan = null,
        ?int $userId = null,
        ?string $status = null
    ): RiwayatPengaduan {
        return RiwayatPengaduan::create([
            'pengaduan_id' => $pengaduan->id,
            'user_id' => $userId,
            'judul' => $judul,
            'keterangan' => $keterangan,
            'status' => $status,
        ]);
    }

    public function notifikasi(
        int $userId,
        ?Pengaduan $pengaduan,
        string $jenis,
        string $judul,
        string $pesan
    ): Notifikasi {
        return Notifikasi::create([
            'user_id' => $userId,
            'pengaduan_id' => $pengaduan?->id,
            'jenis' => $jenis,
            'judul' => $judul,
            'pesan' => $pesan,
        ]);
    }

    public function notifikasiAdmin(?Pengaduan $pengaduan, string $jenis, string $judul, string $pesan): void
    {
        $adminIds = User::where('role', 'Admin')->where('aktif', true)->pluck('id');

        foreach ($adminIds as $id) {
            $this->notifikasi((int) $id, $pengaduan, $jenis, $judul, $pesan);
        }
    }

    /**
     * Notifikasi pesan chat tidak dikirim berulang selama
     * notifikasi pesan sebelumnya untuk laporan yang sama belum dibaca.
     */
    public function notifikasiPesan(int $userId, Pengaduan $pengaduan, string $dari): void
    {
        $sudahAda = Notifikasi::where('user_id', $userId)
            ->where('pengaduan_id', $pengaduan->id)
            ->where('jenis', 'pesan')
            ->whereNull('dibaca_pada')
            ->exists();

        if ($sudahAda) {
            return;
        }

        $this->notifikasi(
            $userId,
            $pengaduan,
            'pesan',
            'Pesan baru',
            $dari . ' mengirim pesan pada laporan "' . $pengaduan->judul . '".'
        );
    }

    /**
     * Membuat kode tiket, misalnya LS-261103-0007.
     */
    public function kodeTiket(Pengaduan $pengaduan): string
    {
        return 'LS-' . $pengaduan->created_at->format('ymd') . '-'
            . str_pad((string) $pengaduan->id, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Laporan otomatis Selesai bila semua teknisi yang ditugaskan sudah selesai.
     * Mengembalikan true jika status laporan baru saja berubah menjadi Selesai.
     */
    public function cekPenyelesaian(Pengaduan $pengaduan): bool
    {
        if ($pengaduan->final) {
            return false;
        }

        $total = $pengaduan->penugasan()->count();

        if ($total === 0) {
            return false;
        }

        $belumSelesai = $pengaduan->penugasan()->where('status', '!=', 'Selesai')->count();

        if ($belumSelesai > 0) {
            return false;
        }

        $pengaduan->status = 'Selesai';
        $pengaduan->tanggal_selesai = now();
        $pengaduan->save();

        $this->catat(
            $pengaduan,
            'Perbaikan selesai',
            'Seluruh teknisi telah menyelesaikan tugasnya.',
            null,
            'Selesai'
        );

        $this->notifikasi(
            (int) $pengaduan->user_id,
            $pengaduan,
            'status',
            'Laporan selesai ditangani',
            'Laporan "' . $pengaduan->judul . '" telah selesai diperbaiki. Kamu bisa memberi penilaian.'
        );

        $this->notifikasiAdmin(
            $pengaduan,
            'status',
            'Laporan selesai',
            'Laporan ' . $pengaduan->kode_tiket . ' telah diselesaikan seluruh teknisi.'
        );

        return true;
    }

    public function notifBelumDibaca(User $user): int
    {
        return Notifikasi::where('user_id', $user->id)->whereNull('dibaca_pada')->count();
    }

    /**
     * Jumlah pesan chat yang belum dibaca oleh pengguna.
     * Admin menghitung pesan dari pelapor; pelapor menghitung pesan dari admin.
     */
    public function pesanBelumDibaca(User $user): int
    {
        if ($user->isAdmin()) {
            return Pesan::whereNull('dibaca_pada')
                ->whereHas('pengaduan', function ($q) {
                    $q->whereColumn('pengaduan.user_id', 'pesan.pengirim_id');
                })
                ->count();
        }

        if ($user->isPelapor()) {
            return Pesan::whereNull('dibaca_pada')
                ->where('pengirim_id', '!=', $user->id)
                ->whereHas('pengaduan', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->count();
        }

        return 0;
    }
}

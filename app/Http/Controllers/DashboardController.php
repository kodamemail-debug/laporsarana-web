<?php

namespace App\Http\Controllers;

use App\Models\Pengaduan;
use App\Models\Penugasan;
use App\Models\User;
use App\Services\PengaduanService;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Angka ringkasan untuk kartu statistik. Dipakai juga oleh endpoint
     * /ringkasan supaya angka di dashboard ikut diperbarui otomatis.
     */
    private function angka(User $user): array
    {
        if ($user->isTeknisi()) {
            $per = Penugasan::where('teknisi_id', $user->id)
                ->selectRaw('status, count(*) as n')
                ->groupBy('status')
                ->pluck('n', 'status');

            return [
                'total' => (int) $per->sum(),
                'menunggu' => (int) ($per['Ditugaskan'] ?? 0),
                'proses' => (int) ($per['Dikerjakan'] ?? 0),
                'selesai' => (int) ($per['Selesai'] ?? 0),
                'ditolak' => 0,
            ];
        }

        $query = Pengaduan::query();

        if ($user->isPelapor()) {
            $query->where('user_id', $user->id);
        }

        $per = $query->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        return [
            'total' => (int) $per->sum(),
            'menunggu' => (int) ($per['Menunggu'] ?? 0),
            'proses' => (int) (($per['Diverifikasi'] ?? 0) + ($per['Dikerjakan'] ?? 0)),
            'selesai' => (int) ($per['Selesai'] ?? 0),
            'ditolak' => (int) ($per['Ditolak'] ?? 0),
        ];
    }

    public function index(PengaduanService $layanan)
    {
        $user = Auth::user();
        $angka = $this->angka($user);

        $data = [
            'user' => $user,
            'angka' => $angka,
            'notifikasi' => $user->notifikasi()->latest()->take(5)->get(),
        ];

        if ($user->isPelapor()) {
            $data['aktif'] = Pengaduan::where('user_id', $user->id)
                ->whereNotIn('status', ['Selesai', 'Ditolak'])
                ->latest()
                ->take(3)
                ->get();

            $data['terbaru'] = Pengaduan::where('user_id', $user->id)
                ->latest()
                ->take(5)
                ->get();
        }

        if ($user->isAdmin()) {
            $data['antrian'] = Pengaduan::with('pelapor')
                ->where('status', 'Menunggu')
                ->oldest()
                ->take(6)
                ->get();

            $data['tanpaTeknisi'] = Pengaduan::whereIn('status', ['Diverifikasi', 'Dikerjakan'])
                ->whereDoesntHave('penugasan')
                ->count();

            $data['perKategori'] = Pengaduan::selectRaw('kategori, count(*) as n')
                ->groupBy('kategori')
                ->orderByDesc('n')
                ->pluck('n', 'kategori');

            $data['bebanTeknisi'] = User::where('role', 'Teknisi')
                ->where('aktif', true)
                ->withCount([
                    'penugasan as tugas_aktif' => function ($q) {
                        $q->whereIn('status', ['Ditugaskan', 'Dikerjakan']);
                    },
                ])
                ->orderBy('name')
                ->get();
        }

        if ($user->isTeknisi()) {
            $data['tugasAktif'] = Penugasan::with('pengaduan.pelapor')
                ->where('teknisi_id', $user->id)
                ->whereIn('status', ['Ditugaskan', 'Dikerjakan'])
                ->latest()
                ->take(6)
                ->get();
        }

        return view('dashboard', $data);
    }

    public function ringkasan(PengaduanService $layanan)
    {
        $user = Auth::user();

        return response()->json([
            'angka' => $this->angka($user),
            'notif' => $layanan->notifBelumDibaca($user),
            'pesan' => $layanan->pesanBelumDibaca($user),
        ]);
    }
}

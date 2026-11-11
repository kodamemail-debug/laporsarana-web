<?php

namespace App\Http\Controllers;

use App\Models\Pengaduan;
use App\Models\Pesan;
use App\Models\User;
use App\Services\PengaduanService;
use Illuminate\Http\Request;

class PesanController extends Controller
{
    public function __construct(private PengaduanService $layanan)
    {
    }

    /**
     * Chat hanya untuk pemilik laporan (siswa/guru) dan admin.
     */
    private function izin(Request $request, Pengaduan $pengaduan): User
    {
        $user = $request->user();

        abort_unless(
            $user->isAdmin() || (int) $pengaduan->user_id === (int) $user->id,
            403,
            'Kamu tidak memiliki akses ke percakapan ini.'
        );

        return $user;
    }

    private function tandaiDibaca(User $user, Pengaduan $pengaduan): void
    {
        $query = Pesan::where('pengaduan_id', $pengaduan->id)->whereNull('dibaca_pada');

        if ($user->isAdmin()) {
            $query->where('pengirim_id', $pengaduan->user_id);
        } else {
            $query->where('pengirim_id', '!=', $user->id);
        }

        $query->update(['dibaca_pada' => now()]);
    }

    private function format(Pesan $pesan, User $saya): array
    {
        return [
            'id' => $pesan->id,
            'isi' => $pesan->isi,
            'nama' => $pesan->pengirim->name,
            'peran' => $pesan->pengirim->role,
            'milikku' => (int) $pesan->pengirim_id === (int) $saya->id,
            'waktu' => $pesan->created_at->translatedFormat('d M, H:i'),
        ];
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $query = Pengaduan::query()
            ->with(['pelapor', 'pesanTerakhir.pengirim'])
            ->has('pesan')
            ->withCount([
                'pesan as belum_dibaca' => function ($q) use ($user) {
                    $q->whereNull('dibaca_pada');

                    if ($user->isAdmin()) {
                        $q->whereColumn('pesan.pengirim_id', 'pengaduan.user_id');
                    } else {
                        $q->where('pesan.pengirim_id', '!=', $user->id);
                    }
                },
            ])
            ->withMax('pesan', 'created_at')
            ->orderByDesc('pesan_max_created_at');

        if ($user->isPelapor()) {
            $query->where('user_id', $user->id);
        }

        return view('pesan.index', ['percakapan' => $query->paginate(12)]);
    }

    public function show(Request $request, Pengaduan $pengaduan)
    {
        $user = $this->izin($request, $pengaduan);

        $this->tandaiDibaca($user, $pengaduan);

        $pesan = Pesan::with('pengirim')
            ->where('pengaduan_id', $pengaduan->id)
            ->orderByDesc('id')
            ->take(100)
            ->get()
            ->reverse()
            ->values();

        $pengaduan->load('pelapor');

        return view('pesan.show', [
            'pengaduan' => $pengaduan,
            'pesan' => $pesan->map(fn ($m) => $this->format($m, $user))->all(),
            'terakhirId' => $pesan->last()?->id ?? 0,
        ]);
    }

    /**
     * Dipanggil JavaScript setiap beberapa detik untuk mengambil pesan baru.
     */
    public function baru(Request $request, Pengaduan $pengaduan)
    {
        $user = $this->izin($request, $pengaduan);
        $setelah = (int) $request->query('setelah', 0);

        $baru = Pesan::with('pengirim')
            ->where('pengaduan_id', $pengaduan->id)
            ->where('id', '>', $setelah)
            ->orderBy('id')
            ->get();

        if ($baru->isNotEmpty()) {
            $this->tandaiDibaca($user, $pengaduan);
        }

        return response()->json([
            'pesan' => $baru->map(fn ($m) => $this->format($m, $user))->all(),
        ]);
    }

    public function kirim(Request $request, Pengaduan $pengaduan)
    {
        $user = $this->izin($request, $pengaduan);

        $data = $request->validate([
            'isi' => ['required', 'string', 'max:2000'],
        ], [
            'isi.required' => 'Tulis pesan terlebih dahulu.',
        ]);

        $pesan = Pesan::create([
            'pengaduan_id' => $pengaduan->id,
            'pengirim_id' => $user->id,
            'isi' => trim($data['isi']),
        ]);

        $pesan->load('pengirim');

        if ($user->isAdmin()) {
            $this->layanan->notifikasiPesan((int) $pengaduan->user_id, $pengaduan, 'Admin');
        } else {
            $adminIds = User::where('role', 'Admin')->where('aktif', true)->pluck('id');

            foreach ($adminIds as $adminId) {
                $this->layanan->notifikasiPesan((int) $adminId, $pengaduan, $user->name);
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['pesan' => $this->format($pesan, $user)]);
        }

        return redirect()->route('pesan.show', $pengaduan);
    }
}

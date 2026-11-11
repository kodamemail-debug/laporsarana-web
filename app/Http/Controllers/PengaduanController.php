<?php

namespace App\Http\Controllers;

use App\Models\Pengaduan;
use App\Models\User;
use App\Services\PengaduanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PengaduanController extends Controller
{
    public function __construct(private PengaduanService $layanan)
    {
    }

    /**
     * Daftar pengaduan: pelapor melihat miliknya, admin melihat semuanya.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status');
        $kategori = $request->query('kategori');
        $kolom = $request->query('urut', 'created_at');
        $arah = $request->query('arah', 'desc') === 'asc' ? 'asc' : 'desc';

        if (! in_array($kolom, ['created_at', 'judul', 'status', 'kategori'], true)) {
            $kolom = 'created_at';
        }

        $query = Pengaduan::query()->with(['pelapor', 'teknisi']);

        if ($user->isPelapor()) {
            $query->where('user_id', $user->id);
        }

        if ($q !== '') {
            $query->where(function ($w) use ($q, $user) {
                $w->where('judul', 'like', '%' . $q . '%')
                    ->orWhere('kode_tiket', 'like', '%' . $q . '%')
                    ->orWhere('lokasi', 'like', '%' . $q . '%');

                if ($user->isAdmin()) {
                    $w->orWhereHas('pelapor', function ($p) use ($q) {
                        $p->where('name', 'like', '%' . $q . '%');
                    });
                }
            });
        }

        if ($status && in_array($status, Pengaduan::STATUS, true)) {
            $query->where('status', $status);
        }

        if ($kategori && in_array($kategori, Pengaduan::KATEGORI, true)) {
            $query->where('kategori', $kategori);
        }

        $pengaduan = $query->orderBy($kolom, $arah)->paginate(10)->withQueryString();

        return view('pengaduan.index', [
            'pengaduan' => $pengaduan,
            'q' => $q,
            'status' => $status,
            'kategori' => $kategori,
            'urut' => $kolom,
            'arah' => $arah,
        ]);
    }

    public function create()
    {
        return view('pengaduan.create', [
            'kategoriList' => Pengaduan::KATEGORI,
            'lokasiList' => Pengaduan::LOKASI,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'judul' => ['required', 'string', 'max:150'],
            'kategori' => ['required', Rule::in(Pengaduan::KATEGORI)],
            'lokasi' => ['required', Rule::in(Pengaduan::lokasiDaftar())],
            'detail_lokasi' => ['nullable', 'string', 'max:100'],
            'deskripsi' => ['required', 'string', 'min:10', 'max:3000'],
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'foto.required' => 'Foto bukti kerusakan wajib diunggah.',
            'foto.image' => 'Berkas harus berupa gambar.',
            'foto.mimes' => 'Format foto harus JPG, PNG, atau WEBP.',
            'foto.max' => 'Ukuran foto maksimal 5 MB.',
            'lokasi.required' => 'Pilih lokasi kerusakan.',
            'lokasi.in' => 'Pilih lokasi dari daftar yang tersedia.',
            'deskripsi.min' => 'Deskripsi minimal 10 karakter agar teknisi paham kondisinya.',
        ]);

        $path = $request->file('foto')->store('pengaduan', 'public');
        $user = Auth::user();

        try {
            $pengaduan = DB::transaction(function () use ($data, $path, $user) {
                $pengaduan = Pengaduan::create([
                    'user_id' => $user->id,
                    'judul' => $data['judul'],
                    'kategori' => $data['kategori'],
                    // Area dari dropdown, ditambah detail bila diisi (contoh: "Ruang Kelas — XII RPL C, lantai 2").
                    'lokasi' => $data['lokasi'] . (! empty($data['detail_lokasi']) ? ' — ' . trim($data['detail_lokasi']) : ''),
                    'deskripsi' => $data['deskripsi'],
                    'foto' => $path,
                    'status' => 'Menunggu',
                ]);

                $pengaduan->kode_tiket = $this->layanan->kodeTiket($pengaduan);
                $pengaduan->save();

                $this->layanan->catat(
                    $pengaduan,
                    'Laporan dikirim',
                    'Menunggu verifikasi admin.',
                    $user->id,
                    'Menunggu'
                );

                $this->layanan->notifikasiAdmin(
                    $pengaduan,
                    'baru',
                    'Pengaduan baru',
                    $user->name . ' melaporkan: ' . $pengaduan->judul . ' (' . $pengaduan->lokasi . ').'
                );

                return $pengaduan;
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($path);

            throw $e;
        }

        return redirect()
            ->route('pengaduan.show', $pengaduan)
            ->with('success', 'Pengaduan terkirim dengan nomor ' . $pengaduan->kode_tiket . '.');
    }

    public function show(Pengaduan $pengaduan)
    {
        $user = Auth::user();

        $boleh = match ($user->role) {
            'Admin' => true,
            'Pelapor' => (int) $pengaduan->user_id === (int) $user->id,
            'Teknisi' => $pengaduan->penugasan()->where('teknisi_id', $user->id)->exists(),
            default => false,
        };

        abort_unless($boleh, 403, 'Kamu tidak memiliki akses ke pengaduan ini.');

        $pengaduan->load(['pelapor', 'penugasan.teknisi', 'riwayat.user']);

        $data = [
            'pengaduan' => $pengaduan,
            'riwayat' => $pengaduan->riwayat->sortBy('id')->values(),
        ];

        if ($user->isAdmin()) {
            $sudah = $pengaduan->penugasan->pluck('teknisi_id')->all();

            $data['teknisiTersedia'] = User::where('role', 'Teknisi')
                ->where('aktif', true)
                ->whereNotIn('id', $sudah)
                ->withCount([
                    'penugasan as tugas_aktif' => function ($q) {
                        $q->whereIn('status', ['Ditugaskan', 'Dikerjakan']);
                    },
                ])
                ->orderBy('name')
                ->get();
        }

        if ($user->isTeknisi()) {
            $data['penugasanSaya'] = $pengaduan->penugasan->firstWhere('teknisi_id', $user->id);
        }

        return view('pengaduan.show', $data);
    }

    public function feedback(Request $request, Pengaduan $pengaduan)
    {
        $user = Auth::user();

        abort_unless((int) $pengaduan->user_id === (int) $user->id, 403);

        if ($pengaduan->status !== 'Selesai' || $pengaduan->rating !== null) {
            return back()->with('error', 'Penilaian hanya dapat diberikan sekali setelah laporan selesai.');
        }

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'komentar_feedback' => ['nullable', 'string', 'max:1000'],
        ], [
            'rating.required' => 'Pilih penilaian bintang 1 sampai 5.',
        ]);

        $pengaduan->update([
            'rating' => $data['rating'],
            'komentar_feedback' => $data['komentar_feedback'] ?? null,
            'feedback_pada' => now(),
        ]);

        $this->layanan->catat($pengaduan, 'Penilaian diberikan', 'Pelapor memberi nilai ' . $data['rating'] . ' dari 5.', $user->id);

        return back()->with('success', 'Terima kasih, penilaianmu sudah tersimpan.');
    }
}

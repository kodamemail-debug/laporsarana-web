<?php

namespace App\Http\Controllers;

use App\Models\Penugasan;
use App\Services\PengaduanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TugasController extends Controller
{
    public function __construct(private PengaduanService $layanan)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status');

        $query = Penugasan::with('pengaduan.pelapor')->where('teknisi_id', $user->id);

        if ($status && in_array($status, ['Ditugaskan', 'Dikerjakan', 'Selesai'], true)) {
            $query->where('status', $status);
        }

        if ($q !== '') {
            $query->whereHas('pengaduan', function ($p) use ($q) {
                $p->where('judul', 'like', '%' . $q . '%')
                    ->orWhere('kode_tiket', 'like', '%' . $q . '%')
                    ->orWhere('lokasi', 'like', '%' . $q . '%');
            });
        }

        // Tugas yang belum selesai ditampilkan lebih dulu.
        $tugas = $query
            ->orderByRaw("FIELD(status, 'Dikerjakan', 'Ditugaskan', 'Selesai')")
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('tugas.index', compact('tugas', 'q', 'status'));
    }

    private function milikSaya(Request $request, Penugasan $penugasan): void
    {
        abort_unless((int) $penugasan->teknisi_id === (int) $request->user()->id, 403, 'Ini bukan tugasmu.');
    }

    public function mulai(Request $request, Penugasan $penugasan)
    {
        $this->milikSaya($request, $penugasan);
        $pengaduan = $penugasan->pengaduan;

        if ($penugasan->status !== 'Ditugaskan' || $pengaduan->final) {
            return back()->with('error', 'Tugas ini tidak dapat dimulai.');
        }

        $user = $request->user();

        DB::transaction(function () use ($penugasan, $pengaduan, $user) {
            $penugasan->status = 'Dikerjakan';
            $penugasan->mulai_pada = now();
            $penugasan->save();

            $this->layanan->catat($pengaduan, 'Pengerjaan dimulai', $user->name . ' mulai mengerjakan.', $user->id);

            if ($pengaduan->status !== 'Dikerjakan') {
                $pengaduan->status = 'Dikerjakan';
                $pengaduan->save();

                $this->layanan->catat($pengaduan, 'Status: Dikerjakan', null, $user->id, 'Dikerjakan');

                $this->layanan->notifikasi(
                    (int) $pengaduan->user_id,
                    $pengaduan,
                    'status',
                    'Laporan sedang dikerjakan',
                    'Teknisi mulai memperbaiki: ' . $pengaduan->judul . '.'
                );
            }
        });

        return back()->with('success', 'Status tugas diperbarui menjadi Dikerjakan.');
    }

    public function progres(Request $request, Penugasan $penugasan)
    {
        $this->milikSaya($request, $penugasan);

        if ($penugasan->status !== 'Dikerjakan') {
            return back()->with('error', 'Catatan progres hanya bisa ditambah saat tugas berstatus Dikerjakan.');
        }

        $data = $request->validate([
            'keterangan' => ['required', 'string', 'max:1000'],
        ], [
            'keterangan.required' => 'Tulis catatan progres terlebih dahulu.',
        ]);

        $user = $request->user();
        $this->layanan->catat($penugasan->pengaduan, 'Catatan progres', $data['keterangan'], $user->id);

        return back()->with('success', 'Catatan progres ditambahkan.');
    }

    public function selesai(Request $request, Penugasan $penugasan)
    {
        $this->milikSaya($request, $penugasan);
        $pengaduan = $penugasan->pengaduan;

        if ($penugasan->status !== 'Dikerjakan' || $pengaduan->final) {
            return back()->with('error', 'Mulai kerjakan tugas terlebih dahulu sebelum menandainya selesai.');
        }

        $data = $request->validate([
            'catatan_tindakan' => ['required', 'string', 'max:2000'],
            'foto_hasil' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'catatan_tindakan.required' => 'Keterangan tindakan perbaikan wajib diisi.',
            'foto_hasil.required' => 'Foto hasil perbaikan wajib diunggah.',
            'foto_hasil.image' => 'Berkas harus berupa gambar.',
            'foto_hasil.mimes' => 'Format foto harus JPG, PNG, atau WEBP.',
            'foto_hasil.max' => 'Ukuran foto maksimal 5 MB.',
        ]);

        $path = $request->file('foto_hasil')->store('hasil-perbaikan', 'public');
        $user = $request->user();

        try {
            DB::transaction(function () use ($penugasan, $pengaduan, $data, $path, $user) {
                $penugasan->update([
                    'status' => 'Selesai',
                    'catatan_tindakan' => $data['catatan_tindakan'],
                    'foto_hasil' => $path,
                    'selesai_pada' => now(),
                ]);

                $this->layanan->catat(
                    $pengaduan,
                    'Tugas selesai: ' . $user->name,
                    $data['catatan_tindakan'],
                    $user->id
                );

                $this->layanan->cekPenyelesaian($pengaduan->fresh());
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($path);

            throw $e;
        }

        return redirect()
            ->route('tugas.index')
            ->with('success', 'Tugas ditandai selesai. Terima kasih.');
    }
}

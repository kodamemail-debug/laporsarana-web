<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengaduan;
use App\Models\Penugasan;
use App\Models\User;
use App\Services\PengaduanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PengaduanAdminController extends Controller
{
    public function __construct(private PengaduanService $layanan)
    {
    }

    private function aturanTeknisi(): array
    {
        return [
            'teknisi_ids' => ['required', 'array', 'min:1'],
            'teknisi_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('users', 'id')->where(function ($q) {
                    $q->where('role', 'Teknisi')->where('aktif', true);
                }),
            ],
        ];
    }

    /**
     * Menugaskan teknisi (satu atau lebih) pada laporan.
     * Teknisi yang sudah pernah ditugaskan pada laporan ini dilewati.
     */
    private function tugaskan(Pengaduan $pengaduan, array $ids, int $adminId): array
    {
        $sudah = $pengaduan->penugasan()->pluck('teknisi_id')->all();
        $baru = array_values(array_diff($ids, $sudah));
        $nama = [];

        foreach ($baru as $teknisiId) {
            Penugasan::create([
                'pengaduan_id' => $pengaduan->id,
                'teknisi_id' => $teknisiId,
                'ditugaskan_oleh' => $adminId,
                'status' => 'Ditugaskan',
            ]);

            $teknisi = User::find($teknisiId);
            $nama[] = $teknisi->name;

            $this->layanan->notifikasi(
                (int) $teknisiId,
                $pengaduan,
                'tugas',
                'Tugas baru',
                'Kamu ditugaskan menangani laporan ' . $pengaduan->kode_tiket . ': ' . $pengaduan->judul . '.'
            );
        }

        return $nama;
    }

    public function verifikasi(Request $request, Pengaduan $pengaduan)
    {
        if ($pengaduan->status !== 'Menunggu') {
            return back()->with('error', 'Hanya laporan berstatus Menunggu yang dapat diverifikasi.');
        }

        $data = $request->validate($this->aturanTeknisi() + [
            'catatan_admin' => ['nullable', 'string', 'max:2000'],
        ], [
            'teknisi_ids.required' => 'Pilih minimal satu teknisi untuk menangani laporan ini.',
            'teknisi_ids.min' => 'Pilih minimal satu teknisi untuk menangani laporan ini.',
        ]);

        $admin = $request->user();

        DB::transaction(function () use ($pengaduan, $data, $admin) {
            $pengaduan->status = 'Diverifikasi';
            $pengaduan->diverifikasi_pada = now();
            $pengaduan->catatan_admin = $data['catatan_admin'] ?? null;
            $pengaduan->save();

            $this->layanan->catat(
                $pengaduan,
                'Laporan diverifikasi',
                $data['catatan_admin'] ?? 'Laporan dinyatakan valid dan akan segera ditangani.',
                $admin->id,
                'Diverifikasi'
            );

            $nama = $this->tugaskan($pengaduan, $data['teknisi_ids'], $admin->id);

            $this->layanan->catat(
                $pengaduan,
                'Teknisi ditugaskan',
                implode(', ', $nama),
                $admin->id
            );

            $this->layanan->notifikasi(
                (int) $pengaduan->user_id,
                $pengaduan,
                'status',
                'Laporan diverifikasi',
                'Laporan "' . $pengaduan->judul . '" telah diverifikasi dan diteruskan ke teknisi.'
            );
        });

        return back()->with('success', 'Laporan diverifikasi dan teknisi sudah ditugaskan.');
    }

    public function tolak(Request $request, Pengaduan $pengaduan)
    {
        if ($pengaduan->status !== 'Menunggu') {
            return back()->with('error', 'Hanya laporan berstatus Menunggu yang dapat ditolak.');
        }

        $data = $request->validate([
            'catatan_admin' => ['required', 'string', 'max:2000'],
        ], [
            'catatan_admin.required' => 'Alasan penolakan wajib diisi agar pelapor paham.',
        ]);

        $admin = $request->user();

        DB::transaction(function () use ($pengaduan, $data, $admin) {
            $pengaduan->status = 'Ditolak';
            $pengaduan->catatan_admin = $data['catatan_admin'];
            $pengaduan->save();

            $this->layanan->catat($pengaduan, 'Laporan ditolak', $data['catatan_admin'], $admin->id, 'Ditolak');

            $this->layanan->notifikasi(
                (int) $pengaduan->user_id,
                $pengaduan,
                'status',
                'Laporan ditolak',
                'Laporan "' . $pengaduan->judul . '" ditolak. Alasan: ' . $data['catatan_admin']
            );
        });

        return back()->with('success', 'Laporan ditolak dan pelapor sudah diberi tahu.');
    }

    public function tambahTeknisi(Request $request, Pengaduan $pengaduan)
    {
        if (! in_array($pengaduan->status, ['Diverifikasi', 'Dikerjakan'], true)) {
            return back()->with('error', 'Teknisi hanya dapat ditambahkan pada laporan yang sedang diproses.');
        }

        $data = $request->validate($this->aturanTeknisi(), [
            'teknisi_ids.required' => 'Pilih minimal satu teknisi.',
            'teknisi_ids.min' => 'Pilih minimal satu teknisi.',
        ]);

        $admin = $request->user();

        DB::transaction(function () use ($pengaduan, $data, $admin) {
            $nama = $this->tugaskan($pengaduan, $data['teknisi_ids'], $admin->id);

            if ($nama) {
                $this->layanan->catat($pengaduan, 'Teknisi ditambahkan', implode(', ', $nama), $admin->id);
            }
        });

        return back()->with('success', 'Teknisi berhasil ditambahkan.');
    }

    public function hapusTeknisi(Request $request, Pengaduan $pengaduan, Penugasan $penugasan)
    {
        abort_unless((int) $penugasan->pengaduan_id === (int) $pengaduan->id, 404);

        if ($pengaduan->final) {
            return back()->with('error', 'Laporan ini sudah final.');
        }

        if ($penugasan->status !== 'Ditugaskan') {
            return back()->with('error', 'Teknisi yang sudah mulai bekerja tidak dapat dicabut penugasannya.');
        }

        $admin = $request->user();
        $teknisi = $penugasan->teknisi;

        DB::transaction(function () use ($pengaduan, $penugasan, $teknisi, $admin) {
            $penugasan->delete();

            $this->layanan->catat($pengaduan, 'Penugasan dicabut', $teknisi->name, $admin->id);

            $this->layanan->notifikasi(
                (int) $teknisi->id,
                $pengaduan,
                'tugas',
                'Penugasan dicabut',
                'Penugasanmu pada laporan ' . $pengaduan->kode_tiket . ' dicabut oleh admin.'
            );

            // Bila teknisi yang tersisa sudah selesai semua, laporan ikut selesai.
            $this->layanan->cekPenyelesaian($pengaduan->fresh());
        });

        return back()->with('success', 'Penugasan dicabut.');
    }
}

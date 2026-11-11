<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengaduan;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LaporanController extends Controller
{
    private function saring(Request $request)
    {
        $query = Pengaduan::query()->with(['pelapor', 'penugasan.teknisi']);

        $dari = $request->query('dari');
        $sampai = $request->query('sampai');
        $status = $request->query('status');
        $kategori = $request->query('kategori');

        if ($dari) {
            $query->whereDate('created_at', '>=', $dari);
        }

        if ($sampai) {
            $query->whereDate('created_at', '<=', $sampai);
        }

        if ($status && in_array($status, Pengaduan::STATUS, true)) {
            $query->where('status', $status);
        }

        if ($kategori && in_array($kategori, Pengaduan::KATEGORI, true)) {
            $query->where('kategori', $kategori);
        }

        return $query;
    }

    /**
     * Angka ringkasan dan data grafik, dipakai halaman rekap dan halaman cetak.
     */
    private function ringkasan(Request $request): array
    {
        $query = $this->saring($request);

        $perStatusRaw = (clone $query)->reorder()
            ->selectRaw('status, count(*) as n')
            ->groupBy('status')
            ->pluck('n', 'status');

        $perStatus = [];
        foreach (Pengaduan::STATUS as $s) {
            $perStatus[$s] = (int) ($perStatusRaw[$s] ?? 0);
        }

        $perKategori = (clone $query)->reorder()
            ->selectRaw('kategori, count(*) as n')
            ->groupBy('kategori')
            ->orderByDesc('n')
            ->pluck('n', 'kategori')
            ->map(fn ($n) => (int) $n)
            ->all();

        // Tren per bulan (maksimal 12 bulan terakhir pada data yang tersaring).
        $trenRaw = (clone $query)->reorder()
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as bulan, count(*) as n")
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->pluck('n', 'bulan')
            ->slice(-12);

        $trenLabel = [];
        $trenNilai = [];
        foreach ($trenRaw as $bulan => $n) {
            $trenLabel[] = Carbon::parse($bulan . '-01')->translatedFormat('M Y');
            $trenNilai[] = (int) $n;
        }

        $rataJam = (clone $query)->reorder()
            ->where('status', 'Selesai')
            ->whereNotNull('tanggal_selesai')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, tanggal_selesai)) as jam')
            ->value('jam');

        $rataRating = (clone $query)->reorder()->whereNotNull('rating')->avg('rating');

        return [
            'query' => $query,
            'total' => array_sum($perStatus),
            'perStatus' => $perStatus,
            'perKategori' => $perKategori,
            'trenLabel' => $trenLabel,
            'trenNilai' => $trenNilai,
            'rataJam' => $rataJam !== null ? (float) $rataJam : null,
            'rataRating' => $rataRating !== null ? (float) $rataRating : null,
            'filter' => $request->only(['dari', 'sampai', 'status', 'kategori']),
        ];
    }

    public function index(Request $request)
    {
        $data = $this->ringkasan($request);
        $data['pengaduan'] = $data['query']->latest()->paginate(15)->withQueryString();
        unset($data['query']);

        return view('admin.laporan.index', $data);
    }

    /**
     * Halaman rapi untuk dicetak atau disimpan sebagai PDF lewat browser.
     */
    public function cetak(Request $request)
    {
        $data = $this->ringkasan($request);
        $data['pengaduan'] = $data['query']->latest()->limit(1000)->get();
        unset($data['query']);
        $data['auto'] = $request->boolean('auto');

        return view('admin.laporan.cetak', $data);
    }

    /**
     * Ekspor CSV (UTF-8 dengan BOM agar terbaca benar di Excel).
     */
    public function export(Request $request)
    {
        $query = $this->saring($request)->latest();
        $nama = 'laporan-pengaduan-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Kode Tiket', 'Tanggal Lapor', 'Waktu Lapor (WIB)', 'Pelapor', 'Jenis', 'Kategori', 'Judul', 'Lokasi',
                'Status', 'Teknisi', 'Tanggal Selesai', 'Waktu Selesai (WIB)', 'Rating',
            ]);

            $query->chunk(200, function ($baris) use ($out) {
                foreach ($baris as $p) {
                    fputcsv($out, [
                        $p->kode_tiket,
                        $p->created_at->format('Y-m-d'),
                        $p->created_at->format('H:i:s'),
                        $p->pelapor?->name,
                        $p->pelapor?->jenis_pengguna,
                        $p->kategori,
                        $p->judul,
                        $p->lokasi,
                        $p->status,
                        $p->penugasan->map(fn ($t) => $t->teknisi?->name)->filter()->implode(', '),
                        $p->tanggal_selesai?->format('Y-m-d'),
                        $p->tanggal_selesai?->format('H:i:s'),
                        $p->rating,
                    ]);
                }
            });

            fclose($out);
        }, $nama, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}

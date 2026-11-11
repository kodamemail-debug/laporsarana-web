<?php

namespace App\Http\Controllers;

use App\Models\Notifikasi;
use Illuminate\Http\Request;

class NotifikasiController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $hanyaBaru = $request->query('tampil') === 'baru';

        $query = Notifikasi::where('user_id', $user->id)->latest();

        if ($hanyaBaru) {
            $query->whereNull('dibaca_pada');
        }

        return view('notifikasi.index', [
            'notifikasi' => $query->paginate(15)->withQueryString(),
            'hanyaBaru' => $hanyaBaru,
        ]);
    }

    /**
     * Menandai notifikasi sudah dibaca lalu membuka halaman terkait.
     */
    public function buka(Request $request, Notifikasi $notifikasi)
    {
        $user = $request->user();

        abort_unless((int) $notifikasi->user_id === (int) $user->id, 403);

        if ($notifikasi->dibaca_pada === null) {
            $notifikasi->update(['dibaca_pada' => now()]);
        }

        if (! $notifikasi->pengaduan_id) {
            return redirect()->route('notifikasi.index');
        }

        if ($notifikasi->jenis === 'pesan' && ! $user->isTeknisi()) {
            return redirect()->route('pesan.show', $notifikasi->pengaduan_id);
        }

        return redirect()->route('pengaduan.show', $notifikasi->pengaduan_id);
    }

    public function bacaSemua(Request $request)
    {
        Notifikasi::where('user_id', $request->user()->id)
            ->whereNull('dibaca_pada')
            ->update(['dibaca_pada' => now()]);

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Penugasan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TeknisiController extends Controller
{
    public function index()
    {
        $teknisi = User::where('role', 'Teknisi')
            ->withCount([
                'penugasan as tugas_aktif' => function ($q) {
                    $q->whereIn('status', ['Ditugaskan', 'Dikerjakan']);
                },
                'penugasan as tugas_selesai' => function ($q) {
                    $q->where('status', 'Selesai');
                },
            ])
            ->orderByDesc('aktif')
            ->orderBy('name')
            ->get();

        // Penugasan yang sedang berjalan, untuk memantau siapa mengerjakan apa.
        $berjalan = Penugasan::with(['pengaduan', 'teknisi'])
            ->whereIn('status', ['Ditugaskan', 'Dikerjakan'])
            ->latest()
            ->take(20)
            ->get();

        return view('admin.teknisi.index', compact('teknisi', 'berjalan'));
    }

    public function create()
    {
        return view('admin.teknisi.form', ['teknisi' => new User()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'no_hp' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s]{8,20}$/'],
            'keahlian' => ['nullable', 'string', 'max:100'],
        ], [
            'password.min' => 'Password minimal 8 karakter.',
            'no_hp.regex' => 'Nomor HP hanya boleh berisi angka, spasi, tanda + atau -.',
        ]);

        User::create($data + ['role' => 'Teknisi', 'aktif' => true]);

        return redirect()->route('admin.teknisi.index')->with('success', 'Akun teknisi berhasil dibuat.');
    }

    public function edit(User $teknisi)
    {
        abort_unless($teknisi->isTeknisi(), 404);

        return view('admin.teknisi.form', compact('teknisi'));
    }

    public function update(Request $request, User $teknisi)
    {
        abort_unless($teknisi->isTeknisi(), 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($teknisi->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'no_hp' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s]{8,20}$/'],
            'keahlian' => ['nullable', 'string', 'max:100'],
        ], [
            'password.min' => 'Password minimal 8 karakter.',
            'no_hp.regex' => 'Nomor HP hanya boleh berisi angka, spasi, tanda + atau -.',
        ]);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $teknisi->update($data);

        return redirect()->route('admin.teknisi.index')->with('success', 'Data teknisi diperbarui.');
    }

    public function toggle(User $teknisi)
    {
        abort_unless($teknisi->isTeknisi(), 404);

        $teknisi->update(['aktif' => ! $teknisi->aktif]);

        return back()->with(
            'success',
            $teknisi->aktif ? 'Akun teknisi diaktifkan.' : 'Akun teknisi dinonaktifkan.'
        );
    }
}

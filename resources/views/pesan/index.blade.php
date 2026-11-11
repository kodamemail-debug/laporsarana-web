@extends('layouts.app')

@section('title', 'Pesan')

@section('content')
@php $admin = auth()->user()->isAdmin(); @endphp

<div class="page-head">
    <div>
        <div class="eyebrow">Percakapan</div>
        <h1>Pesan</h1>
        <p>{{ $admin ? 'Percakapan dengan siswa dan guru, dikelompokkan per laporan.' : 'Percakapanmu dengan admin sarpras, dikelompokkan per laporan.' }}</p>
    </div>
</div>

<div class="card">
    @if ($percakapan->isEmpty())
        <div class="empty">
            <strong>Belum ada percakapan</strong>
            @if ($admin)
                Buka sebuah laporan lalu pilih &ldquo;Pesan ke pelapor&rdquo; untuk memulai.
            @else
                Buka salah satu laporanmu lalu pilih &ldquo;Diskusi dengan admin&rdquo; untuk bertanya atau menambah informasi.
            @endif
        </div>
    @else
        <ul class="list">
            @foreach ($percakapan as $p)
                <li class="{{ $p->belum_dibaca > 0 ? 'unread' : '' }}">
                    <a class="row" href="{{ route('pesan.show', $p) }}">
                        <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-start;">
                            <div style="min-width:0;">
                                <strong>{{ $p->judul }}</strong>
                                <span class="sub mono">{{ $p->kode_tiket }}@if ($admin) &middot; {{ $p->pelapor?->name }}@endif</span>
                                @if ($p->pesanTerakhir)
                                    <span class="sub" style="display:block; margin-top:3px;">
                                        {{ $p->pesanTerakhir->pengirim_id === auth()->id() ? 'Kamu: ' : '' }}{{ \Illuminate\Support\Str::limit($p->pesanTerakhir->isi, 110) }}
                                    </span>
                                @endif
                            </div>
                            <div style="text-align:right; flex:none;">
                                <span class="sub">{{ $p->pesanTerakhir?->created_at->diffForHumans() }}</span>
                                @if ($p->belum_dibaca > 0)
                                    <span class="count" style="display:inline-grid; margin-top:4px; min-width:20px; height:20px; padding:0 6px; border-radius:10px; background:var(--accent); color:var(--accent-ink); font-size:11px; font-weight:700; place-items:center;">{{ $p->belum_dibaca }}</span>
                                @endif
                            </div>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>
        {{ $percakapan->links() }}
    @endif
</div>
@endsection

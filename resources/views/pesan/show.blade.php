@extends('layouts.app')

@section('title', 'Pesan')

@section('content')
@php $admin = auth()->user()->isAdmin(); @endphp

<a href="{{ route('pesan.index') }}" class="back"><x-icon name="back" :size="15" /> Semua pesan</a>

<div class="page-head">
    <div>
        <div class="eyebrow mono">{{ $pengaduan->kode_tiket }}</div>
        <h1>{{ $pengaduan->judul }}</h1>
        <p>{{ $admin ? 'Percakapan dengan ' . $pengaduan->pelapor->name : 'Percakapan dengan admin sarpras' }}</p>
    </div>
    <div class="page-actions">
        <x-badge :status="$pengaduan->status" />
        <a href="{{ route('pengaduan.show', $pengaduan) }}" class="btn btn-outline btn-sm">
            <x-icon name="file" :size="15" /> Lihat laporan
        </a>
    </div>
</div>

<div class="card">
    <div class="chat" id="chat"
         data-url-baru="{{ route('pesan.baru', $pengaduan) }}"
         data-url-kirim="{{ route('pesan.kirim', $pengaduan) }}"
         data-terakhir="{{ $terakhirId }}">
        <div class="chat-log" id="chat-log">
            @forelse ($pesan as $m)
                <div class="msg {{ $m['milikku'] ? 'mine' : '' }}" data-id="{{ $m['id'] }}">
                    <div class="who">{{ $m['milikku'] ? 'Kamu' : $m['nama'] . ($m['peran'] === 'Admin' ? ' · Admin' : '') }}</div>
                    <div class="bubble">{{ $m['isi'] }}</div>
                    <div class="time">{{ $m['waktu'] }}</div>
                </div>
            @empty
                <div class="chat-empty">
                    <strong style="display:block; color:var(--ink);">Belum ada pesan</strong>
                    Tulis pesan pertama di bawah. Percakapan ini hanya terlihat oleh pelapor dan admin.
                </div>
            @endforelse
        </div>

        <form class="chat-form" id="chat-form" method="POST" action="{{ route('pesan.kirim', $pengaduan) }}">
            @csrf
            <textarea name="isi" rows="1" maxlength="2000" required placeholder="Tulis pesan…" aria-label="Tulis pesan"></textarea>
            <button type="submit" class="btn" aria-label="Kirim pesan"><x-icon name="send" :size="18" /> <span class="btn-txt">Kirim</span></button>
        </form>
        <div class="chat-hint">Tekan Enter untuk mengirim, Shift + Enter untuk baris baru.</div>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Notifikasi')

@section('content')
<div class="page-head">
    <div>
        <div class="eyebrow">Pemberitahuan</div>
        <h1>Notifikasi</h1>
    </div>
    <div class="page-actions">
        <a href="{{ route('notifikasi.index', $hanyaBaru ? [] : ['tampil' => 'baru']) }}" class="btn btn-outline btn-sm">
            {{ $hanyaBaru ? 'Tampilkan semua' : 'Hanya yang belum dibaca' }}
        </a>
        <form method="POST" action="{{ route('notifikasi.baca-semua') }}">
            @csrf
            <button type="submit" class="btn btn-outline btn-sm">Tandai semua dibaca</button>
        </form>
    </div>
</div>

<div class="card">
    @if ($notifikasi->isEmpty())
        <div class="empty"><strong>{{ $hanyaBaru ? 'Semua sudah dibaca' : 'Belum ada notifikasi' }}</strong></div>
    @else
        <ul class="list">
            @foreach ($notifikasi as $n)
                <li class="{{ $n->dibaca_pada ? '' : 'unread' }}">
                    <a class="row" href="{{ route('notifikasi.buka', $n) }}">
                        <strong>{{ $n->judul }}</strong>
                        <span class="sub" style="display:block; color:var(--ink-2);">{{ $n->pesan }}</span>
                        <span class="sub">{{ $n->created_at->diffForHumans() }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
        {{ $notifikasi->links() }}
    @endif
</div>
@endsection

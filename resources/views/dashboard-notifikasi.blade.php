<div class="card">
    <div class="card-head"><h2>Pemberitahuan terbaru</h2></div>
    @if ($notifikasi->isEmpty())
        <div class="empty">Belum ada pemberitahuan.</div>
    @else
        <ul class="list">
            @foreach ($notifikasi as $n)
                <li class="{{ $n->dibaca_pada ? '' : 'unread' }}">
                    <a class="row" href="{{ route('notifikasi.buka', $n) }}">
                        <strong>{{ $n->judul }}</strong>
                        <span class="sub">{{ \Illuminate\Support\Str::limit($n->pesan, 90) }}</span>
                        <span class="sub" style="display:block;">{{ $n->created_at->diffForHumans() }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</div>

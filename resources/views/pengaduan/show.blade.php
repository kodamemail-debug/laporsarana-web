@extends('layouts.app')

@section('title', $pengaduan->kode_tiket ?? 'Detail Pengaduan')

@section('content')
@php
    $me = auth()->user();
    $admin = $me->isAdmin();
    $pelapor = $me->isPelapor();
    $teknisi = $me->isTeknisi();
    $kembali = $teknisi ? route('tugas.index') : route('pengaduan.index');
@endphp

<a href="{{ $kembali }}" class="back"><x-icon name="back" :size="15" /> Kembali ke daftar</a>

<div class="page-head">
    <div>
        <div class="eyebrow mono">{{ $pengaduan->kode_tiket }}</div>
        <h1>{{ $pengaduan->judul }}</h1>
        <p>
            <span class="tag">{{ $pengaduan->kategori }}</span>
            &nbsp;<x-icon name="pin" :size="14" /> {{ $pengaduan->lokasi }}
        </p>
    </div>
    <div class="page-actions">
        <x-badge :status="$pengaduan->status" />
        @if ($admin || $pelapor)
            <a href="{{ route('pesan.show', $pengaduan) }}" class="btn btn-outline btn-sm">
                <x-icon name="chat" :size="15" /> {{ $admin ? 'Pesan ke pelapor' : 'Diskusi dengan admin' }}
            </a>
        @endif
    </div>
</div>

<div class="grid grid-main">
    {{-- ================= KOLOM UTAMA ================= --}}
    <div class="stack">
        <div class="card">
            <div class="card-head"><h2>Status penanganan</h2></div>
            <div class="card-body">
                <x-stepper :status="$pengaduan->status" />

                @if ($pengaduan->catatan_admin)
                    <div class="alert {{ $pengaduan->status === 'Ditolak' ? 'alert-danger' : 'alert-info' }}" style="margin-top: 20px;">
                        <x-icon name="info" :size="18" />
                        <div>
                            <strong style="display:block;">{{ $pengaduan->status === 'Ditolak' ? 'Alasan penolakan' : 'Catatan admin' }}</strong>
                            {{ $pengaduan->catatan_admin }}
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h2>Rincian laporan</h2></div>
            <div class="card-body">
                <dl class="dl">
                    <dt>Tanggal lapor</dt>
                    <dd>{{ $pengaduan->created_at->translatedFormat('d F Y, H:i') }}</dd>
                    <dt>Kategori</dt>
                    <dd>{{ $pengaduan->kategori }}</dd>
                    <dt>Lokasi</dt>
                    <dd>{{ $pengaduan->lokasi }}</dd>
                    @if ($pengaduan->tanggal_selesai)
                        <dt>Selesai pada</dt>
                        <dd>{{ $pengaduan->tanggal_selesai->translatedFormat('d F Y, H:i') }}</dd>
                    @endif
                    <dt>Uraian</dt>
                    <dd style="white-space: pre-line;">{{ $pengaduan->deskripsi }}</dd>
                </dl>

                @if ($pengaduan->foto_url)
                    <figure class="photo-frame" style="margin-top: 18px;">
                        <a href="{{ $pengaduan->foto_url }}" target="_blank" rel="noopener">
                            <img src="{{ $pengaduan->foto_url }}" alt="Foto bukti kerusakan">
                        </a>
                        <figcaption>Foto bukti kerusakan dari pelapor</figcaption>
                    </figure>
                @endif
            </div>
        </div>

        {{-- Teknisi dan hasil perbaikan --}}
        @if ($pengaduan->penugasan->isNotEmpty())
            <div class="card">
                <div class="card-head">
                    <h2>Teknisi dan hasil perbaikan</h2>
                    <span class="sub">{{ $pengaduan->penugasan->count() }} teknisi</span>
                </div>
                <div class="card-body">
                    @foreach ($pengaduan->penugasan as $t)
                        <div class="work-item">
                            <div class="row">
                                <div class="person">
                                    <x-avatar :user="$t->teknisi" :size="34" />
                                    <div class="info">
                                        <strong>{{ $t->teknisi->name }}</strong>
                                        <span class="sub">{{ $t->teknisi->keahlian ?: 'Teknisi' }}</span>
                                    </div>
                                </div>
                                <div style="display:flex; gap:8px; align-items:center;">
                                    <x-badge :status="$t->status" />
                                    @if ($admin && $t->status === 'Ditugaskan' && ! $pengaduan->final)
                                        <form method="POST" action="{{ route('admin.penugasan.destroy', [$pengaduan, $t]) }}"
                                              data-confirm="Cabut penugasan {{ $t->teknisi->name }}?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">Cabut</button>
                                        </form>
                                    @endif
                                </div>
                            </div>

                            @if ($t->mulai_pada || $t->selesai_pada)
                                <div class="sub muted small" style="margin-top: 8px;">
                                    @if ($t->mulai_pada) Mulai {{ $t->mulai_pada->translatedFormat('d M Y, H:i') }} @endif
                                    @if ($t->selesai_pada) &middot; Selesai {{ $t->selesai_pada->translatedFormat('d M Y, H:i') }} @endif
                                </div>
                            @endif

                            @if ($t->catatan_tindakan)
                                <div class="note"><strong>Tindakan:</strong> {{ $t->catatan_tindakan }}</div>
                            @endif
                            @if ($t->foto_hasil_url)
                                <a href="{{ $t->foto_hasil_url }}" target="_blank" rel="noopener">
                                    <img src="{{ $t->foto_hasil_url }}" alt="Foto hasil perbaikan oleh {{ $t->teknisi->name }}">
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Penilaian --}}
        @if ($pengaduan->status === 'Selesai')
            <div class="card">
                <div class="card-head"><h2>Penilaian hasil perbaikan</h2></div>
                <div class="card-body">
                    @if ($pengaduan->rating)
                        <div class="stars" style="font-size: 22px;">{{ str_repeat('★', $pengaduan->rating) }}{{ str_repeat('☆', 5 - $pengaduan->rating) }}</div>
                        @if ($pengaduan->komentar_feedback)
                            <p style="margin-top: 8px; white-space: pre-line;">{{ $pengaduan->komentar_feedback }}</p>
                        @endif
                    @elseif ($pelapor)
                        <form method="POST" action="{{ route('pengaduan.feedback', $pengaduan) }}" data-loading>
                            @csrf
                            <div class="field @error('rating') has-error @enderror">
                                <span class="label">Seberapa puas kamu dengan hasilnya?</span>
                                <div class="rating">
                                    @foreach ([5, 4, 3, 2, 1] as $n)
                                        <input type="radio" name="rating" id="rate-{{ $n }}" value="{{ $n }}" @checked((int) old('rating') === $n)>
                                        <label for="rate-{{ $n }}" title="{{ $n }} dari 5">★</label>
                                    @endforeach
                                </div>
                                @error('rating') <div class="field-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="field">
                                <label for="komentar_feedback">Komentar (opsional)</label>
                                <textarea id="komentar_feedback" name="komentar_feedback" maxlength="1000" style="min-height: 80px;">{{ old('komentar_feedback') }}</textarea>
                            </div>
                            <button type="submit" class="btn">Kirim penilaian</button>
                        </form>
                    @else
                        <span class="muted">Pelapor belum memberi penilaian.</span>
                    @endif
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-head"><h2>Riwayat</h2></div>
            <div class="card-body">
                <ul class="timeline">
                    @foreach ($riwayat as $r)
                        <li>
                            <strong>{{ $r->judul }}</strong>
                            <span class="when">{{ $r->created_at->translatedFormat('d M Y, H:i') }}@if ($r->user) &middot; {{ $r->user->name }}@endif</span>
                            @if ($r->keterangan) <p>{{ $r->keterangan }}</p> @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    {{-- ================= KOLOM SAMPING ================= --}}
    <div class="stack">
        @if ($admin || $teknisi)
            <div class="card">
                <div class="card-head"><h2>Pelapor</h2></div>
                <div class="card-body">
                    <div class="person">
                        <x-avatar :user="$pengaduan->pelapor" :size="42" />
                        <div class="info">
                            <strong>{{ $pengaduan->pelapor->name }}</strong>
                            <span class="sub">{{ $pengaduan->pelapor->jenis_pengguna }}@if ($pengaduan->pelapor->kelas) &middot; {{ $pengaduan->pelapor->kelas }}@endif</span>
                        </div>
                    </div>
                    @if ($admin)
                        <dl class="dl" style="margin-top: 14px; grid-template-columns: 70px minmax(0, 1fr);">
                            <dt>{{ $pengaduan->pelapor->label_identitas }}</dt>
                            <dd class="mono">{{ $pengaduan->pelapor->nomor_identitas }}</dd>
                            <dt>Email</dt>
                            <dd style="overflow-wrap:anywhere;">{{ $pengaduan->pelapor->email }}</dd>
                            @if ($pengaduan->pelapor->no_hp)
                                <dt>No. HP</dt>
                                <dd>{{ $pengaduan->pelapor->no_hp }}</dd>
                            @endif
                        </dl>
                    @endif
                </div>
            </div>
        @endif

        {{-- ===== Admin: verifikasi ===== --}}
        @if ($admin && $pengaduan->status === 'Menunggu')
            <div class="card">
                <div class="card-head"><h2>Verifikasi laporan</h2></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.pengaduan.verifikasi', $pengaduan) }}">
                        @csrf

                        <div class="field @error('teknisi_ids') has-error @enderror @error('teknisi_ids.*') has-error @enderror">
                            <span class="label">Tugaskan teknisi <span class="req">*</span></span>
                            @if ($teknisiTersedia->isEmpty())
                                <div class="alert alert-warn">Belum ada teknisi aktif. Tambahkan dulu di menu Teknisi.</div>
                            @else
                                <div class="choice-list">
                                    @foreach ($teknisiTersedia as $t)
                                        <label class="choice">
                                            <input type="checkbox" name="teknisi_ids[]" value="{{ $t->id }}" @checked(in_array($t->id, (array) old('teknisi_ids', [])))>
                                            <span class="choice-body">
                                                <strong>{{ $t->name }}</strong>
                                                <span>{{ $t->keahlian ?: 'Umum' }}</span>
                                            </span>
                                            <span class="meta">{{ $t->tugas_aktif }} tugas</span>
                                        </label>
                                    @endforeach
                                </div>
                                <div class="help">Boleh memilih lebih dari satu teknisi.</div>
                            @endif
                            @error('teknisi_ids') <div class="field-error">{{ $message }}</div> @enderror
                            @error('teknisi_ids.*') <div class="field-error">{{ $message }}</div> @enderror
                        </div>

                        <div class="field @error('catatan_admin') has-error @enderror">
                            <label for="catatan_admin">Catatan <span class="muted" style="font-weight:400;">(wajib bila menolak)</span></label>
                            <textarea id="catatan_admin" name="catatan_admin" maxlength="2000" style="min-height: 90px;">{{ old('catatan_admin') }}</textarea>
                            @error('catatan_admin') <div class="field-error">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn" style="flex:1;">Verifikasi &amp; tugaskan</button>
                            <button type="submit" class="btn btn-danger"
                                    formaction="{{ route('admin.pengaduan.tolak', $pengaduan) }}"
                                    onclick="return confirm('Tolak laporan ini? Pelapor akan diberi tahu.');">Tolak</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        {{-- ===== Admin: tambah teknisi ===== --}}
        @if ($admin && in_array($pengaduan->status, ['Diverifikasi', 'Dikerjakan'], true))
            <div class="card">
                <div class="card-head"><h2>Tambah teknisi</h2></div>
                <div class="card-body">
                    @if ($teknisiTersedia->isEmpty())
                        <span class="muted">Semua teknisi aktif sudah ditugaskan pada laporan ini.</span>
                    @else
                        <form method="POST" action="{{ route('admin.penugasan.store', $pengaduan) }}" data-loading>
                            @csrf
                            <div class="choice-list" style="margin-bottom: 14px;">
                                @foreach ($teknisiTersedia as $t)
                                    <label class="choice">
                                        <input type="checkbox" name="teknisi_ids[]" value="{{ $t->id }}">
                                        <span class="choice-body">
                                            <strong>{{ $t->name }}</strong>
                                            <span>{{ $t->keahlian ?: 'Umum' }}</span>
                                        </span>
                                        <span class="meta">{{ $t->tugas_aktif }} tugas</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('teknisi_ids') <div class="field-error" style="margin-bottom:10px;">{{ $message }}</div> @enderror
                            <button type="submit" class="btn btn-outline btn-block">Tambahkan</button>
                        </form>
                    @endif
                </div>
            </div>
        @endif

        {{-- ===== Teknisi: tugas saya ===== --}}
        @if ($teknisi && $penugasanSaya)
            <div class="card">
                <div class="card-head">
                    <h2>Tugasmu</h2>
                    <x-badge :status="$penugasanSaya->status" />
                </div>
                <div class="card-body">
                    @if ($penugasanSaya->status === 'Ditugaskan')
                        <p class="muted" style="margin-bottom: 14px;">Tekan tombol di bawah saat kamu mulai mengerjakan perbaikan ini.</p>
                        <form method="POST" action="{{ route('tugas.mulai', $penugasanSaya) }}" data-loading>
                            @csrf
                            <button type="submit" class="btn btn-block">Mulai kerjakan</button>
                        </form>

                    @elseif ($penugasanSaya->status === 'Dikerjakan' && ! $pengaduan->final)
                        <form method="POST" action="{{ route('tugas.progres', $penugasanSaya) }}" data-loading style="margin-bottom: 20px;">
                            @csrf
                            <div class="field @error('keterangan') has-error @enderror">
                                <label for="keterangan">Catatan progres</label>
                                <textarea id="keterangan" name="keterangan" maxlength="1000" style="min-height: 70px;"
                                          placeholder="Contoh: Material sudah dipesan, pengerjaan lanjut besok.">{{ old('keterangan') }}</textarea>
                                @error('keterangan') <div class="field-error">{{ $message }}</div> @enderror
                            </div>
                            <button type="submit" class="btn btn-outline btn-sm">Simpan catatan</button>
                        </form>

                        <hr style="border:0; border-top:1px solid var(--line); margin: 0 0 18px;">

                        <form method="POST" action="{{ route('tugas.selesai', $penugasanSaya) }}" enctype="multipart/form-data" data-loading>
                            @csrf
                            <div class="field @error('catatan_tindakan') has-error @enderror">
                                <label for="catatan_tindakan">Tindakan perbaikan <span class="req">*</span></label>
                                <textarea id="catatan_tindakan" name="catatan_tindakan" maxlength="2000" required
                                          placeholder="Apa yang kamu kerjakan dan bahan yang dipakai.">{{ old('catatan_tindakan') }}</textarea>
                                @error('catatan_tindakan') <div class="field-error">{{ $message }}</div> @enderror
                            </div>
                            <div class="field">
                                <span class="label">Foto hasil perbaikan <span class="req">*</span></span>
                                <x-dropzone name="foto_hasil" label="Unggah foto hasil" :maks="5" />
                            </div>
                            <button type="submit" class="btn btn-block">Tandai selesai</button>
                        </form>

                    @else
                        <p class="muted">Tugasmu pada laporan ini sudah selesai.</p>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

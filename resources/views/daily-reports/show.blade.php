@extends('layouts.app')

@section('title', 'Detail Laporan')

@push('styles')
<style>
    .chat-bubble {
        padding: .55rem .8rem;
        border-radius: 14px;
        white-space: pre-wrap;
        line-height: 1.5;
        font-size: .9rem;
        word-break: break-word;
    }
    .chat-bubble-them {
        background: var(--bg-soft);
        color: var(--ink-900);
        border: 1px solid var(--line);
        border-top-left-radius: 4px;
    }
    .chat-bubble-me {
        background: var(--brand-600);
        color: #fff;
        border-top-right-radius: 4px;
    }
    .avatar-me {
        background: var(--brand-600);
        color: #fff;
    }
</style>
@endpush

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <h2 class="h4 fw-bold mb-1"><i class="bi bi-file-earmark-text text-primary me-2"></i>Detail Laporan Harian</h2>
        <p class="text-muted mb-0 small">Format sesuai standar Daily Closing System.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('daily-reports.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
        @if($report->user_id === auth()->id())
            <a href="{{ route('daily-reports.edit', $report) }}" class="btn btn-outline-secondary">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
            <form action="{{ route('daily-reports.destroy', $report) }}" method="POST"
                  onsubmit="return confirm('Hapus laporan tanggal {{ $report->report_date->translatedFormat('d M Y') }}?');" class="d-inline">
                @csrf @method('DELETE')
                <button class="btn btn-soft-danger"><i class="bi bi-trash me-1"></i> Hapus</button>
            </form>
        @endif
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-8">
        <div class="card">
            <div class="card-header" style="background: linear-gradient(135deg, var(--brand-50), #fff);">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-clipboard2-check text-primary"></i>
                    <span class="fw-bold">📋 DAILY CLOSING SYSTEM</span>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="text-muted small">📅 Tanggal</div>
                        <div class="fw-semibold">{{ $report->report_date->translatedFormat('d F Y') }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">⏰ Jam Selesai Kerja</div>
                        <div class="fw-semibold">{{ substr($report->work_finished_at, 0, 5) }} WIB</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">👤 Nama</div>
                        <div class="fw-semibold">{{ $report->user->name }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">🏢 Divisi / Jabatan</div>
                        <div class="fw-semibold">{{ ($report->user->division ?? '—') }} – {{ $report->user->position ?? '—' }}</div>
                    </div>
                </div>

                <hr>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="text-muted small">🕒 Status Lembur</div>
                        @if($report->overtime_status)
                            <span class="badge-soft bg-soft-warning"><i class="bi bi-stopwatch"></i> Ya</span>
                        @else
                            <span class="badge-soft" style="background:#f1f5f9;color:#64748b">Tidak</span>
                        @endif
                    </div>
                    @if($report->overtime_status)
                        <div class="col-md-6">
                            <div class="text-muted small">⏱️ Jam Lembur</div>
                            <div class="fw-semibold">{{ substr($report->overtime_start, 0, 5) }} – {{ substr($report->overtime_end, 0, 5) }} WIB</div>
                        </div>
                    @endif
                </div>

                <hr>

                <div class="mb-3">
                    <div class="text-muted small mb-1">1️⃣ Pekerjaan yang sudah selesai</div>
                    <div class="p-3" style="background: var(--bg-soft); border-radius:10px; white-space:pre-wrap; line-height:1.6;">{{ $report->completed_work }}</div>
                </div>

                <div class="mb-3">
                    <div class="text-muted small mb-1">2️⃣ Pekerjaan yang belum selesai</div>
                    <div class="p-3" style="background: var(--bg-soft); border-radius:10px; white-space:pre-wrap; line-height:1.6;">{{ $report->unfinished_work ?: '—' }}</div>
                </div>

                <div class="mb-3">
                    <div class="text-muted small mb-1">3️⃣ Hambatan yang ada</div>
                    <div class="p-3" style="background: var(--bg-soft); border-radius:10px; white-space:pre-wrap; line-height:1.6;">{{ $report->obstacles ?: '—' }}</div>
                </div>

                <div class="mb-3">
                    <div class="text-muted small mb-1">4️⃣ Butuh bantuan leader</div>
                    @if($report->need_leader_help)
                        <span class="badge-soft bg-soft-danger mb-2"><i class="bi bi-life-preserver"></i> Ya</span>
                        <div class="p-3" style="background: var(--bg-soft); border-radius:10px; white-space:pre-wrap; line-height:1.6;">{{ $report->leader_help_description }}</div>
                    @else
                        <span class="badge-soft" style="background:#f1f5f9;color:#64748b">Tidak</span>
                    @endif
                </div>

                <div class="mb-3">
                    <div class="text-muted small mb-1">5️⃣ Planning besok</div>
                    <div class="p-3" style="background: var(--bg-soft); border-radius:10px; white-space:pre-wrap; line-height:1.6;">{{ $report->tomorrow_plan }}</div>
                </div>

                <div class="mb-0">
                    <div class="text-muted small mb-1">📝 Catatan tambahan</div>
                    <div class="p-3" style="background: var(--bg-soft); border-radius:10px; white-space:pre-wrap; line-height:1.6;">{{ $report->additional_notes ?: '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-info-circle me-2 text-muted"></i>Meta Laporan</div>
            <div class="card-body">
                <div class="d-flex flex-column gap-3">
                    <div>
                        <div class="text-muted small">Dibuat oleh</div>
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <div class="avatar">{{ strtoupper(substr($report->user->name,0,1)) }}</div>
                            <div>
                                <div class="fw-semibold">{{ $report->user->name }}</div>
                                <div class="small text-muted">{{ $report->user->level_name }}</div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="text-muted small">Dibuat pada</div>
                        <div class="fw-semibold">{{ $report->created_at->translatedFormat('d M Y, H:i') }}</div>
                    </div>
                    @if($report->updated_at && $report->updated_at->ne($report->created_at))
                        <div>
                            <div class="text-muted small">Diperbarui</div>
                            <div class="fw-semibold">{{ $report->updated_at->translatedFormat('d M Y, H:i') }}</div>
                        </div>
                    @endif
                    <div>
                        <div class="text-muted small">Status</div>
                        <div class="d-flex flex-wrap gap-2 mt-1">
                            @if($report->overtime_status)
                                <span class="badge-soft bg-soft-warning"><i class="bi bi-stopwatch"></i> Lembur</span>
                            @endif
                            @if($report->need_leader_help)
                                <span class="badge-soft bg-soft-danger"><i class="bi bi-life-preserver"></i> Butuh Bantuan</span>
                            @endif
                            @if($report->is_late)
                                <span class="badge-soft bg-soft-danger"><i class="bi bi-exclamation-triangle"></i> Sanksi Terlambat</span>
                            @endif
                            @if(! $report->overtime_status && ! $report->need_leader_help && ! $report->is_late)
                                <span class="badge-soft bg-soft-success"><i class="bi bi-check-circle"></i> Normal</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Komentar / masukan atasan --}}
<div class="row g-3 mt-1" id="komentar">
    <div class="col-12 col-lg-8">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-chat-left-text me-2 text-primary"></i>Komentar &amp; Masukan</span>
                <span class="badge-soft bg-soft-primary">{{ $report->comments->count() }}</span>
            </div>
            <div class="card-body">
                @if($report->user_id === auth()->id() && $report->comments->isNotEmpty())
                    <div class="alert border-0 d-flex align-items-center mb-3" role="alert"
                         style="background:#fff5e6; color:#b15c00; border-radius:12px;">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        <div class="small">Atasan memberi masukan pada laporan ini. Mohon diperhatikan untuk perbaikan.</div>
                    </div>
                @endif

                {{-- Daftar komentar — gaya percakapan (chat) --}}
                @forelse($report->comments as $comment)
                    @php $mine = $comment->user_id === auth()->id(); @endphp
                    <div class="d-flex gap-2 mb-3 {{ $mine ? 'flex-row-reverse' : '' }}">
                        <div class="avatar {{ $mine ? 'avatar-me' : '' }}" style="width:36px;height:36px;flex:0 0 36px">
                            {{ strtoupper(substr($comment->author->name ?? '?', 0, 1)) }}
                        </div>
                        <div style="max-width:78%">
                            {{-- Nama + level --}}
                            <div class="small mb-1 {{ $mine ? 'text-end' : '' }}">
                                <span class="fw-semibold">{{ $mine ? 'Anda' : ($comment->author->name ?? 'Pengguna') }}</span>
                                @if($comment->author->level_name)
                                    <span class="text-muted ms-1">{{ $comment->author->level_name }}</span>
                                @endif
                            </div>
                            {{-- Gelembung pesan --}}
                            <div class="chat-bubble {{ $mine ? 'chat-bubble-me' : 'chat-bubble-them' }}">{{ $comment->body }}</div>
                            {{-- Waktu + aksi --}}
                            <div class="d-flex align-items-center gap-2 mt-1 {{ $mine ? 'justify-content-end' : '' }}">
                                <span class="text-muted" style="font-size:.72rem">{{ $comment->created_at->translatedFormat('d M Y, H:i') }}</span>
                                @if(auth()->user()->isSuperAdmin() || $mine)
                                    <form action="{{ route('daily-reports.comments.destroy', $comment) }}" method="POST"
                                          onsubmit="return confirm('Hapus komentar ini?');" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-link text-danger p-0" title="Hapus" style="font-size:.72rem">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted text-center mb-3 py-2">
                        <i class="bi bi-chat-left me-1"></i>Belum ada komentar.
                    </p>
                @endforelse

                {{-- Form tambah komentar --}}
                <form action="{{ route('daily-reports.comments.store', $report) }}" method="POST" class="mt-2">
                    @csrf
                    <label class="form-label">
                        @if($report->user_id === auth()->id())
                            Tambah balasan
                        @else
                            Tambah masukan untuk {{ $report->user->name }}
                        @endif
                    </label>
                    <textarea name="body" rows="3"
                              class="form-control @error('body') is-invalid @enderror"
                              placeholder="Tulis komentar atau masukan perbaikan…" required>{{ old('body') }}</textarea>
                    @error('body') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="text-end mt-2">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-send me-1"></i>Kirim Komentar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

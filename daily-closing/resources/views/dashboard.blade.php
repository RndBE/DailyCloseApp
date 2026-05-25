@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <div>
        <h2 class="h4 fw-bold mb-1">Selamat datang, {{ auth()->user()->name }} 👋</h2>
        <p class="text-muted mb-0 small">Ringkasan aktivitas laporan harian sesuai hak akses Anda.</p>
    </div>
    <div class="d-flex gap-2">
        @if($myReportToday)
            <a href="{{ route('daily-reports.show', $myReportToday) }}" class="btn btn-outline-secondary">
                <i class="bi bi-eye me-1"></i> Laporan saya hari ini
            </a>
        @else
            <a href="{{ route('daily-reports.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Buat Laporan Hari Ini
            </a>
        @endif
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="label">Laporan Hari Ini</div>
                    <div class="value">{{ $stats['today_reports'] }}</div>
                    <div class="delta">{{ now()->translatedFormat('d M Y') }}</div>
                </div>
                <div class="icon bg-soft-primary"><i class="bi bi-journal-text"></i></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="label">Minggu Ini</div>
                    <div class="value">{{ $stats['week_reports'] }}</div>
                    <div class="delta">Senin – Minggu</div>
                </div>
                <div class="icon bg-soft-info"><i class="bi bi-calendar-week"></i></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="label">Lembur Hari Ini</div>
                    <div class="value">{{ $stats['overtime_count'] }}</div>
                    <div class="delta">Status lembur = Ya</div>
                </div>
                <div class="icon bg-soft-warning"><i class="bi bi-stopwatch"></i></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="label">Butuh Bantuan Leader</div>
                    <div class="value">{{ $stats['need_help_count'] }}</div>
                    <div class="delta">Hari ini</div>
                </div>
                <div class="icon bg-soft-danger"><i class="bi bi-life-preserver"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-clock-history me-2 text-muted"></i>Laporan Terbaru</span>
                <a href="{{ route('daily-reports.index') }}" class="small text-decoration-none">Lihat semua &rarr;</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Nama</th>
                            <th>Divisi</th>
                            <th>Lembur</th>
                            <th>Butuh Bantuan</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentReports as $r)
                            <tr>
                                <td class="fw-semibold">{{ $r->report_date->translatedFormat('d M Y') }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar" style="width:28px;height:28px;font-size:.78rem">{{ strtoupper(substr($r->user->name,0,1)) }}</div>
                                        <div>
                                            <div class="fw-semibold">{{ $r->user->name }}</div>
                                            <div class="small text-muted">{{ $r->user->level_name }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-muted small">{{ $r->user->division ?? '—' }}</td>
                                <td>
                                    @if($r->overtime_status)
                                        <span class="badge-soft bg-soft-warning"><i class="bi bi-stopwatch"></i> Ya</span>
                                    @else
                                        <span class="badge-soft" style="background:#f1f5f9;color:#64748b">Tidak</span>
                                    @endif
                                </td>
                                <td>
                                    @if($r->need_leader_help)
                                        <span class="badge-soft bg-soft-danger"><i class="bi bi-life-preserver"></i> Ya</span>
                                    @else
                                        <span class="badge-soft" style="background:#f1f5f9;color:#64748b">Tidak</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('daily-reports.show', $r) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox display-6 d-block mb-2 text-muted opacity-50"></i>
                                    Belum ada laporan untuk ditampilkan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-person-check me-2 text-muted"></i>Submitter Hari Ini</div>
            <div class="card-body">
                <div class="d-flex align-items-end gap-2 mb-2">
                    <div class="display-6 fw-bold mb-0">{{ $stats['today_submitters'] }}</div>
                    <div class="text-muted small mb-2">user telah submit</div>
                </div>
                <div class="text-muted small">Berdasarkan hak akses level user Anda.</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><i class="bi bi-info-circle me-2 text-muted"></i>Status Laporan Anda</div>
            <div class="card-body">
                @if($myReportToday)
                    <span class="badge-soft bg-soft-success mb-3"><i class="bi bi-check-circle"></i> Sudah submit hari ini</span>
                    <div class="text-muted small mb-3">
                        Anda telah mengirim laporan untuk {{ $myReportToday->report_date->translatedFormat('d M Y') }}.
                    </div>
                    <a href="{{ route('daily-reports.show', $myReportToday) }}" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-eye me-1"></i> Lihat laporan saya
                    </a>
                @else
                    <span class="badge-soft bg-soft-warning mb-3"><i class="bi bi-exclamation-circle"></i> Belum submit</span>
                    <p class="text-muted small mb-3">
                        Anda belum membuat laporan harian untuk hari ini. Sempatkan waktu sebelum jam pulang kerja.
                    </p>
                    <a href="{{ route('daily-reports.create') }}" class="btn btn-primary w-100">
                        <i class="bi bi-plus-lg me-1"></i> Buat Laporan Sekarang
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

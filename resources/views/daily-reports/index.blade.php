@extends('layouts.app')

@php
    $isMine = ($scope ?? 'team') === 'mine';
    $user   = auth()->user();
    $isSuperAdmin = $user->isSuperAdmin();
    $teamMenuLabel = $isSuperAdmin ? 'Semua Laporan' : 'Laporan Tim';
@endphp

@section('title', $isMine ? 'Laporan Saya' : $teamMenuLabel)

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <h2 class="h4 fw-bold mb-1">
            @if($isMine)
                <i class="bi bi-person-lines-fill text-primary me-2"></i>Laporan Saya
            @else
                <i class="bi bi-people text-primary me-2"></i>{{ $teamMenuLabel }}
            @endif
        </h2>
        <p class="text-muted mb-0 small">
            @if($isMine)
                Daftar laporan harian yang Anda kirim sendiri.
            @elseif($isSuperAdmin)
                Daftar anggota & status laporan harian per tanggal yang dipilih.
            @else
                Daftar anggota tim Anda — level di bawah {{ $user->level_name }} — beserta status laporan per tanggal.
            @endif
        </p>
    </div>
    <div class="d-flex gap-2">
        @if($isMine)
            @if($isSuperAdmin || $user->level !== \App\Models\User::LEVEL_STAFF)
                <a href="{{ route('daily-reports.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-people me-1"></i> {{ $teamMenuLabel }}
                </a>
            @endif
        @else
            <a href="{{ route('daily-reports.mine') }}" class="btn btn-outline-secondary">
                <i class="bi bi-person-lines-fill me-1"></i> Laporan Saya
            </a>
        @endif
        <a href="{{ route('daily-reports.bulanan') }}" class="btn btn-outline-secondary">
            <i class="bi bi-calendar3 me-1"></i> Laporan Bulanan
        </a>
        @if(!$isMine && ($isSuperAdmin || in_array($user->level, [1, 2, 3])))
            <a href="{{ route('daily-reports.rangkuman', ['date' => request('date', now()->toDateString())]) }}" class="btn btn-outline-primary">
                <i class="bi bi-clipboard2-data me-1"></i> Rangkuman
            </a>
        @endif
        <a href="{{ route('daily-reports.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Buat Laporan
        </a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            @if($isMine)
                <div class="col-6 col-md-2">
                    <label class="form-label">Dari Tanggal</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">Sampai Tanggal</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                </div>
                <div class="col-12 col-md-5">
                    <label class="form-label">Status</label>
                    <div class="d-flex gap-2">
                        <select name="overtime" class="form-select form-select-sm">
                            <option value="">Lembur: Semua</option>
                            <option value="1" @selected(request('overtime')==='1')>Lembur: Ya</option>
                            <option value="0" @selected(request('overtime')==='0')>Lembur: Tidak</option>
                        </select>
                        <select name="need_help" class="form-select form-select-sm">
                            <option value="">Bantuan: Semua</option>
                            <option value="1" @selected(request('need_help')==='1')>Bantuan: Ya</option>
                            <option value="0" @selected(request('need_help')==='0')>Bantuan: Tidak</option>
                        </select>
                    </div>
                </div>
                <div class="col-12 col-md-3 d-flex gap-2 justify-content-md-end">
                    <a href="{{ route('daily-reports.mine') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg me-1"></i>Reset</a>
                    <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Terapkan</button>
                </div>
            @else
                <div class="col-12 col-md-3">
                    <label class="form-label">Tanggal</label>
                    <input type="date" name="date" value="{{ $selectedDate }}" class="form-control">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Nama</label>
                    <input type="text" name="name" value="{{ request('name') }}" class="form-control" placeholder="Cari nama…">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Divisi</label>
                    <select name="division" class="form-select">
                        <option value="">Semua</option>
                        @foreach($divisions as $d)
                            <option value="{{ $d }}" @selected(request('division')===$d)>{{ $d }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2 d-flex gap-2 justify-content-md-end">
                    <a href="{{ route('daily-reports.index') }}" class="btn btn-outline-secondary" title="Reset"><i class="bi bi-x-lg"></i></a>
                    <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Terapkan</button>
                </div>
            @endif
        </form>
    </div>
</div>

@unless($isMine)
    @php
        $selectedDateLabel = \Carbon\Carbon::parse($selectedDate)->translatedFormat('d M Y');
    @endphp
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3 small">
        <span class="text-muted">Tanggal:</span>
        <span class="badge-soft bg-soft-primary"><i class="bi bi-calendar-event me-1"></i>{{ $selectedDateLabel }}</span>
        <span class="badge-soft bg-soft-info"><i class="bi bi-people me-1"></i>Total anggota: {{ $summary['total'] }}</span>
        <span class="badge-soft bg-soft-success"><i class="bi bi-check-circle me-1"></i>Sudah kirim: {{ $summary['submitted'] }}</span>
        <span class="badge-soft bg-soft-warning"><i class="bi bi-exclamation-circle me-1"></i>Belum kirim: {{ $summary['missing'] }}</span>
        @if(($summary['leave'] ?? 0) > 0)
            <span class="badge-soft bg-soft-info"><i class="bi bi-calendar-heart me-1"></i>Cuti/Sakit: {{ $summary['leave'] }}</span>
        @endif
    </div>
@endunless

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    @unless($isMine)
                        <th>Nama</th>
                        <th>Divisi / Jabatan</th>
                        <th>Status</th>
                    @endunless
                    <th>Lembur</th>
                    <th>Butuh Bantuan</th>
                    <th>Jam Selesai</th>
                    <th>Sanksi</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @if($isMine)
                    @forelse($reports as $r)
                        <tr>
                            <td class="fw-semibold">
                                {{ $r->report_date->translatedFormat('d M Y') }}
                                @if(($r->comments_count ?? 0) > 0)
                                    <a href="{{ route('daily-reports.show', $r) }}#komentar" class="badge-soft bg-soft-primary text-decoration-none ms-1" title="Ada masukan atasan">
                                        <i class="bi bi-chat-left-text"></i> {{ $r->comments_count }}
                                    </a>
                                @endif
                            </td>
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
                            <td class="small text-muted">{{ substr($r->work_finished_at, 0, 5) }}</td>
                            <td>
                                @if($r->is_late)
                                    <span class="badge-soft bg-soft-danger"><i class="bi bi-exclamation-triangle"></i> Sanksi</span>
                                @else
                                    <span class="badge-soft bg-soft-success"><i class="bi bi-check-circle"></i> Tepat Waktu</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('daily-reports.show', $r) }}" class="btn btn-sm btn-outline-secondary" title="Lihat detail">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if($r->user_id === auth()->id())
                                    <a href="{{ route('daily-reports.edit', $r) }}" class="btn btn-sm btn-outline-secondary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('daily-reports.destroy', $r) }}" method="POST"
                                          onsubmit="return confirm('Hapus laporan tanggal {{ $r->report_date->translatedFormat('d M Y') }}?');" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-soft-danger" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="bi bi-inbox display-5 d-block mb-2 text-muted opacity-50"></i>
                                Anda belum memiliki laporan. <a href="{{ route('daily-reports.create') }}">Buat laporan pertama Anda &rarr;</a>
                            </td>
                        </tr>
                    @endforelse
                @else
                    @forelse($rows as $row)
                        @php
                            $report = $row->report;
                            $member = $row->user;
                            $leave  = $row->leave ?? null;
                            $rowDate = $report?->report_date ?? \Carbon\Carbon::parse($selectedDate);
                        @endphp
                        <tr @class(['table-warning' => ! $report && ! $leave])>
                            <td class="fw-semibold">{{ $rowDate->translatedFormat('d M Y') }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar" style="width:30px;height:30px;font-size:.78rem">{{ strtoupper(substr($member->name,0,1)) }}</div>
                                    <div>
                                        <div class="fw-semibold">{{ $member->name }}</div>
                                        <div class="small text-muted">{{ $member->level_name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="small">
                                <div>{{ $member->division ?? '—' }}</div>
                                <div class="text-muted">{{ $member->position ?? '—' }}</div>
                            </td>
                            <td>
                                @if($report)
                                    <span class="badge-soft bg-soft-success"><i class="bi bi-check-circle"></i> Sudah Kirim</span>
                                    @if(($report->comments_count ?? 0) > 0)
                                        <a href="{{ route('daily-reports.show', $report) }}#komentar" class="badge-soft bg-soft-primary text-decoration-none ms-1" title="Sudah ada komentar">
                                            <i class="bi bi-chat-left-text"></i> {{ $report->comments_count }}
                                        </a>
                                    @endif
                                @elseif($leave)
                                    @if($leave->type === \App\Models\Leave::TYPE_SAKIT)
                                        <span class="badge-soft bg-soft-danger"><i class="bi bi-thermometer-half"></i> {{ $leave->type_label }}</span>
                                    @else
                                        <span class="badge-soft bg-soft-info"><i class="bi bi-calendar-heart"></i> {{ $leave->type_label }}</span>
                                    @endif
                                @else
                                    <span class="badge-soft bg-soft-warning"><i class="bi bi-exclamation-circle"></i> Belum Kirim</span>
                                @endif
                            </td>
                            <td>
                                @if($report && $report->overtime_status)
                                    <span class="badge-soft bg-soft-warning"><i class="bi bi-stopwatch"></i> Ya</span>
                                @elseif($report)
                                    <span class="badge-soft" style="background:#f1f5f9;color:#64748b">Tidak</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($report && $report->need_leader_help)
                                    <span class="badge-soft bg-soft-danger"><i class="bi bi-life-preserver"></i> Ya</span>
                                @elseif($report)
                                    <span class="badge-soft" style="background:#f1f5f9;color:#64748b">Tidak</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="small text-muted">
                                {{ $report ? substr($report->work_finished_at, 0, 5) : '—' }}
                            </td>
                            <td>
                                @if($report && $report->is_late)
                                    <span class="badge-soft bg-soft-danger"><i class="bi bi-exclamation-triangle"></i> Sanksi</span>
                                @elseif($report)
                                    <span class="badge-soft bg-soft-success"><i class="bi bi-check-circle"></i> Tepat Waktu</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($report)
                                    <a href="{{ route('daily-reports.show', $report) }}" class="btn btn-sm btn-outline-secondary" title="Lihat detail">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="bi bi-inbox display-5 d-block mb-2 text-muted opacity-50"></i>
                                Tidak ada anggota tim yang sesuai filter.
                            </td>
                        </tr>
                    @endforelse
                @endif
            </tbody>
        </table>
    </div>
    @php $paginator = $isMine ? $reports : $rows; @endphp
    @if($paginator->hasPages())
        <div class="p-3 border-top">
            {{ $paginator->links() }}
        </div>
    @endif
</div>
@endsection

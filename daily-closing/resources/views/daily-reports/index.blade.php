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
                Daftar laporan dari seluruh user (kecuali laporan Anda sendiri).
            @else
                Daftar laporan dari tim Anda — level di bawah {{ $user->level_name }}.
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
        <a href="{{ route('daily-reports.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Buat Laporan
        </a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-2">
                <label class="form-label">Dari Tanggal</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label">Sampai Tanggal</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
            </div>
            @unless($isMine)
                <div class="col-md-3">
                    <label class="form-label">Nama</label>
                    <input type="text" name="name" value="{{ request('name') }}" class="form-control" placeholder="Cari nama…">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Divisi</label>
                    <select name="division" class="form-select">
                        <option value="">Semua</option>
                        @foreach($divisions as $d)
                            <option value="{{ $d }}" @selected(request('division')===$d)>{{ $d }}</option>
                        @endforeach
                    </select>
                </div>
            @endunless
            <div class="col-md-{{ $isMine ? 5 : 3 }} d-flex gap-2 align-items-end">
                <div class="flex-fill">
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
            </div>
            <div class="col-12 d-flex gap-2 justify-content-end">
                <a href="{{ $isMine ? route('daily-reports.mine') : route('daily-reports.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg me-1"></i>Reset</a>
                <button class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i>Terapkan Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    @unless($isMine)<th>Nama</th>@endunless
                    @unless($isMine)<th>Divisi / Jabatan</th>@endunless
                    <th>Lembur</th>
                    <th>Butuh Bantuan</th>
                    <th>Jam Selesai</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reports as $r)
                    <tr>
                        <td class="fw-semibold">{{ $r->report_date->translatedFormat('d M Y') }}</td>
                        @unless($isMine)
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar" style="width:30px;height:30px;font-size:.78rem">{{ strtoupper(substr($r->user->name,0,1)) }}</div>
                                    <div>
                                        <div class="fw-semibold">{{ $r->user->name }}</div>
                                        <div class="small text-muted">{{ $r->user->level_name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="small">
                                <div>{{ $r->user->division ?? '—' }}</div>
                                <div class="text-muted">{{ $r->user->position ?? '—' }}</div>
                            </td>
                        @endunless
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
                        <td class="text-end">
                            <a href="{{ route('daily-reports.show', $r) }}" class="btn btn-sm btn-outline-secondary" title="Lihat detail">
                                <i class="bi bi-eye"></i>
                            </a>
                            @if($r->user_id === auth()->id())
                                <a href="{{ route('daily-reports.edit', $r) }}" class="btn btn-sm btn-outline-secondary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $isMine ? 5 : 7 }}" class="text-center text-muted py-5">
                            <i class="bi bi-inbox display-5 d-block mb-2 text-muted opacity-50"></i>
                            @if($isMine)
                                Anda belum memiliki laporan. <a href="{{ route('daily-reports.create') }}">Buat laporan pertama Anda &rarr;</a>
                            @else
                                Tidak ada laporan yang sesuai filter.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($reports->hasPages())
        <div class="p-3 border-top">
            {{ $reports->links() }}
        </div>
    @endif
</div>
@endsection

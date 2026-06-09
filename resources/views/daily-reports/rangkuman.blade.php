@extends('layouts.app')

@php
$splitText = function(string $text): array {
    $lines = preg_split('/\r\n|\n|\r/', $text);
    $result = [];
    foreach ($lines as $line) {
        foreach (preg_split('/\s*(?:^|\s)-\s+/', $line) as $part) {
            $part = trim($part, " \t-•·");
            if ($part !== '') $result[] = $part;
        }
    }
    return $result ?: [trim($text)];
};
@endphp

@section('title', 'Rangkuman Laporan Tim')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <h2 class="h4 fw-bold mb-1">
            <i class="bi bi-clipboard2-data text-primary me-2"></i>Rangkuman Laporan Tim
        </h2>
        <p class="text-muted small mb-0">
            {{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('l, d F Y') }}
        </p>
    </div>
    <div class="d-flex gap-2 no-print">
        <a href="{{ route('daily-reports.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
        <a href="{{ route('daily-reports.rangkuman.cetak', ['date' => $selectedDate]) }}"
           target="_blank" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-printer me-1"></i>Cetak
        </a>
    </div>
</div>

{{-- Filter Tanggal --}}
<div class="card mb-3 no-print">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('daily-reports.rangkuman') }}" class="d-flex align-items-center gap-2">
            <label class="form-label mb-0 text-muted small fw-semibold">Tanggal</label>
            <input type="date" name="date" value="{{ $selectedDate }}"
                   class="form-control form-control-sm" style="width:170px"
                   onchange="this.form.submit()">
        </form>
    </div>
</div>

{{-- Laporan Manager --}}
@if($managerRows->count() > 0)
<div class="mb-4">
    <h5 class="fw-bold mb-3">
        <i class="bi bi-person-badge text-primary me-2"></i>Laporan Manager
    </h5>
    @foreach($managerRows as $mRow)
        @php
            $mr = $mRow->report;
        @endphp
        <div class="card mb-3">
            <div class="card-header py-2 px-4 d-flex align-items-center justify-content-between"
                 style="background:#eef2ff; border-bottom:2px solid #a5b4fc">
                <span class="fw-bold">
                    <i class="bi bi-person-circle me-2 text-primary"></i>{{ $mRow->user->name }}
                </span>
                <span class="text-muted small">{{ $mRow->user->division ?? 'Tanpa Divisi' }}</span>
            </div>
            <div class="card-body px-4 py-3">
                @if(!$mr && ($mRow->leave ?? null))
                    <div class="p-3 rounded" style="background:#e5f4fb; border-left:3px solid #0c6f97">
                        <span style="color:#0c6f97">
                            <i class="bi bi-calendar-heart me-1"></i>{{ $mRow->leave->type_label }}
                            @if($mRow->leave->reason) — {{ $mRow->leave->reason }} @endif
                        </span>
                    </div>
                @elseif(!$mr)
                    <div class="p-3 rounded" style="background:#fff5e6; border-left:3px solid #f59e0b">
                        <span style="color:#b15c00">
                            <i class="bi bi-exclamation-circle me-1"></i>Belum mengirim laporan
                        </span>
                    </div>
                @else
                    @php $n = 0; @endphp

                    <div class="mb-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
                                  style="width:24px;height:24px;min-width:24px;background:#6366f1;font-size:.8rem">{{ ++$n }}</span>
                            <span class="fw-semibold">Pekerjaan yang Diselesaikan</span>
                        </div>
                        @if(trim($mr->completed_work))
                            <ul class="mb-0" style="padding-left:2rem; line-height:1.9">
                                @foreach($splitText(trim($mr->completed_work)) as $line)
                                    <li>{{ $line }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-muted mb-0 ms-4"><em>Tidak ada data.</em></p>
                        @endif
                    </div>

                    <div class="mb-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
                                  style="width:24px;height:24px;min-width:24px;background:#6366f1;font-size:.8rem">{{ ++$n }}</span>
                            <span class="fw-semibold">Pekerjaan yang Belum Selesai</span>
                        </div>
                        @if(trim($mr->unfinished_work ?? ''))
                            <ul class="mb-0" style="padding-left:2rem; line-height:1.9">
                                @foreach($splitText(trim($mr->unfinished_work)) as $line)
                                    <li>{{ $line }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-muted mb-0 ms-4"><em>Tidak ada pekerjaan yang tertunda.</em></p>
                        @endif
                    </div>

                    <div class="mb-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
                                  style="width:24px;height:24px;min-width:24px;background:#6366f1;font-size:.8rem">{{ ++$n }}</span>
                            <span class="fw-semibold">Hambatan</span>
                        </div>
                        @if(trim($mr->obstacles ?? ''))
                            <ul class="mb-0" style="padding-left:2rem; line-height:1.9">
                                @foreach($splitText(trim($mr->obstacles)) as $line)
                                    <li>{{ $line }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-muted mb-0 ms-4"><em>Tidak ada hambatan yang dilaporkan.</em></p>
                        @endif
                    </div>

                    <div class="mb-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
                                  style="width:24px;height:24px;min-width:24px;background:#6366f1;font-size:.8rem">{{ ++$n }}</span>
                            <span class="fw-semibold">Rencana Kerja Hari Berikutnya</span>
                        </div>
                        @if(trim($mr->tomorrow_plan))
                            <ul class="mb-0" style="padding-left:2rem; line-height:1.9">
                                @foreach($splitText(trim($mr->tomorrow_plan)) as $line)
                                    <li>{{ $line }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-muted mb-0 ms-4"><em>Tidak ada data.</em></p>
                        @endif
                    </div>

                    @if(trim($mr->additional_notes ?? ''))
                        <div class="mb-3">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
                                      style="width:24px;height:24px;min-width:24px;background:#6366f1;font-size:.8rem">{{ ++$n }}</span>
                                <span class="fw-semibold">Catatan Tambahan</span>
                            </div>
                            <ul class="mb-0" style="padding-left:2rem; line-height:1.9">
                                @foreach($splitText(trim($mr->additional_notes)) as $line)
                                    <li>{{ $line }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <div class="p-3 rounded h-100" style="background:#fff5e6; border-left:3px solid #f59e0b">
                                <div class="fw-semibold mb-1 small" style="color:#b15c00">
                                    <i class="bi bi-stopwatch me-1"></i>Lembur
                                </div>
                                @if($mr->overtime_status)
                                    <span class="small">
                                        {{ $mr->overtime_start ? substr($mr->overtime_start,0,5) : '-' }}
                                        s/d
                                        {{ $mr->overtime_end ? substr($mr->overtime_end,0,5) : '-' }}
                                    </span>
                                @else
                                    <span class="small text-muted"><em>Tidak lembur.</em></span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded h-100" style="background:#fef2f2; border-left:3px solid #ef4444">
                                <div class="fw-semibold mb-1 small" style="color:#b02a37">
                                    <i class="bi bi-exclamation-triangle me-1"></i>Keterlambatan Kirim
                                </div>
                                @if($mr->is_late)
                                    <span class="small" style="color:#b02a37">
                                        Kirim pukul {{ $mr->created_at->translatedFormat('H:i') }} WIB
                                    </span>
                                @else
                                    <span class="small text-muted"><em>Tepat waktu.</em></span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endforeach
</div>
<hr class="my-4">
@endif

{{-- Rangkuman Per Divisi --}}
@forelse($byDivision as $division => $rows)
    @php
        $scheduledOff = $rows->filter(fn($r) => !$r->report && ($r->securityOff ?? false));
        $reportableRows = $rows->reject(fn($r) => $r->securityOff ?? false);
        $submitted  = $reportableRows->filter(fn($r) => $r->report);
        $onLeave    = $reportableRows->filter(fn($r) => !$r->report && ($r->leave ?? null));
        $missing    = $reportableRows->filter(fn($r) => !$r->report && !($r->leave ?? null));
        $overtime   = $submitted->filter(fn($r) => $r->report->overtime_status);
        $needHelp   = $submitted->filter(fn($r) => $r->report->need_leader_help);
        $lateRows   = $submitted->filter(fn($r) => $r->report->is_late);

        $completedWork  = $submitted->map(fn($r) => trim($r->report->completed_work))->filter()->values();
        $unfinished     = $submitted->map(fn($r) => trim($r->report->unfinished_work ?? ''))->filter()->values();
        $obstacles      = $submitted->map(fn($r) => trim($r->report->obstacles ?? ''))->filter()->values();
        $helpDesc       = $needHelp->map(fn($r) => trim($r->report->leader_help_description ?? ''))->filter()->values();
        $plans          = $submitted->map(fn($r) => trim($r->report->tomorrow_plan))->filter()->values();
        $notes          = $submitted->map(fn($r) => trim($r->report->additional_notes ?? ''))->filter()->values();
        $isSecurityDivision = $division === \App\Models\User::DIVISION_SECURITY;
    @endphp

    <div class="card mb-4">
        {{-- Header Divisi --}}
        <div class="card-header py-2 px-4 d-flex align-items-center justify-content-between"
             style="background:#f0f4ff; border-bottom:2px solid #c7d7fa">
            <span class="fw-bold" style="font-size:1rem">
                <i class="bi bi-building me-2 text-primary"></i>Divisi {{ $division }}
            </span>
            <span class="text-muted small">
                {{ $submitted->count() }}/{{ $reportableRows->count() }} anggota mengirim laporan
            </span>
        </div>

        <div class="card-body px-4 py-3">

            {{-- Belum kirim --}}
            @if($missing->count() > 0)
                <div class="mb-4 p-3 rounded" style="background:#fff5e6; border-left:3px solid #f59e0b">
                    <span class="fw-semibold" style="color:#b15c00">
                        <i class="bi bi-exclamation-circle me-1"></i>Belum mengirim laporan:
                    </span>
                    <span class="ms-1" style="color:#b15c00">
                        {{ $missing->map(fn($r) => $r->user->name)->join(', ') }}
                    </span>
                </div>
            @endif

            {{-- Libur shift security --}}
            @if($scheduledOff->count() > 0)
                <div class="mb-4 p-3 rounded" style="background:#eef6ff; border-left:3px solid #3b82f6">
                    <span class="fw-semibold" style="color:#1d4ed8">
                        <i class="bi bi-calendar-check me-1"></i>Libur Shift:
                    </span>
                    <span class="ms-1" style="color:#1d4ed8">
                        {{ $scheduledOff->map(fn($r) => $r->user->name)->join(', ') }}
                    </span>
                </div>
            @endif

            {{-- Cuti / sakit --}}
            @if($onLeave->count() > 0)
                <div class="mb-4 p-3 rounded" style="background:#e5f4fb; border-left:3px solid #0c6f97">
                    <span class="fw-semibold" style="color:#0c6f97">
                        <i class="bi bi-calendar-heart me-1"></i>Cuti / Sakit:
                    </span>
                    <span class="ms-1" style="color:#0c6f97">
                        {{ $onLeave->map(fn($r) => $r->user->name . ' (' . $r->leave->type_label . ')')->join(', ') }}
                    </span>
                </div>
            @endif

            @if($submitted->count() === 0)
                <p class="text-muted text-center py-3 mb-0">
                    <i class="bi bi-inbox me-1"></i>Tidak ada laporan yang masuk dari divisi ini.
                </p>
            @elseif($isSecurityDivision)
                @foreach($submitted as $row)
                    @php
                        $report = $row->report;
                        $schedule = $row->securitySchedule ?? null;
                        $shiftLabel = ($schedule && !$schedule->is_off && $schedule->start_time && $schedule->end_time)
                            ? 'Shift ' . substr($schedule->start_time, 0, 5) . ' - ' . substr($schedule->end_time, 0, 5)
                            : 'Shift belum diatur';
                        $securitySections = [
                            'Pekerjaan yang Diselesaikan' => trim($report->completed_work),
                            'Pekerjaan yang Belum Selesai' => trim($report->unfinished_work ?? ''),
                            'Hambatan' => trim($report->obstacles ?? ''),
                            'Rencana Kerja Hari Berikutnya' => trim($report->tomorrow_plan),
                            'Catatan Tambahan' => trim($report->additional_notes ?? ''),
                        ];
                    @endphp

                    <div class="py-3 {{ $loop->first ? 'pt-0' : '' }} {{ ! $loop->last ? 'border-bottom' : '' }}">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                            <div>
                                <div class="fw-bold">
                                    <i class="bi bi-person-circle me-1 text-primary"></i>{{ $row->user->name }}
                                </div>
                                <div class="text-muted small">
                                    {{ $row->user->position ?? 'Security' }} &bull; {{ $shiftLabel }}
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                @if($report->overtime_status)
                                    <span class="badge-soft bg-soft-warning">
                                        <i class="bi bi-stopwatch"></i>Lembur
                                    </span>
                                @endif
                                @if($report->need_leader_help)
                                    <span class="badge-soft bg-soft-danger">
                                        <i class="bi bi-exclamation-circle"></i>Butuh Bantuan
                                    </span>
                                @endif
                                @if($report->is_late)
                                    <span class="badge-soft bg-soft-danger">
                                        <i class="bi bi-clock-history"></i>Terlambat
                                    </span>
                                @endif
                            </div>
                        </div>

                        @foreach($securitySections as $sectionTitle => $sectionText)
                            @continue($sectionTitle === 'Catatan Tambahan' && $sectionText === '')
                            <div class="mb-3">
                                <div class="fw-semibold small text-uppercase text-muted mb-1">{{ $sectionTitle }}</div>
                                @if($sectionText !== '')
                                    <ul class="mb-0" style="padding-left:1.25rem; line-height:1.8">
                                        @foreach($splitText($sectionText) as $line)
                                            <li>{{ $line }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-muted mb-0"><em>Tidak ada data.</em></p>
                                @endif
                            </div>
                        @endforeach

                        <div class="mb-0">
                            <div class="fw-semibold small text-uppercase text-muted mb-1">Kebutuhan Bantuan Pimpinan</div>
                            @if($report->need_leader_help)
                                @php $helpText = trim($report->leader_help_description ?? ''); @endphp
                                @if($helpText !== '')
                                    <ul class="mb-0" style="padding-left:1.25rem; line-height:1.8">
                                        @foreach($splitText($helpText) as $line)
                                            <li>{{ $line }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="mb-0" style="color:#b02a37"><em>Butuh bantuan pimpinan.</em></p>
                                @endif
                            @else
                                <p class="text-muted mb-0"><em>Tidak ada permintaan bantuan.</em></p>
                            @endif
                        </div>
                    </div>
                @endforeach
            @else
                @php $n = 0; @endphp

                {{-- 1. Pekerjaan yang diselesaikan --}}
                <div class="mb-4">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
                              style="width:24px;height:24px;min-width:24px;background:#3b82f6;font-size:.8rem">{{ ++$n }}</span>
                        <span class="fw-semibold">Pekerjaan yang Diselesaikan</span>
                    </div>
                    @if($completedWork->count() > 0)
                        <ul class="mb-0" style="padding-left:2rem; line-height:1.9">
                            @foreach($completedWork as $item)
                                @foreach($splitText($item) as $line)
                                    <li>{{ $line }}</li>
                                @endforeach
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted mb-0 ms-4"><em>Tidak ada data.</em></p>
                    @endif
                </div>

                {{-- 2. Yang belum selesai --}}
                <div class="mb-4">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
                              style="width:24px;height:24px;min-width:24px;background:#3b82f6;font-size:.8rem">{{ ++$n }}</span>
                        <span class="fw-semibold">Pekerjaan yang Belum Selesai</span>
                    </div>
                    @if($unfinished->count() > 0)
                        <ul class="mb-0" style="padding-left:2rem; line-height:1.9">
                            @foreach($unfinished as $item)
                                @foreach($splitText($item) as $line)
                                    <li>{{ $line }}</li>
                                @endforeach
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted mb-0 ms-4"><em>Tidak ada pekerjaan yang tertunda.</em></p>
                    @endif
                </div>

                {{-- 3. Hambatan --}}
                <div class="mb-4">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
                              style="width:24px;height:24px;min-width:24px;background:#3b82f6;font-size:.8rem">{{ ++$n }}</span>
                        <span class="fw-semibold">Hambatan</span>
                    </div>
                    @if($obstacles->count() > 0)
                        <ul class="mb-0" style="padding-left:2rem; line-height:1.9">
                            @foreach($obstacles as $item)
                                @foreach($splitText($item) as $line)
                                    <li>{{ $line }}</li>
                                @endforeach
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted mb-0 ms-4"><em>Tidak ada hambatan yang dilaporkan.</em></p>
                    @endif
                </div>

                {{-- 4. Kebutuhan bantuan --}}
                <div class="mb-4">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
                              style="width:24px;height:24px;min-width:24px;background:#3b82f6;font-size:.8rem">{{ ++$n }}</span>
                        <span class="fw-semibold">Kebutuhan Bantuan Pimpinan</span>
                    </div>
                    @if($needHelp->count() > 0)
                        <div class="ms-4">
                            @if($helpDesc->count() > 0)
                                <ul class="mb-1" style="padding-left:1rem; line-height:1.9">
                                    @foreach($helpDesc as $item)
                                        @foreach($splitText($item) as $line)
                                            <li>{{ $line }}</li>
                                        @endforeach
                                    @endforeach
                                </ul>
                            @endif
                            <p class="mb-0 small" style="color:#b02a37">
                                <i class="bi bi-person-fill me-1"></i>
                                Diajukan oleh: <strong>{{ $needHelp->map(fn($r) => $r->user->name)->join(', ') }}</strong>
                            </p>
                        </div>
                    @else
                        <p class="text-muted mb-0 ms-4"><em>Tidak ada permintaan bantuan.</em></p>
                    @endif
                </div>

                {{-- 5. Planning --}}
                <div class="mb-4">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
                              style="width:24px;height:24px;min-width:24px;background:#3b82f6;font-size:.8rem">{{ ++$n }}</span>
                        <span class="fw-semibold">Rencana Kerja Hari Berikutnya</span>
                    </div>
                    @if($plans->count() > 0)
                        <ul class="mb-0" style="padding-left:2rem; line-height:1.9">
                            @foreach($plans as $item)
                                @foreach($splitText($item) as $line)
                                    <li>{{ $line }}</li>
                                @endforeach
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted mb-0 ms-4"><em>Tidak ada data.</em></p>
                    @endif
                </div>

                {{-- 6. Catatan tambahan --}}
                @if($notes->count() > 0)
                    <div class="mb-4">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
                                  style="width:24px;height:24px;min-width:24px;background:#3b82f6;font-size:.8rem">{{ ++$n }}</span>
                            <span class="fw-semibold">Catatan Tambahan</span>
                        </div>
                        <ul class="mb-0" style="padding-left:2rem; line-height:1.9">
                            @foreach($notes as $item)
                                @foreach($splitText($item) as $line)
                                    <li>{{ $line }}</li>
                                @endforeach
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Divider --}}
                <hr class="my-3" style="border-color:#e2e8f0">

                {{-- 7. Lembur --}}
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="p-3 rounded h-100" style="background:#fff5e6; border-left:3px solid #f59e0b">
                            <div class="fw-semibold mb-1" style="color:#b15c00">
                                <i class="bi bi-stopwatch me-1"></i>
                                {{ ++$n }}. Yang Lembur
                            </div>
                            @if($overtime->count() > 0)
                                <ul class="mb-0 small" style="padding-left:1.2rem; line-height:1.9">
                                    @foreach($overtime as $row)
                                        <li>
                                            <strong>{{ $row->user->name }}</strong>
                                            @if($row->report->overtime_start && $row->report->overtime_end)
                                                — {{ substr($row->report->overtime_start,0,5) }} s/d {{ substr($row->report->overtime_end,0,5) }}
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="mb-0 small text-muted"><em>Tidak ada lembur.</em></p>
                            @endif
                        </div>
                    </div>

                    {{-- 8. Sanksi --}}
                    <div class="col-md-6">
                        <div class="p-3 rounded h-100" style="background:#fef2f2; border-left:3px solid #ef4444">
                            <div class="fw-semibold mb-1" style="color:#b02a37">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                {{ ++$n }}. Sanksi Terlambat Kirim
                            </div>
                            @if($lateRows->count() > 0)
                                <ul class="mb-0 small" style="padding-left:1.2rem; line-height:1.9">
                                    @foreach($lateRows as $row)
                                        <li>
                                            <strong>{{ $row->user->name }}</strong>
                                            — kirim {{ $row->report->created_at->translatedFormat('H:i') }} WIB
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="mb-0 small text-muted"><em>Tidak ada sanksi.</em></p>
                            @endif
                        </div>
                    </div>
                </div>

            @endif
        </div>
    </div>
@empty
    <div class="card">
        <div class="card-body text-center text-muted py-5">
            <i class="bi bi-inbox display-5 d-block mb-2 opacity-50"></i>
            Tidak ada anggota tim yang ditemukan.
        </div>
    </div>
@endforelse

@endsection

@push('styles')
<style>
@media print {
    .no-print, .sidebar, .navbar { display: none !important; }
    .card { break-inside: avoid; box-shadow: none !important; border: 1px solid #ddd !important; margin-bottom: 1rem !important; }
    .row.g-3 .col-md-6 { width: 50% !important; float: left; }
    body { font-size: 12px; }
}
</style>
@endpush

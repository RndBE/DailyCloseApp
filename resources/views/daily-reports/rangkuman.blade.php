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

{{-- Rangkuman Per Divisi --}}
@forelse($byDivision as $division => $rows)
    @php
        $submitted  = $rows->filter(fn($r) => $r->report);
        $missing    = $rows->filter(fn($r) => !$r->report);
        $overtime   = $submitted->filter(fn($r) => $r->report->overtime_status);
        $needHelp   = $submitted->filter(fn($r) => $r->report->need_leader_help);
        $lateRows   = $submitted->filter(fn($r) => $r->report->is_late);

        $completedWork  = $submitted->map(fn($r) => trim($r->report->completed_work))->filter()->values();
        $unfinished     = $submitted->map(fn($r) => trim($r->report->unfinished_work ?? ''))->filter()->values();
        $obstacles      = $submitted->map(fn($r) => trim($r->report->obstacles ?? ''))->filter()->values();
        $helpDesc       = $needHelp->map(fn($r) => trim($r->report->leader_help_description ?? ''))->filter()->values();
        $plans          = $submitted->map(fn($r) => trim($r->report->tomorrow_plan))->filter()->values();
        $notes          = $submitted->map(fn($r) => trim($r->report->additional_notes ?? ''))->filter()->values();
    @endphp

    <div class="card mb-4">
        {{-- Header Divisi --}}
        <div class="card-header py-2 px-4 d-flex align-items-center justify-content-between"
             style="background:#f0f4ff; border-bottom:2px solid #c7d7fa">
            <span class="fw-bold" style="font-size:1rem">
                <i class="bi bi-building me-2 text-primary"></i>Divisi {{ $division }}
            </span>
            <span class="text-muted small">
                {{ $submitted->count() }}/{{ $rows->count() }} anggota mengirim laporan
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

            @if($submitted->count() === 0)
                <p class="text-muted text-center py-3 mb-0">
                    <i class="bi bi-inbox me-1"></i>Tidak ada laporan yang masuk dari divisi ini.
                </p>
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

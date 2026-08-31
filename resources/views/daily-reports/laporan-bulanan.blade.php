@extends('layouts.app')

@section('title', 'Laporan Bulanan — ' . $monthLabel)

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <h2 class="h4 fw-bold mb-1">
            <i class="bi bi-calendar3 text-primary me-2"></i>Laporan Bulanan
        </h2>
        <p class="text-muted small mb-0">Rekap laporan harian — {{ $monthLabel }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('daily-reports.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>
</div>

{{-- Filter bulan --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('daily-reports.bulanan') }}" class="d-flex align-items-center gap-2 flex-wrap">
            <label class="form-label mb-0 text-muted small fw-semibold">Bulan</label>
            <select name="month" class="form-select form-select-sm" style="width:130px">
                @foreach(range(1,12) as $m)
                    <option value="{{ $m }}" @selected($m == $month)>
                        {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                    </option>
                @endforeach
            </select>
            <select name="year" class="form-select form-select-sm" style="width:90px">
                @foreach(range(now()->year - 2, now()->year) as $y)
                    <option value="{{ $y }}" @selected($y == $year)>{{ $y }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-search me-1"></i>Tampilkan
            </button>
        </form>
    </div>
</div>

{{-- Statistik global --}}
<div class="row g-3 mb-4">
    @foreach([
        ['val' => $totalStats['members'],   'label' => 'Total Anggota',   'bg' => '#f1f5f9', 'color' => '#1e293b'],
        ['val' => $totalStats['submitted'],  'label' => 'Total Laporan',   'bg' => '#e7f7ee', 'color' => '#157347'],
        ['val' => $totalStats['overtime'],   'label' => 'Total Lembur',    'bg' => '#fff5e6', 'color' => '#b15c00'],
        ['val' => $totalStats['leave'],      'label' => 'Cuti / Sakit / Izin', 'bg' => '#e5f4fb', 'color' => '#0c6f97'],
        ['val' => $totalStats['need_help'],  'label' => 'Butuh Bantuan',   'bg' => '#fef2f2', 'color' => '#b02a37'],
        ['val' => $totalStats['late'],       'label' => 'Sanksi',          'bg' => '#fef2f2', 'color' => '#b02a37'],
    ] as $s)
        <div class="col-6 col-md">
            <div class="card text-center border-0 h-100" style="background:{{ $s['bg'] }}">
                <div class="card-body py-3">
                    <div class="h3 fw-bold mb-0" style="color:{{ $s['color'] }}">{{ $s['val'] }}</div>
                    <div class="small text-muted">{{ $s['label'] }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- Per Divisi --}}
@forelse($byDivision as $division => $rows)
    <div class="card mb-4">
        <div class="card-header py-2 px-3 d-flex align-items-center justify-content-between"
             style="background:#f0f4ff; border-bottom:2px solid #c7d7fa">
            <span class="fw-bold"><i class="bi bi-building me-2 text-primary"></i>{{ $division }}</span>
            <span class="text-muted small">{{ $rows->count() }} anggota</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:.9rem">
                <thead class="table-light">
                    <tr>
                        <th>Nama</th>
                        <th>Level</th>
                        <th class="text-center">Laporan Terkirim</th>
                        <th class="text-center">Lembur</th>
                        <th class="text-center">Butuh Bantuan</th>
                        <th class="text-center">Sanksi</th>
                        <th class="text-center">Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        @php $pct = $row->total_days > 0 ? round($row->submitted / $row->total_days * 100) : 0; @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $row->user->name }}</div>
                                <div class="text-muted" style="font-size:.8rem">{{ $row->user->position ?? '—' }}</div>
                            </td>
                            <td class="text-muted small">{{ $row->user->level_name }}</td>
                            <td class="text-center">
                                <div class="fw-semibold">{{ $row->submitted }}/{{ $row->total_days }}</div>
                                <div class="progress mt-1" style="height:5px; width:80px; margin:0 auto">
                                    <div class="progress-bar {{ $pct >= 80 ? 'bg-success' : ($pct >= 50 ? 'bg-warning' : 'bg-danger') }}"
                                         style="width:{{ $pct }}%"></div>
                                </div>
                                <div class="text-muted" style="font-size:.75rem">{{ $pct }}%</div>
                            </td>
                            <td class="text-center">
                                @if($row->user->isSecurity())
                                    @if($row->overtime_hours > 0)
                                        <span class="badge-soft bg-soft-warning">{{ rtrim(rtrim(number_format($row->overtime_hours, 1), '0'), '.') }} jam</span>
                                    @else <span class="text-muted">—</span>
                                    @endif
                                @elseif($row->overtime > 0)
                                    <span class="badge-soft bg-soft-warning">{{ $row->overtime }}x</span>
                                @else <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($row->need_help > 0)
                                    <span class="badge-soft bg-soft-danger">{{ $row->need_help }}x</span>
                                @else <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($row->late > 0)
                                    <span class="badge-soft bg-soft-danger">{{ $row->late }}x</span>
                                @else
                                    <span class="badge-soft bg-soft-success">0</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modal-detail-{{ $row->user->id }}">
                                    <i class="bi bi-list-ul me-1"></i>Detail
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@empty
    <div class="card">
        <div class="card-body text-center text-muted py-5">
            <i class="bi bi-inbox display-5 d-block mb-2 opacity-50"></i>
            Tidak ada data untuk bulan ini.
        </div>
    </div>
@endforelse

{{-- Modal detail per anggota --}}
@php
$splitCell = function(string $text): array {
    $result = [];
    foreach (preg_split('/\r\n|\n|\r/', $text) as $line) {
        foreach (preg_split('/\s+-\s+/', $line) as $part) {
            $part = trim($part, " \t-•·");
            if ($part !== '') $result[] = $part;
        }
    }
    return $result ?: [trim($text)];
};
@endphp

@foreach($byDivision as $division => $rows)
    @foreach($rows as $row)
        <div class="modal fade" id="modal-detail-{{ $row->user->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header" style="background:#f0f4ff; border-bottom:2px solid #c7d7fa">
                        <div>
                            <h5 class="modal-title fw-bold mb-0">
                                <i class="bi bi-person-lines-fill me-2 text-primary"></i>{{ $row->user->name }}
                            </h5>
                            <div class="text-muted small">
                                {{ $row->user->level_name }} &mdash; {{ $row->user->division ?? '—' }}
                                @if($row->user->position) &mdash; {{ $row->user->position }} @endif
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-0">

                        {{-- Ringkasan mini --}}
                        <div class="d-flex gap-3 p-3 border-bottom flex-wrap" style="background:#fafbff">
                            @php $pct = $row->total_days > 0 ? round($row->submitted / $row->total_days * 100) : 0; @endphp
                            <div class="text-center px-3">
                                <div class="fw-bold h5 mb-0">{{ $row->submitted }}/{{ $row->total_days }}</div>
                                <div class="small text-muted">Laporan</div>
                                <div class="progress mt-1" style="height:4px;width:70px;margin:0 auto">
                                    <div class="progress-bar {{ $pct >= 80 ? 'bg-success' : ($pct >= 50 ? 'bg-warning' : 'bg-danger') }}"
                                         style="width:{{ $pct }}%"></div>
                                </div>
                                <div class="text-muted" style="font-size:.75rem">{{ $pct }}%</div>
                            </div>
                            <div class="vr"></div>
                            <div class="text-center px-3">
                                @if($row->user->isSecurity())
                                    <div class="fw-bold h5 mb-0 {{ $row->overtime_hours > 0 ? 'text-warning' : 'text-muted' }}">{{ rtrim(rtrim(number_format($row->overtime_hours, 1), '0'), '.') }} <span class="small">jam</span></div>
                                    <div class="small text-muted">Lembur ({{ $row->overtime }} shift)</div>
                                @else
                                    <div class="fw-bold h5 mb-0 {{ $row->overtime > 0 ? 'text-warning' : 'text-muted' }}">{{ $row->overtime }}</div>
                                    <div class="small text-muted">Lembur</div>
                                @endif
                            </div>
                            <div class="vr"></div>
                            <div class="text-center px-3">
                                <div class="fw-bold h5 mb-0 {{ $row->need_help > 0 ? 'text-danger' : 'text-muted' }}">{{ $row->need_help }}</div>
                                <div class="small text-muted">Butuh Bantuan</div>
                            </div>
                            <div class="vr"></div>
                            <div class="text-center px-3">
                                <div class="fw-bold h5 mb-0 {{ $row->late > 0 ? 'text-danger' : 'text-success' }}">{{ $row->late }}</div>
                                <div class="small text-muted">Sanksi</div>
                            </div>
                        </div>

                        {{-- Tabel detail laporan — full 1 bulan --}}
                        @php
                            $schedule    = $row->user->work_schedule ?? \App\Models\User::SCHEDULE_5DAYS;
                            $holidayDays = $schedule === \App\Models\User::SCHEDULE_6DAYS ? [0] : [0, 6];
                            $isSecurity  = $schedule === \App\Models\User::SCHEDULE_SECURITY;
                            $secSchedule = $isSecurity ? ($securitySchedules->get($row->user->id) ?? collect()) : collect();
                            $today       = \Carbon\Carbon::today();
                            $reportsByDate = $row->reports->keyBy(fn($r) => $r->report_date->toDateString());
                            $memberLeaves = $leaves->get($row->user->id) ?? [];
                            $iter        = $startDate->copy();
                        @endphp
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" style="font-size:.85rem">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:130px;min-width:130px">Tanggal</th>
                                        <th style="min-width:300px">Pekerjaan Diselesaikan</th>
                                        <th style="min-width:240px">Belum Selesai</th>
                                        <th style="min-width:240px">Hambatan</th>
                                        <th style="width:90px;min-width:90px">Jam Selesai</th>
                                        <th style="width:120px;min-width:120px">Lembur</th>
                                        <th style="width:80px;min-width:80px">Sanksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @while($iter->lte($endDate))
                                        @php
                                            $dateStr         = $iter->toDateString();
                                            $r               = $reportsByDate->get($dateStr);
                                            $nationalHoliday = $holidays->get($dateStr);
                                            $secRow          = $isSecurity ? $secSchedule->get($dateStr) : null;
                                            $leave           = $memberLeaves[$dateStr] ?? null;
                                            // Lembur otomatis security: kelebihan durasi shift di atas 8 jam (shift 12 jam → 4 jam).
                                            // Security outsourcing dikecualikan (tanpa lembur otomatis).
                                            $autoOt          = ($isSecurity && $secRow && ! $secRow->is_off && ! $row->user->isOutsourcing()) ? $secRow->overtimeHours() : 0;
                                            $autoOtLabel     = $autoOt > 0 ? rtrim(rtrim(number_format($autoOt, 1), '0'), '.') . ' jam' : null;
                                            if ($isSecurity) {
                                                // Security: libur = hari off terjadwal. Libur nasional tidak memaksa off.
                                                $isWeekendOff = $secRow ? $secRow->is_off : false;
                                                $isHoliday    = $isWeekendOff;
                                            } else {
                                                $isWeekendOff = in_array($iter->dayOfWeek, $holidayDays, true);
                                                $isHoliday    = $isWeekendOff || $nationalHoliday !== null;
                                            }
                                            $isFuture        = $iter->gt($today);
                                        @endphp
                                        @php
                                            $isLeaveDay = $leave && ! $r && ! $isHoliday;
                                            $leaveBadge = $leave?->badge;
                                            $leaveBg    = $leaveBadge['bg'] ?? '#e5f4fb';
                                            $leaveColor = $leaveBadge['color'] ?? '#0c6f97';
                                            $leaveIcon  = $leaveBadge['icon'] ?? 'bi-calendar-heart';

                                            $trStyle = $isHoliday
                                                ? 'background:#fef2f2'
                                                : ($isLeaveDay ? 'background:' . $leaveBg : ($isFuture ? 'background:#fafafa' : ''));
                                            $dateColor = $isHoliday ? '#b02a37' : ($isLeaveDay ? $leaveColor : 'inherit');
                                            $dayColor = $isHoliday ? '#b02a37' : ($isLeaveDay ? $leaveColor : '#64748b');
                                            $holidayLabel = $nationalHoliday
                                                ? $nationalHoliday->name
                                                : 'Libur';
                                        @endphp
                                        <tr style="{{ $trStyle }}">
                                            <td class="fw-semibold small" style="color:{{ $dateColor }}">
                                                {{ $iter->translatedFormat('d M Y') }}
                                                <div class="small fw-normal" style="font-size:.75rem; color:{{ $dayColor }}">
                                                    {{ $iter->translatedFormat('l') }}
                                                    @if($isHoliday) <i class="bi bi-calendar-x ms-1"></i> @endif
                                                </div>
                                                @if($isSecurity && $secRow && ! $secRow->is_off)
                                                    <div class="mt-1">
                                                        <span class="badge" style="background:#0d6efd; color:#fff; font-size:.68rem; font-weight:600">
                                                            <i class="bi bi-clock me-1"></i>{{ $secRow->shift_label }}
                                                        </span>
                                                    </div>
                                                @endif
                                                @if($nationalHoliday)
                                                    <div class="mt-1" style="font-size:.7rem; color:#b02a37; font-weight:600">
                                                        <i class="bi bi-flag-fill me-1"></i>{{ $nationalHoliday->name }}
                                                    </div>
                                                @endif
                                                @if($leave)
                                                    <div class="mt-1">
                                                        <span class="badge" style="background:{{ $leaveColor }}; color:#fff; font-size:.68rem; font-weight:600">
                                                            <i class="bi {{ $leaveIcon }} me-1"></i>{{ $leave->type_label }}
                                                        </span>
                                                    </div>
                                                @endif
                                                @if($isHoliday && $r)
                                                    <span class="badge mt-1" style="background:#b02a37; color:#fff; font-size:.65rem; font-weight:600">
                                                        <i class="bi bi-stopwatch me-1"></i>
                                                        Lembur di {{ $nationalHoliday ? 'libur nasional' : 'hari libur' }}
                                                    </span>
                                                @endif
                                            </td>

                                            @if($isHoliday && !$r)
                                                <td colspan="6" class="text-center small fst-italic" style="color:#b02a37">
                                                    <i class="bi bi-calendar-x me-1"></i>{{ $holidayLabel }}
                                                </td>
                                            @elseif($leave && !$r)
                                                <td colspan="6" class="text-center small fst-italic" style="color:{{ $leaveColor }}">
                                                    <i class="bi {{ $leaveIcon }} me-1"></i>{{ $leave->type_label }}
                                                    @if($leave->reason) — {{ $leave->reason }} @endif
                                                </td>
                                            @elseif(!$r)
                                                <td class="text-center text-muted small">—</td>
                                                <td class="text-center text-muted small">—</td>
                                                <td class="text-center text-muted small">—</td>
                                                <td class="text-center text-muted small">—</td>
                                                <td class="text-center">
                                                    @if($autoOtLabel)
                                                        <span class="badge-soft bg-soft-warning" style="font-size:.75rem" title="Lembur otomatis dari shift 12 jam">{{ $autoOtLabel }}</span>
                                                    @else
                                                        <span class="text-muted small">—</span>
                                                    @endif
                                                </td>
                                                <td class="text-center text-muted small">—</td>
                                            @else
                                                <td class="small">
                                                    @php $lines = $splitCell($r->completed_work); @endphp
                                                    @if(count($lines) > 1)
                                                        <ul class="mb-0 ps-3">
                                                            @foreach($lines as $line)<li>{{ $line }}</li>@endforeach
                                                        </ul>
                                                    @else
                                                        {{ $lines[0] }}
                                                    @endif
                                                </td>
                                                <td class="small text-muted">
                                                    @if($r->unfinished_work)
                                                        @php $lines = $splitCell($r->unfinished_work); @endphp
                                                        @if(count($lines) > 1)
                                                            <ul class="mb-0 ps-3">
                                                                @foreach($lines as $line)<li>{{ $line }}</li>@endforeach
                                                            </ul>
                                                        @else
                                                            {{ $lines[0] }}
                                                        @endif
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td class="small text-muted">
                                                    @if($r->obstacles)
                                                        @php $lines = $splitCell($r->obstacles); @endphp
                                                        @if(count($lines) > 1)
                                                            <ul class="mb-0 ps-3">
                                                                @foreach($lines as $line)<li>{{ $line }}</li>@endforeach
                                                            </ul>
                                                        @else
                                                            {{ $lines[0] }}
                                                        @endif
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td class="text-center small">
                                                    {{ substr($r->work_finished_at, 0, 5) }}
                                                </td>
                                                <td class="text-center">
                                                    @if($isSecurity)
                                                        @if($autoOtLabel)
                                                            <span class="badge-soft bg-soft-warning" style="font-size:.75rem" title="Lembur otomatis dari shift 12 jam">{{ $autoOtLabel }}</span>
                                                        @else
                                                            <span class="text-muted">—</span>
                                                        @endif
                                                    @elseif($r->overtime_status)
                                                        <span class="badge-soft bg-soft-warning" style="font-size:.75rem">
                                                            {{ $r->overtime_start ? substr($r->overtime_start,0,5).' - '.substr($r->overtime_end,0,5) : 'Ya' }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @if($r->is_late)
                                                        <span class="badge-soft bg-soft-danger" style="font-size:.75rem">Sanksi</span>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                            @endif
                                        </tr>
                                        @php $iter->addDay(); @endphp
                                    @endwhile
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="{{ route('daily-reports.bulanan.download', ['user_id' => $row->user->id, 'month' => $month, 'year' => $year]) }}"
                           class="btn btn-success btn-sm me-auto">
                            <i class="bi bi-file-earmark-spreadsheet me-1"></i>Download XLS
                        </a>
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endforeach

@endsection

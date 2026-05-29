@extends('layouts.app')

@section('title', 'Jadwal Security — ' . $monthLabel)

@php
    $hasFullStaff = $users->count() === $requiredStaff;
@endphp

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <h2 class="h4 fw-bold mb-1">
            <i class="bi bi-shield-check text-primary me-2"></i>Jadwal Security
        </h2>
        <p class="text-muted small mb-0">Atur shift security per tanggal — {{ $monthLabel }}</p>
    </div>
    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
</div>

{{-- Info pola + pemetaan slot --}}
<div class="card mb-3">
    <div class="card-body py-3">
        <div class="small text-muted mb-2">
            <i class="bi bi-info-circle me-1"></i>
            Klik <strong>Generate</strong> untuk mengisi jadwal otomatis dari pola rotasi 3-minggu (A→B→C).
            Sel yang Anda ubah manual ditandai <span class="badge bg-warning text-dark">manual</span> dan
            <strong>tidak akan ditimpa</strong> saat generate ulang.
        </div>
        @if($hasFullStaff)
            <div class="small">
                Pemetaan libur tetap:
                @foreach($users as $i => $u)
                    <span class="badge rounded-pill bg-light text-dark border me-1">
                        {{ $u->name }} → libur tiap {{ ['Senin','Selasa','Rabu'][$i] ?? '—' }}
                    </span>
                @endforeach
            </div>
        @else
            <div class="alert alert-warning mb-0 py-2 small">
                <i class="bi bi-exclamation-triangle me-1"></i>
                Generator membutuhkan <strong>tepat {{ $requiredStaff }} personel security aktif</strong>
                (user dengan jadwal kerja "Security"). Saat ini terdaftar <strong>{{ $users->count() }}</strong>.
                Anda tetap bisa mengisi jadwal manual di bawah.
            </div>
        @endif
    </div>
</div>

{{-- Filter bulan + tombol generate --}}
<div class="card mb-3">
    <div class="card-body py-2 d-flex flex-wrap align-items-center gap-2">
        <form method="GET" action="{{ route('security-schedule.index') }}" class="d-flex align-items-center gap-2 flex-wrap">
            <label class="form-label mb-0 text-muted small fw-semibold">Bulan</label>
            <select name="month" class="form-select form-select-sm" style="width:130px">
                @foreach(range(1,12) as $m)
                    <option value="{{ $m }}" @selected($m == $month)>
                        {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                    </option>
                @endforeach
            </select>
            <select name="year" class="form-select form-select-sm" style="width:90px">
                @foreach(range(now()->year - 2, now()->year + 1) as $y)
                    <option value="{{ $y }}" @selected($y == $year)>{{ $y }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-search me-1"></i>Tampilkan
            </button>
        </form>

        <form method="POST" action="{{ route('security-schedule.generate') }}" class="ms-sm-auto"
              onsubmit="return confirm('Generate jadwal {{ $monthLabel }} dari pola? Sel manual tetap dipertahankan.');">
            @csrf
            <input type="hidden" name="month" value="{{ $month }}">
            <input type="hidden" name="year" value="{{ $year }}">
            <button type="submit" class="btn btn-outline-success btn-sm" @disabled(!$hasFullStaff)>
                <i class="bi bi-arrow-repeat me-1"></i>Generate Jadwal
            </button>
        </form>
    </div>
</div>

@if($users->isEmpty())
    <div class="card">
        <div class="card-body text-center text-muted py-5">
            <i class="bi bi-person-x display-5 d-block mb-2 opacity-50"></i>
            Belum ada user dengan jadwal kerja <strong>Security</strong>.
            Atur lewat <a href="{{ route('users.index') }}">Manajemen User</a> atau
            <a href="{{ route('work-schedule.index') }}">Jadwal Kerja</a>.
        </div>
    </div>
@else
<form method="POST" action="{{ route('security-schedule.save-all') }}">
    @csrf
    <input type="hidden" name="month" value="{{ $month }}">
    <input type="hidden" name="year" value="{{ $year }}">

    <div class="card">
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0" style="font-size:.85rem">
                <thead class="table-light">
                    <tr>
                        <th style="width:150px; min-width:150px">Tanggal</th>
                        @foreach($users as $u)
                            <th class="text-center" style="min-width:160px">
                                {{ $u->name }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($days as $day)
                        @php
                            $dateStr = $day->toDateString();
                            $isSunday = $day->dayOfWeek === 0;
                        @endphp
                        <tr style="{{ $isSunday ? 'background:#fef2f2' : '' }}">
                            <td class="fw-semibold small">
                                {{ $day->translatedFormat('d M Y') }}
                                <div class="fw-normal" style="font-size:.75rem; color:{{ $isSunday ? '#b02a37' : '#64748b' }}">
                                    {{ $day->translatedFormat('l') }}
                                </div>
                            </td>
                            @foreach($users as $u)
                                @php
                                    $sched    = $schedules->get($u->id . '|' . $dateStr);
                                    $code     = $sched ? $sched->shiftCode() : 'off';
                                    $isManual = $sched ? $sched->is_manual : false;
                                @endphp
                                <td class="p-1">
                                    <select name="cells[{{ $u->id }}][{{ $dateStr }}]"
                                            class="form-select form-select-sm shift-cell {{ $isManual ? 'is-manual' : '' }}"
                                            data-orig="{{ $code }}">
                                        @foreach(\App\Models\SecuritySchedule::SHIFT_OPTIONS as $optCode => $opt)
                                            <option value="{{ $optCode }}" @selected($optCode === $code)>
                                                {{ $opt['label'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="orig[{{ $u->id }}][{{ $dateStr }}]" value="{{ $code }}">
                                    <input type="hidden" name="manual[{{ $u->id }}][{{ $dateStr }}]" value="{{ $isManual ? 1 : 0 }}">
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer py-3 px-4 d-flex justify-content-between align-items-center">
            <span class="text-muted small">
                <span class="badge bg-warning text-dark">manual</span> = sel yang diedit tangan, aman dari generate ulang.
            </span>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-1"></i>Simpan Perubahan
            </button>
        </div>
    </div>
</form>
@endif

@endsection

@push('styles')
<style>
    .shift-cell.is-manual {
        border-color: #f0ad4e;
        box-shadow: 0 0 0 .12rem rgba(240, 173, 78, .25);
    }
    .shift-cell.shift-off {
        color: #b02a37;
        font-weight: 600;
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    function paint(sel) {
        sel.classList.toggle('shift-off', sel.value === 'off');
    }
    document.querySelectorAll('.shift-cell').forEach(function (sel) {
        paint(sel);
        sel.addEventListener('change', function () {
            paint(sel);
            // Tandai manual saat nilai berbeda dari aslinya.
            if (sel.value !== sel.dataset.orig) {
                sel.classList.add('is-manual');
            }
        });
    });
})();
</script>
@endpush

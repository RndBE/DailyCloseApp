@extends('layouts.app')

@section('title', 'Pengaturan Jadwal Kerja')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <div>
        <h2 class="h4 fw-bold mb-1">
            <i class="bi bi-clock text-primary me-2"></i>Pengaturan Jadwal Kerja
        </h2>
        <p class="text-muted small mb-0">Atur jadwal kerja untuk setiap karyawan yang aktif.</p>
    </div>
    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
</div>

{{-- Info jadwal --}}
<div class="row g-3 mb-4">
    @foreach(\App\Models\User::WORK_SCHEDULE_DETAILS as $key => $detail)
    <div class="col-md-6">
        <div class="card h-100" style="border:2px solid {{ $key === \App\Models\User::SCHEDULE_5DAYS ? '#3b82f6' : '#8b5cf6' }};">
            <div class="card-body py-3 px-4">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge rounded-pill"
                          style="background:{{ $key === \App\Models\User::SCHEDULE_5DAYS ? '#3b82f6' : '#8b5cf6' }}; font-size:.8rem;">
                        {{ $detail['label'] }}
                    </span>
                </div>
                <div class="small text-muted mb-1">
                    <i class="bi bi-calendar-week me-1"></i>
                    <strong>{{ $detail['weekdays'] }}</strong> — {{ $detail['hours'] }}
                </div>
                @if($detail['saturday'])
                <div class="small text-muted">
                    <i class="bi bi-calendar me-1"></i>
                    <strong>{{ $detail['saturday'] }}</strong>
                </div>
                @else
                <div class="small text-muted fst-italic">Libur di hari Sabtu</div>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Form utama --}}
<form method="POST" action="{{ route('work-schedule.save-all') }}" id="scheduleForm">
    @csrf

    <div class="card">
        <div class="card-header py-2 px-4 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <span class="fw-semibold">
                <i class="bi bi-people me-2 text-primary"></i>
                Daftar Karyawan
                <span class="badge bg-secondary ms-1" style="font-size:.75rem">{{ $users->count() }} orang</span>
            </span>
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small">Terapkan ke semua:</span>
                <select id="bulkSchedule" class="form-select form-select-sm" style="width:auto">
                    @foreach(\App\Models\User::WORK_SCHEDULE_DETAILS as $key => $detail)
                        <option value="{{ $key }}">{{ $detail['label'] }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="applyToAll()">
                    <i class="bi bi-lightning me-1"></i>Terapkan
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead style="background:#f8fafc; font-size:.83rem; color:#64748b">
                    <tr>
                        <th class="ps-4" style="width:40px">#</th>
                        <th>Nama</th>
                        <th>Level</th>
                        <th>Divisi</th>
                        <th>Jabatan</th>
                        <th style="width:220px">Jadwal Kerja</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $i => $u)
                    <tr>
                        <td class="ps-4 text-muted small">{{ $i + 1 }}</td>
                        <td>
                            <div class="fw-semibold" style="font-size:.9rem">{{ $u->name }}</div>
                            <div class="text-muted" style="font-size:.78rem">{{ $u->email }}</div>
                        </td>
                        <td>
                            <span class="badge rounded-pill"
                                  style="font-size:.73rem; background:{{ ['','#dc2626','#2563eb','#059669','#64748b'][$u->level] ?? '#64748b' }}">
                                L{{ $u->level }} {{ $u->level_name }}
                            </span>
                        </td>
                        <td class="text-muted small">{{ $u->division ?? '—' }}</td>
                        <td class="text-muted small">{{ $u->position ?? '—' }}</td>
                        <td>
                            <select name="schedules[{{ $u->id }}]"
                                    class="form-select form-select-sm schedule-select"
                                    style="border-color: {{ ($u->work_schedule ?? \App\Models\User::SCHEDULE_5DAYS) === \App\Models\User::SCHEDULE_5DAYS ? '#3b82f6' : '#8b5cf6' }}">
                                @foreach(\App\Models\User::WORK_SCHEDULE_DETAILS as $key => $detail)
                                    <option value="{{ $key }}"
                                            @selected(($u->work_schedule ?? \App\Models\User::SCHEDULE_5DAYS) === $key)>
                                        {{ $detail['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="bi bi-inbox me-1"></i>Tidak ada karyawan aktif.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer py-3 px-4 d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-1"></i>Simpan Semua Perubahan
            </button>
        </div>
    </div>
</form>

@endsection

@push('scripts')
<script>
const COLORS = {
    '5_days': '#3b82f6',
    '6_days': '#8b5cf6',
};

function applyToAll() {
    const val = document.getElementById('bulkSchedule').value;
    document.querySelectorAll('.schedule-select').forEach(el => {
        el.value = val;
        el.style.borderColor = COLORS[val] ?? '';
    });
}

document.querySelectorAll('.schedule-select').forEach(el => {
    el.addEventListener('change', function () {
        this.style.borderColor = COLORS[this.value] ?? '';
    });
});
</script>
@endpush

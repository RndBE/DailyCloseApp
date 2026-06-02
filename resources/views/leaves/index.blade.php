@extends('layouts.app')

@section('title', 'Cuti / Sakit')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <h2 class="h4 fw-bold mb-1">
            <i class="bi bi-calendar-heart text-primary me-2"></i>Cuti / Sakit
        </h2>
        <p class="text-muted mb-0 small">
            Catat hari cuti atau sakit Anda. Hari yang dicatat di sini tidak akan dihitung sebagai laporan yang belum dikirim.
        </p>
    </div>
    <a href="{{ route('daily-reports.mine') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
</div>

{{-- Form ajukan --}}
<div class="card mb-3">
    <div class="card-header py-2 px-3 d-flex align-items-center" style="background:#f0f4ff; border-bottom:2px solid #c7d7fa">
        <span class="fw-semibold"><i class="bi bi-plus-circle me-2 text-primary"></i>Catat Cuti / Sakit</span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('leaves.store') }}" class="row g-3">
            @csrf
            <div class="col-md-2">
                <label class="form-label">Jenis <span class="text-danger">*</span></label>
                <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                    @foreach(\App\Models\Leave::TYPES as $k => $label)
                        <option value="{{ $k }}" @selected(old('type') === $k)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-3">
                <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                <input type="date" name="start_date" value="{{ old('start_date') }}"
                       class="form-control @error('start_date') is-invalid @enderror" required>
                @error('start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-3">
                <label class="form-label">Tanggal Selesai <span class="text-danger">*</span></label>
                <input type="date" name="end_date" value="{{ old('end_date') }}"
                       class="form-control @error('end_date') is-invalid @enderror" required>
                @error('end_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Keterangan</label>
                <input type="text" name="reason" value="{{ old('reason') }}"
                       class="form-control @error('reason') is-invalid @enderror"
                       placeholder="Opsional — mis. acara keluarga, surat dokter">
                @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>Simpan
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Daftar --}}
<div class="card">
    <div class="card-header py-2 px-3 d-flex align-items-center justify-content-between">
        <span class="fw-semibold">
            <i class="bi bi-list-ul me-2 text-primary"></i>Riwayat Cuti / Sakit
        </span>
        <span class="text-muted small">{{ $leaves->count() }} catatan</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:50px" class="text-center">#</th>
                    <th style="width:120px">Jenis</th>
                    <th style="width:320px">Tanggal</th>
                    <th style="width:80px" class="text-center">Hari</th>
                    <th>Keterangan</th>
                    <th style="width:90px" class="text-end pe-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($leaves as $i => $leave)
                    <tr>
                        <td class="text-center text-muted small">{{ $i + 1 }}</td>
                        <td>
                            @if($leave->type === \App\Models\Leave::TYPE_SAKIT)
                                <span class="badge-soft" style="background:#fdecec;color:#b02a37">
                                    <i class="bi bi-thermometer-half me-1"></i>{{ $leave->type_label }}
                                </span>
                            @else
                                <span class="badge-soft" style="background:#e5f4fb;color:#0c6f97">
                                    <i class="bi bi-calendar-heart me-1"></i>{{ $leave->type_label }}
                                </span>
                            @endif
                        </td>
                        <td class="small">
                            @if($leave->start_date->isSameDay($leave->end_date))
                                {{ $leave->start_date->translatedFormat('d F Y') }}
                            @else
                                {{ $leave->start_date->translatedFormat('d M Y') }}
                                &ndash; {{ $leave->end_date->translatedFormat('d M Y') }}
                            @endif
                        </td>
                        <td class="text-center">{{ $leave->days_count }}</td>
                        <td class="text-muted small">{{ $leave->reason ?: '—' }}</td>
                        <td class="text-end pe-3">
                            <form method="POST" action="{{ route('leaves.destroy', $leave) }}" class="d-inline"
                                  onsubmit="return confirm('Hapus catatan cuti/sakit ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="bi bi-calendar2-x display-5 d-block mb-2 opacity-50"></i>
                            Belum ada catatan cuti/sakit.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

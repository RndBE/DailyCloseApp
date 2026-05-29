@extends('layouts.app')

@section('title', 'Hari Libur Nasional')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <h2 class="h4 fw-bold mb-1">
            <i class="bi bi-calendar-event text-primary me-2"></i>Hari Libur Nasional
        </h2>
        <p class="text-muted mb-0 small">
            Daftar tanggal libur nasional &amp; cuti bersama. Tanggal-tanggal ini akan ditandai merah pada laporan bulanan.
        </p>
    </div>
    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
</div>

{{-- Filter tahun --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex align-items-center gap-2 flex-wrap">
            <label class="form-label mb-0 text-muted small fw-semibold">Tahun</label>
            <select name="year" class="form-select form-select-sm" style="width:110px" onchange="this.form.submit()">
                @foreach(range(now()->year - 2, now()->year + 2) as $y)
                    <option value="{{ $y }}" @selected($y == $year)>{{ $y }}</option>
                @endforeach
            </select>
            <span class="text-muted small ms-2">
                <i class="bi bi-info-circle me-1"></i>{{ $holidays->count() }} tanggal libur terdaftar tahun {{ $year }}
            </span>
        </form>
    </div>
</div>

{{-- Form tambah hari libur --}}
<div class="card mb-3">
    <div class="card-header py-2 px-3 d-flex align-items-center" style="background:#f0f4ff; border-bottom:2px solid #c7d7fa">
        <span class="fw-semibold"><i class="bi bi-plus-circle me-2 text-primary"></i>Tambah Hari Libur</span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('holidays.store') }}" class="row g-3">
            @csrf
            <div class="col-md-3">
                <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                <input type="date" name="date" value="{{ old('date') }}" class="form-control @error('date') is-invalid @enderror" required>
                @error('date') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-5">
                <label class="form-label">Nama Hari Libur <span class="text-danger">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror"
                       placeholder="Contoh: Idul Fitri, HUT RI, Natal" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-2">
                <label class="form-label">Jenis</label>
                <select name="type" class="form-select">
                    @foreach(\App\Models\Holiday::TYPES as $k => $label)
                        <option value="{{ $k }}" @selected(old('type') === $k)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-save me-1"></i>Simpan
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Tabel daftar hari libur --}}
<div class="card">
    <div class="card-header py-2 px-3 d-flex align-items-center justify-content-between">
        <span class="fw-semibold">
            <i class="bi bi-list-ul me-2 text-primary"></i>
            Daftar Hari Libur {{ $year }}
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:50px" class="text-center">#</th>
                    <th style="width:160px">Tanggal</th>
                    <th style="width:120px">Hari</th>
                    <th>Nama</th>
                    <th style="width:160px">Jenis</th>
                    <th style="width:140px" class="text-end pe-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($holidays as $i => $h)
                    <tr>
                        <td class="text-center text-muted small">{{ $i + 1 }}</td>
                        <td class="fw-semibold" style="color:#b02a37">
                            {{ $h->date->translatedFormat('d F Y') }}
                        </td>
                        <td class="small">{{ $h->date->translatedFormat('l') }}</td>
                        <td>{{ $h->name }}</td>
                        <td>
                            @if($h->type === \App\Models\Holiday::TYPE_NASIONAL)
                                <span class="badge-soft" style="background:#fdecec;color:#b02a37">
                                    <i class="bi bi-flag-fill me-1"></i>{{ $h->type_label }}
                                </span>
                            @else
                                <span class="badge-soft" style="background:#fff5e6;color:#b15c00">
                                    <i class="bi bi-calendar-heart me-1"></i>{{ $h->type_label }}
                                </span>
                            @endif
                        </td>
                        <td class="text-end pe-3">
                            <button type="button" class="btn btn-sm btn-outline-primary"
                                    data-bs-toggle="modal" data-bs-target="#edit-{{ $h->id }}">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form method="POST" action="{{ route('holidays.destroy', $h) }}" class="d-inline"
                                  onsubmit="return confirm('Hapus hari libur ini?')">
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
                            Belum ada hari libur untuk tahun {{ $year }}.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Modal edit per item --}}
@foreach($holidays as $h)
    <div class="modal fade" id="edit-{{ $h->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('holidays.update', $h) }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-header" style="background:#f0f4ff; border-bottom:2px solid #c7d7fa">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-pencil-square me-2 text-primary"></i>Edit Hari Libur
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" name="date" value="{{ $h->date->toDateString() }}" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama Hari Libur <span class="text-danger">*</span></label>
                            <input type="text" name="name" value="{{ $h->name }}" class="form-control" required>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Jenis</label>
                            <select name="type" class="form-select">
                                @foreach(\App\Models\Holiday::TYPES as $k => $label)
                                    <option value="{{ $k }}" @selected($h->type === $k)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-save me-1"></i>Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

@endsection

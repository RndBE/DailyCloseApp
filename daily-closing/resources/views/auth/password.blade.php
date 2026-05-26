@extends('layouts.app')

@section('title', 'Ubah Password')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <h2 class="h4 fw-bold mb-1">
            <i class="bi bi-shield-lock text-primary me-2"></i>Ubah Password
        </h2>
        <p class="text-muted mb-0 small">Perbarui password akun Anda. Minimal 6 karakter.</p>
    </div>
    <div>
        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('password.update') }}" novalidate>
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label" for="current_password">Password Saat Ini <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock text-muted"></i></span>
                            <input type="password" id="current_password" name="current_password"
                                   class="form-control @error('current_password') is-invalid @enderror" required autofocus>
                        </div>
                        @error('current_password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="password">Password Baru <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-key text-muted"></i></span>
                            <input type="password" id="password" name="password"
                                   class="form-control @error('password') is-invalid @enderror" required>
                        </div>
                        <div class="form-text">Minimal 6 karakter.</div>
                        @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="password_confirmation">Konfirmasi Password Baru <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-key-fill text-muted"></i></span>
                            <input type="password" id="password_confirmation" name="password_confirmation"
                                   class="form-control" required>
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end">
                        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-lg me-1"></i> Batal
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Simpan Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-body">
                <h6 class="fw-semibold mb-2"><i class="bi bi-info-circle text-primary me-1"></i> Tips keamanan</h6>
                <ul class="small text-muted mb-0 ps-3">
                    <li>Gunakan minimal 6 karakter — kombinasi huruf, angka, dan simbol lebih aman.</li>
                    <li>Jangan gunakan password yang sama dengan akun lain.</li>
                    <li>Jangan bagikan password ke siapa pun, termasuk rekan kerja.</li>
                    <li>Setelah berhasil ubah password, Anda akan tetap login di perangkat ini.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

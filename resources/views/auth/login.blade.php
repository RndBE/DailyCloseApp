@extends('layouts.guest')

@section('title', 'Login')

@section('content')
<div class="auth-card">
    <div class="text-center mb-4">
        @php
            $logoFile = file_exists(public_path('images/app-logo.png'))
                ? 'images/app-logo.png'
                : 'images/app-logo.svg';
        @endphp
        <div class="login-logo-wrap">
            <img src="{{ asset($logoFile) }}" alt="Logo aplikasi" class="login-logo">
        </div>
        <h1 class="h4 fw-bold mb-1">Daily Closing System</h1>
        <p class="text-muted small mb-0">Masuk untuk mengelola laporan pekerjaan harian Anda.</p>
    </div>

    @if (session('success'))
        <div class="alert border-0" style="background:#e7f7ee;color:#157347;border-radius:12px;">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login.attempt') }}" novalidate>
        @csrf
        <div class="mb-3">
            <label class="form-label" for="email">Email</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope text-muted"></i></span>
                <input type="email" id="email" name="email" value="{{ old('email') }}"
                       class="form-control @error('email') is-invalid @enderror"
                       placeholder="nama@perusahaan.com" required autofocus>
            </div>
            @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label class="form-label" for="password">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock text-muted"></i></span>
                <input type="password" id="password" name="password"
                       class="form-control @error('password') is-invalid @enderror"
                       placeholder="••••••••" required>
            </div>
            @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="remember" id="remember">
            <label class="form-check-label small text-muted" for="remember">Ingat saya pada perangkat ini</label>
        </div>

        <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-box-arrow-in-right me-2"></i>Masuk
        </button>
    </form>

    <p class="text-center text-muted small mt-4 mb-0">&copy; {{ date('Y') }} Daily Closing System</p>
</div>
@endsection

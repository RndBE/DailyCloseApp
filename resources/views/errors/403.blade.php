@extends('layouts.guest')

@section('title', 'Akses Ditolak')

@section('content')
<div class="auth-card text-center">
    <div class="brand-mark mx-auto mb-3" style="background:linear-gradient(135deg,#ef4444,#b91c1c)"><i class="bi bi-shield-lock"></i></div>
    <h1 class="h4 fw-bold mb-2">403 — Akses Ditolak</h1>
    <p class="text-muted small mb-4">{{ $exception->getMessage() ?: 'Anda tidak memiliki akses ke halaman ini.' }}</p>
    <a href="{{ url()->previous() }}" class="btn btn-outline-secondary me-2"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
    <a href="{{ route('dashboard') }}" class="btn btn-primary"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
</div>
@endsection

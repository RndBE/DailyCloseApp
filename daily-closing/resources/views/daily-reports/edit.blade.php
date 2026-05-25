@extends('layouts.app')

@section('title', 'Edit Laporan Harian')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <h2 class="h4 fw-bold mb-1"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Laporan</h2>
        <p class="text-muted mb-0 small">Perbarui informasi laporan tanggal {{ $report->report_date->translatedFormat('d F Y') }}.</p>
    </div>
    <a href="{{ route('daily-reports.show', $report) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
</div>

<form method="POST" action="{{ route('daily-reports.update', $report) }}">
    @method('PUT')
    @include('daily-reports._form', ['report' => $report])
</form>
@endsection

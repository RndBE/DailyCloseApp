<?php

use App\Http\Controllers\Api\ReportStatusController;
use Illuminate\Support\Facades\Route;

// Publik (tanpa autentikasi) — siapa saja yang tahu URL bisa mengakses.
// Daftar karyawan divisi Software yang belum mengirim laporan hari ini.
Route::get('/laporan/software/belum-kirim', [ReportStatusController::class, 'softwareNotSubmitted'])
    ->name('api.reports.software.not-submitted');

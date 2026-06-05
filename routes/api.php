<?php

use App\Http\Controllers\Api\InternalMobileTokenController;
use App\Http\Controllers\Api\InternalPayrollDailyReportController;
use App\Http\Controllers\Api\MobileAuthController;
use App\Http\Controllers\Api\MobileDailyReportController;
use App\Http\Controllers\Api\ReportStatusController;
use Illuminate\Support\Facades\Route;

// Publik (tanpa autentikasi) — siapa saja yang tahu URL bisa mengakses.
// Daftar karyawan divisi Software yang belum mengirim laporan hari ini.
Route::get('/laporan/software/belum-kirim', [ReportStatusController::class, 'softwareNotSubmitted'])
    ->name('api.reports.software.not-submitted');

Route::post('/internal/mobile-token', [InternalMobileTokenController::class, 'issue'])
    ->name('api.internal.mobile-token');
Route::get('/internal/payroll/daily-report-late', [InternalPayrollDailyReportController::class, 'lateCounts'])
    ->name('api.internal.payroll.daily-report-late');

Route::prefix('mobile')->group(function () {
    Route::post('/login', [MobileAuthController::class, 'login']);

    Route::middleware('mobile.token')->group(function () {
        Route::get('/me', [MobileAuthController::class, 'me']);
        Route::post('/logout', [MobileAuthController::class, 'logout']);

        Route::get('/daily-reports', [MobileDailyReportController::class, 'index']);
        Route::get('/daily-reports/today', [MobileDailyReportController::class, 'today']);
        Route::get('/team-daily-reports/access', [MobileDailyReportController::class, 'teamAccess']);
        Route::get('/team-daily-reports', [MobileDailyReportController::class, 'team']);
        Route::post('/daily-reports', [MobileDailyReportController::class, 'store']);
        Route::put('/daily-reports/{dailyReport}', [MobileDailyReportController::class, 'update']);
    });
});

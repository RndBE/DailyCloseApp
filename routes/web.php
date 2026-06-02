<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DailyReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\SecurityScheduleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkScheduleController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/password', [AuthController::class, 'editPassword'])->name('password.edit');
    Route::put('/password', [AuthController::class, 'updatePassword'])->name('password.update');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('daily-reports/mine', [DailyReportController::class, 'mine'])->name('daily-reports.mine');
    Route::get('daily-reports/rangkuman', [DailyReportController::class, 'rangkuman'])->name('daily-reports.rangkuman');
    Route::get('daily-reports/rangkuman/cetak', [DailyReportController::class, 'rangkumanCetak'])->name('daily-reports.rangkuman.cetak');
    Route::get('daily-reports/laporan-bulanan', [DailyReportController::class, 'laporanBulanan'])->name('daily-reports.bulanan');
    Route::get('daily-reports/laporan-bulanan/cetak', [DailyReportController::class, 'laporanBulananCetak'])->name('daily-reports.bulanan.cetak');
    Route::get('daily-reports/laporan-bulanan/download', [DailyReportController::class, 'laporanBulananDownload'])->name('daily-reports.bulanan.download');
    Route::resource('daily-reports', DailyReportController::class);

    Route::get('cuti', [LeaveController::class, 'index'])->name('leaves.index');
    Route::post('cuti', [LeaveController::class, 'store'])->name('leaves.store');
    Route::delete('cuti/{leave}', [LeaveController::class, 'destroy'])->name('leaves.destroy');

    Route::middleware('manage.users')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
    });

    Route::get('/pengaturan/jadwal-kerja', [WorkScheduleController::class, 'index'])->name('work-schedule.index');
    Route::post('/pengaturan/jadwal-kerja', [WorkScheduleController::class, 'saveAll'])->name('work-schedule.save-all');

    Route::get('/pengaturan/jadwal-security', [SecurityScheduleController::class, 'index'])->name('security-schedule.index');
    Route::post('/pengaturan/jadwal-security/generate', [SecurityScheduleController::class, 'generate'])->name('security-schedule.generate');
    Route::post('/pengaturan/jadwal-security', [SecurityScheduleController::class, 'saveAll'])->name('security-schedule.save-all');

    Route::resource('holidays', HolidayController::class)->except(['create', 'show', 'edit']);
});

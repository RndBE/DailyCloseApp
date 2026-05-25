@echo off
setlocal enabledelayedexpansion
title Daily Closing - Stop
color 0C

set "PID_FILE=%~dp0.server.pid"
set "PMA_PID_FILE=%~dp0.pma.pid"

echo.
echo ============================================
echo   Daily Closing System - Stopping...
echo ============================================
echo.

echo Mencari semua proses Laravel + phpMyAdmin...
powershell -NoProfile -Command "$killed = 0; Get-CimInstance Win32_Process -Filter 'Name=\"php.exe\"' | Where-Object { $_.CommandLine -match 'artisan\s+serve|vendor\\laravel\\framework|phpMyAdmin' } | ForEach-Object { Write-Host ('   Killing PID ' + $_.ProcessId + ' (' + ($_.CommandLine.Substring(0,[Math]::Min(60,$_.CommandLine.Length))) + ')'); try { Stop-Process -Id $_.ProcessId -Force -ErrorAction Stop; $killed++ } catch {} }; if ($killed -eq 0) { Write-Host '   Tidak ada server aktif.' } else { Write-Host ('   Total dihentikan: ' + $killed) }"

if exist "%PID_FILE%"     del "%PID_FILE%"     >NUL 2>&1
if exist "%PMA_PID_FILE%" del "%PMA_PID_FILE%" >NUL 2>&1

echo.
set /p ANS="Stop MySQL juga? (y/N): "
if /I "!ANS!"=="y" (
    echo Stopping MySQL...
    taskkill /IM mysqld.exe /F >NUL 2>&1
    echo   MySQL dihentikan.
) else (
    echo   MySQL tetap berjalan.
)

echo.
echo ============================================
echo   DONE.
echo ============================================
echo.
ping -n 4 127.0.0.1 >NUL 2>&1

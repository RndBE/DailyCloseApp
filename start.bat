@echo off
setlocal enabledelayedexpansion
title Daily Closing - Start
color 0B

set "PHP=C:\xampp\php\php.exe"
set "MYSQLD=C:\xampp\mysql\bin\mysqld.exe"
set "MYSQLINI=C:\xampp\mysql\bin\my.ini"
set "PMA_DIR=C:\xampp\phpMyAdmin"
set "APP_DIR=%~dp0."
set "PID_FILE=%~dp0.server.pid"
set "PMA_PID_FILE=%~dp0.pma.pid"
set "URL=http://127.0.0.1:8000"
set "PMA_URL=http://127.0.0.1:8080"

echo.
echo ============================================
echo   Daily Closing System - Starting...
echo ============================================
echo.

REM --- 1) MySQL ---
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I "mysqld.exe" >NUL
if errorlevel 1 (
    echo [1/4] Starting MySQL...
    start "" /B "%MYSQLD%" --defaults-file="%MYSQLINI%" --standalone
    ping -n 4 127.0.0.1 >NUL 2>&1
) else (
    echo [1/4] MySQL already running.
)

REM --- 2) Laravel server ---
set "OLDPID="
if exist "%PID_FILE%" set /p OLDPID=<"%PID_FILE%"
if defined OLDPID (
    tasklist /FI "PID eq !OLDPID!" 2>NUL | find "!OLDPID!" >NUL
    if not errorlevel 1 (
        echo [2/4] Laravel server already running ^(PID !OLDPID!^).
        goto :pma
    )
)
echo [2/4] Starting Laravel server on %URL% ...
powershell -NoProfile -Command "$p = Start-Process -FilePath '%PHP%' -ArgumentList 'artisan','serve','--host=127.0.0.1','--port=8000' -WorkingDirectory '%APP_DIR%' -WindowStyle Hidden -PassThru; Set-Content -Path '%PID_FILE%' -Value $p.Id; Write-Host ('   PID: ' + $p.Id)"
ping -n 3 127.0.0.1 >NUL 2>&1

:pma
REM --- 3) phpMyAdmin (via PHP built-in server) ---
set "OLDPMA="
if exist "%PMA_PID_FILE%" set /p OLDPMA=<"%PMA_PID_FILE%"
if defined OLDPMA (
    tasklist /FI "PID eq !OLDPMA!" 2>NUL | find "!OLDPMA!" >NUL
    if not errorlevel 1 (
        echo [3/4] phpMyAdmin already running ^(PID !OLDPMA!^).
        goto :open
    )
)
echo [3/4] Starting phpMyAdmin on %PMA_URL% ...
powershell -NoProfile -Command "$p = Start-Process -FilePath '%PHP%' -ArgumentList '-S','127.0.0.1:8080','-t','%PMA_DIR%' -WorkingDirectory '%PMA_DIR%' -WindowStyle Hidden -PassThru; Set-Content -Path '%PMA_PID_FILE%' -Value $p.Id; Write-Host ('   PID: ' + $p.Id)"
ping -n 3 127.0.0.1 >NUL 2>&1

:open
echo [4/4] Opening browser...
start "" %URL%

echo.
echo ============================================
echo   READY
echo     App        : %URL%
echo     phpMyAdmin : %PMA_URL%   ^(user: root, pass: kosong^)
echo   Use stop.bat to shut everything down.
echo ============================================
echo.
ping -n 4 127.0.0.1 >NUL 2>&1

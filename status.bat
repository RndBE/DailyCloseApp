@echo off
setlocal enabledelayedexpansion
title Daily Closing - Status
color 0E

set "PID_FILE=%~dp0.server.pid"
set "PMA_PID_FILE=%~dp0.pma.pid"
set "URL=http://127.0.0.1:8000"
set "PMA_URL=http://127.0.0.1:8080"

echo.
echo ============================================
echo   Daily Closing System - Status
echo ============================================
echo.

REM MySQL
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I "mysqld.exe" >NUL
if errorlevel 1 (
    echo [X] MySQL          : NOT RUNNING
) else (
    echo [V] MySQL          : RUNNING
)

REM Laravel server
set "PID="
if exist "%PID_FILE%" set /p PID=<"%PID_FILE%"
if defined PID (
    tasklist /FI "PID eq !PID!" 2>NUL | find "!PID!" >NUL
    if errorlevel 1 (
        echo [X] Laravel server : STALE PID file ^(!PID!^)
    ) else (
        echo [V] Laravel server : RUNNING ^(PID !PID!^)  -^>  %URL%
    )
) else (
    echo [X] Laravel server : NOT RUNNING
)

REM phpMyAdmin
set "PMA="
if exist "%PMA_PID_FILE%" set /p PMA=<"%PMA_PID_FILE%"
if defined PMA (
    tasklist /FI "PID eq !PMA!" 2>NUL | find "!PMA!" >NUL
    if errorlevel 1 (
        echo [X] phpMyAdmin     : STALE PID file ^(!PMA!^)
    ) else (
        echo [V] phpMyAdmin     : RUNNING ^(PID !PMA!^)  -^>  %PMA_URL%
    )
) else (
    echo [X] phpMyAdmin     : NOT RUNNING
)

echo.
pause

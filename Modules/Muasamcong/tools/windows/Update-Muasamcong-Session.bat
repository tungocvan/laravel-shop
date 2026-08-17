@echo off
setlocal EnableExtensions

title Muasamcong - Update Personal Session

set "SCRIPT=%~dp0Update-Muasamcong-Session.ps1"

if not exist "%SCRIPT%" (
    echo [LOI] Khong tim thay:
    echo %SCRIPT%
    pause
    exit /b 1
)

where powershell.exe >nul 2>&1
if errorlevel 1 (
    echo [LOI] Khong tim thay powershell.exe.
    pause
    exit /b 1
)

echo ============================================================
echo  CAP NHAT PERSONAL PAGE SESSION - MUASAMCONG
echo ============================================================
echo  Yeu cau:
echo  - Da mo Chrome bang Open-Muasamcong-Chrome.bat
echo  - Da dang nhap thanh cong tren Chrome rieng
echo  - WSL project: /var/www/source-laravel12
echo ============================================================
echo.

powershell.exe -NoLogo -NoProfile -ExecutionPolicy Bypass -File "%SCRIPT%" -DebugPort 9222 -WslProjectPath "/var/www/source-laravel12"
set "RC=%ERRORLEVEL%"

echo.
if not "%RC%"=="0" (
    echo [LOI] Cap nhat session khong thanh cong.
    echo Hay dam bao Chrome rieng dang mo va da dang nhap Mua sam cong.
) else (
    echo [OK] Session da duoc cap nhat va kiem tra thanh cong.
)

echo.
pause
exit /b %RC%

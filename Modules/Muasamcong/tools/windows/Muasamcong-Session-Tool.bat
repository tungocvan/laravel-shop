@echo off
setlocal EnableExtensions
set "PS1=%~dp0Muasamcong-Session-Tool.ps1"
if not exist "%PS1%" exit /b 1
:MENU
cls
echo ============================================================
echo  MUASAMCONG PERSONAL PAGE SESSION TOOL
echo ============================================================
echo.
echo 1. Kiem tra Cookie/SSO tren Chrome rieng
echo 2. Lam moi Session tu dong va cap nhat Server
echo 3. Mo Chrome de dang nhap Mua sam cong
echo 4. Gui Cookie hien tai len Server
echo 0. Thoat
echo.
set /p "CHOICE=Chon: "
if "%CHOICE%"=="1" goto CHECK
if "%CHOICE%"=="2" goto REFRESH
if "%CHOICE%"=="3" goto LOGIN
if "%CHOICE%"=="4" goto UPDATE
if "%CHOICE%"=="0" goto END
goto MENU
:CHECK
powershell.exe -NoLogo -NoProfile -ExecutionPolicy Bypass -File "%PS1%" -Action Check
pause
goto MENU
:REFRESH
powershell.exe -NoLogo -NoProfile -ExecutionPolicy Bypass -File "%PS1%" -Action Refresh
pause
goto MENU
:LOGIN
powershell.exe -NoLogo -NoProfile -ExecutionPolicy Bypass -File "%PS1%" -Action Login
pause
goto MENU
:UPDATE
powershell.exe -NoLogo -NoProfile -ExecutionPolicy Bypass -File "%PS1%" -Action Update
pause
goto MENU
:END
endlocal
exit /b 0

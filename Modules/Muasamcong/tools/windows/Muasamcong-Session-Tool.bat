@echo off
setlocal EnableExtensions
set "PS1=%~dp0Muasamcong-Session-Tool.ps1"
if not exist "%PS1%" exit /b 1
:MENU
cls
echo MUASAMCONG SESSION TOOL
echo.
echo 1. Kiem tra Session
echo 2. Dang nhap Mua sam cong
echo 3. Cap nhat Session len Server
echo 0. Thoat
echo.
set /p "CHOICE=Chon: "
if "%CHOICE%"=="1" goto CHECK
if "%CHOICE%"=="2" goto LOGIN
if "%CHOICE%"=="3" goto UPDATE
if "%CHOICE%"=="0" goto END
goto MENU
:CHECK
powershell.exe -NoLogo -NoProfile -ExecutionPolicy Bypass -File "%PS1%" -Action Check
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

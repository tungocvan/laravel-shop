@echo off
setlocal EnableExtensions

title Muasamcong - Dedicated Chrome

set "CHROME=%ProgramFiles%\Google\Chrome\Application\chrome.exe"
if not exist "%CHROME%" set "CHROME=%ProgramFiles(x86)%\Google\Chrome\Application\chrome.exe"
if not exist "%CHROME%" set "CHROME=%LocalAppData%\Google\Chrome\Application\chrome.exe"

if not exist "%CHROME%" (
    echo [LOI] Khong tim thay Google Chrome.
    echo Hay sua bien CHROME trong file nay neu Chrome nam o thu muc khac.
    pause
    exit /b 1
)

set "PROFILE=%LocalAppData%\Muasamcong-CDP-Profile"
set "PORT=9222"

start "" "%CHROME%" ^
  --remote-debugging-address=127.0.0.1 ^
  --remote-debugging-port=%PORT% ^
  --user-data-dir="%PROFILE%" ^
  "https://muasamcong.mpi.gov.vn/web/guest/profile-info"

 echo.
 echo ============================================================
 echo  CHROME RIENG CHO MUASAMCONG DA DUOC MO
 echo ============================================================
 echo  1. Dang nhap Mua sam cong tren cua so Chrome vua mo.
 echo  2. CAPTCHA/OTP neu co van thuc hien bang tay.
 echo  3. Sau khi dang nhap thanh cong, chay:
 echo     Update-Muasamcong-Session.bat
 echo.
 echo  Profile rieng: %PROFILE%
 echo  CDP chi lang nghe localhost: %PORT%
 echo ============================================================

endlocal

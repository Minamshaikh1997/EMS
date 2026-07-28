@echo off
echo ========================================
echo EMS Portal Access Fix Tool
echo ========================================
echo.

echo Step 1: Checking Apache status...
tasklist | findstr /I "httpd.exe" >nul
if %errorlevel% == 0 (
    echo Apache is running.
) else (
    echo Apache is NOT running!
    echo.
    echo Starting Apache...
    cd /d C:\xampp
    start /B apache_start.bat
    timeout /t 3 /nobreak >nul
)
echo.

echo Step 2: Checking if port 80 is in use...
netstat -ano | findstr :80 >nul
if %errorlevel% == 0 (
    echo WARNING: Port 80 is currently in use!
    echo.
    echo Processes using port 80:
    netstat -ano | findstr :80
    echo.
    echo This might be blocking Apache.
    echo Common causes: Skype, IIS, World Wide Web Publishing Service
    echo.
) else (
    echo Port 80 is available.
)
echo.

echo Step 3: Checking if port 443 is in use (HTTPS)...
netstat -ano | findstr :443 >nul
if %errorlevel% == 0 (
    echo WARNING: Port 443 is in use!
    netstat -ano | findstr :443
) else (
    echo Port 443 is available.
)
echo.

echo Step 4: Testing localhost access...
echo Opening http://localhost/ in default browser...
timeout /t 1 /nobreak >nul
start http://localhost/
echo.

echo Step 5: Testing EMS portal access...
echo Opening http://localhost/EMS/ in default browser...
timeout /t 1 /nobreak >nul
start http://localhost/EMS/
echo.

echo ========================================
echo Diagnostic Complete!
echo ========================================
echo.
echo If portal is still not opening, check these:
echo.
echo 1. XAMPP Control Panel:
echo    - Apache status should be GREEN (Running)
echo    - MySQL status should be GREEN (Running)
echo.
echo 2. Try these URLs:
echo    - http://localhost/
echo    - http://localhost/EMS/
echo    - http://127.0.0.1/EMS/
echo    - http://localhost/EMS/login.php
echo.
echo 3. Check Apache error log:
echo    C:\xampp\apache\logs\error.log
echo.
echo 4. Common issues:
echo    - Firewall blocking Apache
echo    - Antivirus blocking ports
echo    - Another program using port 80
echo    - PHP errors in code
echo.
pause
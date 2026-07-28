@echo off
chcp 65001 >nul
echo ========================================
echo MySQL Fix Tool for XAMPP
echo ========================================
echo.

echo Step 1: Checking if MySQL is installed...
if exist "C:\xampp\mysql\bin\mysqld.exe" (
    echo [OK] MySQL found at C:\xampp\mysql\
) else (
    echo [ERROR] MySQL not found at C:\xampp\mysql\
    echo Please install XAMPP properly or check the path.
    pause
    exit
)

echo.
echo Step 2: Checking MySQL data directory...
if exist "C:\xampp\mysql\data" (
    echo [OK] MySQL data directory exists
) else (
    echo [ERROR] MySQL data directory missing!
    echo Creating data directory from backup...
    if exist "C:\xampp\mysql\backup" (
        xcopy "C:\xampp\mysql\backup" "C:\xampp\mysql\data" /E /I /H
        echo [OK] Data directory restored from backup
    ) else (
        echo [ERROR] Backup directory not found!
    )
)

echo.
echo Step 3: Checking MySQL configuration...
if exist "C:\xampp\mysql\bin\my.ini" (
    echo [OK] MySQL configuration file found
) else (
    echo [ERROR] MySQL configuration file missing!
)

echo.
echo Step 4: Attempting to start MySQL...
echo Stopping any existing MySQL processes...
taskkill /F /IM mysqld.exe >nul 2>&1
timeout /t 2 /nobreak >nul

echo Starting MySQL...
start "MySQL" /B C:\xampp\mysql\bin\mysqld.exe --defaults-file="C:\xampp\mysql\bin\my.ini"

echo.
echo Waiting for MySQL to start...
timeout /t 5 /nobreak >nul

echo.
echo Step 5: Testing MySQL connection...
C:\xampp\php\php.exe -r "if(@mysqli_connect('localhost', 'root', '')) { echo '[OK] MySQL is running!'; exit(0); } else { echo '[ERROR] MySQL is not responding'; exit(1); }"

if %errorlevel% equ 0 (
    echo.
    echo ========================================
    echo SUCCESS! MySQL is now running!
    echo ========================================
    echo.
    echo You can now:
    echo 1. Open phpMyAdmin: http://localhost/phpmyadmin
    echo 2. Setup database: http://localhost/EMS/setup_database.php
    echo 3. Login: http://localhost/EMS/login.php
) else (
    echo.
    echo ========================================
    echo MySQL failed to start
    echo ========================================
    echo.
    echo Possible solutions:
    echo 1. Check XAMPP Control Panel for error messages
    echo 2. Check if port 3306 is in use by another program
    echo 3. Try running XAMPP as Administrator
    echo 4. Check Windows Event Viewer for errors
    echo.
    echo Common fixes:
    echo - Change MySQL port in my.ini from 3306 to 3307
    echo - Disable Windows Defender Firewall temporarily
    echo - Reinstall XAMPP
)

echo.
pause
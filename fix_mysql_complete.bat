@echo off
echo ========================================
echo XAMPP MySQL Startup Fix Tool
echo ========================================
echo.

echo Step 1: Checking for MySQL processes...
tasklist | findstr /I "mysqld.exe" >nul
if %errorlevel% == 0 (
    echo Found running MySQL processes. Stopping them...
    taskkill /F /IM mysqld.exe >nul 2>&1
    timeout /t 2 /nobreak >nul
) else (
    echo No running MySQL processes found.
)
echo.

echo Step 2: Checking if port 3306 is in use...
netstat -ano | findstr :3306 >nul
if %errorlevel% == 0 (
    echo WARNING: Port 3306 is currently in use!
    echo.
    echo Processes using port 3306:
    netstat -ano | findstr :3306
    echo.
    echo Attempting to stop the process...
    for /f "tokens=5" %%a in ('netstat -ano ^| findstr :3306 ^| findstr LISTENING') do (
        echo Stopping process with PID: %%a
        taskkill /F /PID %%a >nul 2>&1
    )
    timeout /t 2 /nobreak >nul
) else (
    echo Port 3306 is available.
)
echo.

echo Step 3: Stopping MySQL service if running...
net stop mysql >nul 2>&1
timeout /t 2 /nobreak >nul
echo.

echo Step 4: Renaming corrupted InnoDB log files...
cd /d C:\xampp\mysql\data
if exist ib_logfile0 (
    echo Renaming ib_logfile0 to ib_logfile0.bak
    ren ib_logfile0 ib_logfile0.bak
)
if exist ib_logfile1 (
    echo Renaming ib_logfile1 to ib_logfile1.bak
    ren ib_logfile1 ib_logfile1.bak
)
echo.

echo Step 5: Checking MySQL error log...
if exist C:\xampp\mysql\data\mysql_error.log (
    echo Last 20 lines of MySQL error log:
    powershell -Command "Get-Content C:\xampp\mysql\data\mysql_error.log -Tail 20"
) else (
    echo No error log found at C:\xampp\mysql\data\mysql_error.log
    if exist C:\xampp\mysql\data\mysql.err (
        echo Found mysql.err file instead:
        powershell -Command "Get-Content C:\xampp\mysql\data\mysql.err -Tail 20"
    )
)
echo.

echo ========================================
echo Fix completed!
echo ========================================
echo.
echo Now please:
echo 1. Open XAMPP Control Panel
echo 2. Click 'Start' button next to MySQL
echo 3. If it still doesn't start, check the error log above
echo.
pause
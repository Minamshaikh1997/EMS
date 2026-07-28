@echo off
echo ============================================
echo MySQL Root Password Reset
echo ============================================
echo.
echo This will reset MySQL root password to empty
echo.
pause
echo.
echo Step 1: Stopping MySQL...
net stop mysql
timeout /t 2 /nobreak > nul
echo.
echo Step 2: Starting MySQL without password check...
start /B mysqld --skip-grant-tables
timeout /t 3 /nobreak > nul
echo.
echo Step 3: Resetting password...
C:\xampp\mysql\bin\mysql.exe -u root -e "ALTER USER 'root'@'localhost' IDENTIFIED BY ''; FLUSH PRIVILEGES;"
echo.
echo Step 4: Stopping MySQL...
taskkill /F /IM mysqld.exe > nul 2>&1
timeout /t 2 /nobreak > nul
echo.
echo Step 5: Starting MySQL normally...
net start mysql
timeout /t 2 /nobreak > nul
echo.
echo ============================================
echo Done! Password reset to empty.
echo ============================================
echo.
echo Now try:
echo 1. http://localhost/phpmyadmin
echo 2. http://localhost/EMS
echo.
pause
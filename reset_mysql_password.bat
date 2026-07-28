@echo off
echo Stopping MySQL service...
net stop mysql
timeout /t 2 /nobreak > nul

echo Starting MySQL with skip-grant-tables...
start "MySQL Reset" /B mysqld --skip-grant-tables

timeout /t 3 /nobreak > nul

echo Resetting root password...
C:\xampp\mysql\bin\mysql.exe -u root -e "UPDATE mysql.user SET authentication_string=PASSWORD('') WHERE User='root' AND Host='localhost'; FLUSH PRIVILEGES;"

echo.
echo Password reset complete!
echo.
echo Press any key to stop MySQL and restart normally...
pause > nul

echo Stopping MySQL...
taskkill /F /IM mysqld.exe > nul 2>&1

timeout /t 2 /nobreak > nul

echo Starting MySQL normally...
net start mysql

echo.
echo Done! MySQL root password has been reset to empty (no password).
echo.
pause
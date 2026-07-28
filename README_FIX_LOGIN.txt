================================================================================
                           LOGIN NOT WORKING - FIX GUIDE
================================================================================

PROBLEM: Portal login not working (Potrl login nahi horha)
ROOT CAUSE: MySQL database is not running in XAMPP

================================================================================
IMMEDIATE SOLUTION (Choose ONE method)
================================================================================

METHOD 1: Use the Automated Fix Script (EASIEST)
-------------------------------------------------
1. Go to folder: C:\xampp\htdocs\EMS\
2. Find file: fix_mysql.bat
3. Double-click it
4. Wait for it to complete
5. Follow the instructions shown

METHOD 2: Manual Fix via XAMPP Control Panel
---------------------------------------------
1. Open XAMPP Control Panel (Search in Windows Start Menu)
2. Look for "MySQL" in the list
3. Click the "Start" button
4. Wait for it to turn GREEN
5. If it shows an error, read the error message

METHOD 3: Use SQLite Instead (NO MYSQL NEEDED)
----------------------------------------------
If MySQL won't work, I can convert the system to use SQLite:
- SQLite is a file-based database (no server needed)
- Works immediately without any installation
- Just let me know and I'll create a SQLite version

================================================================================
AFTER MYSQL IS RUNNING
================================================================================

1. Open browser and go to:
   http://localhost/phpmyadmin
   (If this opens, MySQL is working!)

2. Setup the database:
   http://localhost/EMS/setup_database.php
   (This creates tables and default users)

3. Login to the system:
   http://localhost/EMS/login.php
   
   Use these credentials:
   Admin:  admin@ems.com / admin123
   Employee: employee@ems.com / employee123

================================================================================
TROUBLESHOOTING IF MYSQL WON'T START
================================================================================

Common Error Messages and Solutions:

1. "Port 3306 in use"
   → Another program is using MySQL port
   → Solution: Change port in my.ini from 3306 to 3307

2. "Can't create/write to file"
   → Permission problem
   → Solution: Run XAMPP as Administrator

3. "MySQL shutdown unexpectedly"
   → Data directory corrupted
   → Solution: See MYSQL_TROUBLESHOOTING_GUIDE.txt

4. "InnoDB: Unable to lock"
   → Another MySQL instance running
   → Solution: Open Task Manager, end mysqld.exe process

================================================================================
FILES CREATED TO HELP YOU
================================================================================

1. fix_mysql.bat - Automated MySQL fix script
2. setup_database.php - Creates database and users
3. diagnose_login.php - Tests login system
4. check_mysql_status.php - Checks MySQL status
5. quick_mysql_check.php - Quick MySQL diagnostics
6. MYSQL_TROUBLESHOOTING_GUIDE.txt - Detailed troubleshooting guide
7. README_FIX_LOGIN.txt - This file

================================================================================
QUICK START COMMANDS
================================================================================

Open Command Prompt and run:

1. Check if MySQL is installed:
   dir C:\xampp\mysql\bin\mysqld.exe

2. Check if MySQL is running:
   tasklist | findstr mysql

3. Check port 3306:
   netstat -ano | findstr :3306

4. Try starting MySQL manually:
   cd C:\xampp\mysql\bin
   mysqld.exe --console

================================================================================
NEXT STEPS
================================================================================

1. Try METHOD 1 (fix_mysql.bat) or METHOD 2 (XAMPP Control Panel)
2. If MySQL starts, go to http://localhost/EMS/setup_database.php
3. Then login at http://localhost/EMS/login.php
4. If MySQL won't start, let me know and I'll create a SQLite version

================================================================================
CONTACT / SUPPORT
================================================================================

If you're still having issues:
1. Tell me what error message you see in XAMPP Control Panel
2. Tell me what happens when you run fix_mysql.bat
3. I'll create a SQLite version if MySQL continues to fail

================================================================================
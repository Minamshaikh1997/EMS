# EMS — Employee Management System

PHP/MySQL employee management system covering employees, attendance, leave, payroll, roles, documents and security auditing.

## Local requirements

- XAMPP with PHP 8.2 or newer
- MariaDB/MySQL
- Apache `mod_rewrite` and `.htaccess` support

The default local database name is `employee_leave_system`. Local XAMPP uses the `root` database account with an empty password. Production credentials must be provided through server environment variables:

```text
EMS_APP_ENV=production
EMS_FORCE_HTTPS=1
EMS_DB_HOST
EMS_DB_USER
EMS_DB_PASSWORD
EMS_DB_NAME
EMS_MAIL_FROM
```

Never commit real credentials. See `.env.example` for variable names; the application reads server environment variables and does not automatically load `.env` files.

## Database setup and upgrades

Apply SQL migrations from the project directory using XAMPP's MySQL client:

```powershell
Get-Content database\add_security_audit_log.sql -Raw | C:\xampp\mysql\bin\mysql.exe -u root employee_leave_system
Get-Content database\2026_08_03_core_schema_alignment.sql -Raw | C:\xampp\mysql\bin\mysql.exe -u root employee_leave_system
Get-Content database\2026_08_03_login_attempts.sql -Raw | C:\xampp\mysql\bin\mysql.exe -u root employee_leave_system
Get-Content database\2026_08_03_salary_integrity.sql -Raw | C:\xampp\mysql\bin\mysql.exe -u root employee_leave_system
Get-Content database\2026_08_03_role_permission_schema.sql -Raw | C:\xampp\mysql\bin\mysql.exe -u root employee_leave_system
Get-Content database\2026_08_04_attendance_adjustment_schema.sql -Raw | C:\xampp\mysql\bin\mysql.exe -u root employee_leave_system
Get-Content database\2026_08_05_employee_requisitions.sql -Raw | C:\xampp\mysql\bin\mysql.exe -u root employee_leave_system
Get-Content database\2026_08_05_attendance_status_requests.sql -Raw | C:\xampp\mysql\bin\mysql.exe -u root employee_leave_system
Get-Content database\2026_08_05_attendance_status_lock.sql -Raw | C:\xampp\mysql\bin\mysql.exe -u root employee_leave_system
Get-Content database\2026_08_05_attendance_day_statuses.sql -Raw | C:\xampp\mysql\bin\mysql.exe -u root employee_leave_system
Get-Content database\2026_08_09_requisition_role_permissions.sql -Raw | C:\xampp\mysql\bin\mysql.exe -u root employee_leave_system
Get-Content database\2026_08_09_performance_mis_schema.sql -Raw | C:\xampp\mysql\bin\mysql.exe -u root employee_leave_system
```

The alignment migration is safe to run repeatedly and preserves existing employee records.

## Run locally

Start Apache and MySQL from XAMPP, then open:

```text
http://localhost/EMS/
```

## Health check

Run the read-only automated database and security check:

```powershell
C:\xampp\php\php.exe tests\health_check.php
C:\xampp\php\php.exe tests\auth_rate_limit_test.php
C:\xampp\php\php.exe tests\workflow_integrity_test.php
C:\xampp\php\php.exe tests\deployment_readiness.php
```

The command exits with code `0` when all checks pass and `1` when a problem is found. It checks required tables/columns, password hashes, duplicate records, orphan records and critical indexes.

## Backup

Create a database dump, uploads archive and SHA-256 manifest:

```powershell
powershell -ExecutionPolicy Bypass -File tools\backup.ps1
```

Backups are written to `storage\backups`, excluded from Git and denied by Apache. Copy completed backups to encrypted off-site storage. The backup tool never deletes old backups and does not perform restores.

## Security notes

- Login sessions use HttpOnly/SameSite cookies and regenerate after authentication.
- Sensitive forms use CSRF tokens and role checks.
- Uploaded files are MIME-validated and executable extensions are blocked.
- Sensitive changes are recorded in `security_audit_log`.
- Setup, debug, reset and legacy bulk-delete pages are denied by Apache.
- Security Audit is available to Super Admin and Admin users from the dashboard.

## Production checklist

1. Rotate any credentials that were previously stored in source code.
2. Configure the four `EMS_DB_*` environment variables.
3. Set `EMS_APP_ENV=production` and `EMS_FORCE_HTTPS=1`.
4. Apply all database migrations in the documented order.
5. Serve the application over HTTPS.
6. Confirm Apache honors the included `.htaccess` files.
7. Confirm the PHP error log is writable and not web-accessible.
8. Schedule encrypted database and upload backups, and test a restore on a separate database.
9. Run `tests\health_check.php` after each deployment.

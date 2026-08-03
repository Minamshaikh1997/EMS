<?php
if(PHP_SAPI!=='cli'){session_start();if(!isset($_SESSION['admin'])){http_response_code(403);exit('Admin login required.');}}
include dirname(__DIR__).'/config/db.php';
$sqls=[
"ALTER TABLE attendance ADD COLUMN IF NOT EXISTS check_in_at DATETIME NULL, ADD COLUMN IF NOT EXISTS check_out_at DATETIME NULL, ADD COLUMN IF NOT EXISTS work_minutes INT UNSIGNED DEFAULT 0, ADD COLUMN IF NOT EXISTS late_minutes INT UNSIGNED DEFAULT 0, ADD COLUMN IF NOT EXISTS early_out_minutes INT UNSIGNED DEFAULT 0, ADD COLUMN IF NOT EXISTS overtime_minutes INT UNSIGNED DEFAULT 0, ADD COLUMN IF NOT EXISTS is_locked TINYINT(1) DEFAULT 0, ADD COLUMN IF NOT EXISTS source VARCHAR(30) DEFAULT 'Web'",
"CREATE TABLE IF NOT EXISTS attendance_policy (id TINYINT PRIMARY KEY, grace_minutes SMALLINT UNSIGNED DEFAULT 10, half_day_minutes SMALLINT UNSIGNED DEFAULT 240, full_day_minutes SMALLINT UNSIGNED DEFAULT 480, overtime_after_minutes SMALLINT UNSIGNED DEFAULT 480, allow_early_check_in_minutes SMALLINT UNSIGNED DEFAULT 120, updated_by INT NULL, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)",
"CREATE TABLE IF NOT EXISTS attendance_period_locks (period_month CHAR(7) PRIMARY KEY, is_locked TINYINT(1) DEFAULT 1, locked_by INT NULL, locked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, unlocked_at DATETIME NULL, notes VARCHAR(255) NULL)",
"INSERT INTO attendance_policy(id) VALUES(1) ON DUPLICATE KEY UPDATE id=id"
];$errors=[];foreach($sqls as $sql){try{$conn->query($sql);}catch(Throwable $e){$errors[]=$e->getMessage();}}
if(PHP_SAPI==='cli'){echo $errors?'Attendance upgrade warnings: '.implode('; ',$errors).PHP_EOL:"Attendance upgrade completed.\n";exit($errors?1:0);}header('Location: ../admin/attendance_policy.php');

# Employee Rights Management System

## Overview
Yeh system admin ko har employee ke liye alag-alag feature access control karne ki permission deta hai. Admin kisi bhi employee ko specific features enable/disable kar sakta hai.

## Features

### 1. **Employee Rights Management Page** (`admin/employee_rights_management.php`)
- Sabhi employees ka rights overview ek jagah
- Search and filter by department
- Click karke employee ke rights manage kar sakte hain
- Modal popup mein saare toggles hain
- Real-time statistics (kitne employees ke payroll on/off hai)

### 2. **Rights Available for Control**
Har employee ke liye yeh 7 main features control kar sakte hain:

1. **View Payroll/Salary** (`can_view_payroll`)
   - Employee apni salary slips dekh sakta hai
   - Payroll details access kar sakta hai

2. **Apply for Leave** (`can_apply_leave`)
   - Naye leave applications submit kar sakta hai
   - Leave history dekh sakta hai

3. **View Attendance** (`can_view_attendance`)
   - Attendance mark kar sakta hai
   - Attendance history dekh sakta hai

4. **Submit Adjustments** (`can_submit_adjustment`)
   - Attendance adjustments request kar sakta hai
   - Apne adjustments track kar sakta hai

5. **Edit Profile** (`can_edit_profile`)
   - Apna profile update kar sakta hai
   - Photo upload kar sakta hai

6. **View Reports** (`can_view_reports`)
   - Reports and analytics dekh sakta hai

7. **Change Password** (`can_change_password`)
   - Apna password change kar sakta hai

## Installation & Setup

### Step 1: Database Setup
Browser mein yeh URL open karein:
```
http://localhost/EMS/database/run_employee_rights_setup.php
```

Yeh script automatically:
- `employees` table mein 7 naye columns add kar dega
- Har column ka default value `1` (enabled) hoga
- Purane employees ke liye saare rights enabled rahenge

### Step 2: Access the Management Page
1. Admin login karein
2. Sidebar mein **"Employee Rights"** link par click karein
   - Ya directly: `admin/employee_rights_management.php`
3. Ya phir kisi employee ko edit karte waqt rights manage kar sakte hain

## How to Use

### Method 1: From Employee List Page
1. **Admin** → **Employees** → **Employee Rights** par click karein
2. Search karke employee dhoondein
3. Employee card par **"Manage Rights"** button par click karein
4. Modal khulega, saare rights ke toggles dikhenge
5. Jitne features ON/Off karna hai, toggle karein
6. **Save Rights** button par click karein

### Method 2: From Edit Employee Page
1. **Admin** → **Employees** → kisi employee ke **Edit** button par click
2. Page ke end mein **"Employee Rights & Permissions"** section hai
3. Wahan saare toggles hain
4. Update karte waqt rights bhi save ho jayenge

## Employee Experience

### Dashboard mein kya dikhega:
- **ON** rights: Sidebar mein wo menu items dikhenge
- **OFF** rights: Wo menu items hide ho jayenge
- Employee apne dashboard par sirf wo features dekh payega jo uske rights mein enabled hain

### Example:
Agar employee ke `can_view_payroll` = 0 (OFF) hai:
- Sidebar mein "My Payroll" link nahi dikhega
- Direct URL se access karne par bhi redirect ho jayega dashboard par

## Database Structure

### Employees Table - New Columns:
```sql
can_view_payroll TINYINT(1) DEFAULT 1
can_apply_leave TINYINT(1) DEFAULT 1
can_view_attendance TINYINT(1) DEFAULT 1
can_submit_adjustment TINYINT(1) DEFAULT 1
can_edit_profile TINYINT(1) DEFAULT 1
can_view_reports TINYINT(1) DEFAULT 1
can_change_password TINYINT(1) DEFAULT 1
```

## Files Modified/Created

### New Files:
1. `config/employee_rights.php` - Helper functions for checking rights
2. `admin/employee_rights_management.php` - Main management page
3. `employee/my_payroll.php` - Employee payroll view page
4. `database/run_employee_rights_setup.php` - Database setup script
5. `database/add_payroll_visibility_column.php` - Initial migration script

### Modified Files:
1. `admin/edit_employee.php` - Added rights management section
2. `admin/employee.php` - Added "Employee Rights" link in sidebar
3. `employee/dashboard.php` - Added conditional menu items based on rights

## Helper Functions

### `hasEmployeeRight($conn, $employee_id, $feature)`
Check if employee has specific right
```php
if (hasEmployeeRight($conn, $employee_id, 'can_view_payroll')) {
    // Employee can view payroll
}
```

### `requireEmployeeRight($conn, $employee_id, $feature, $redirect_url)`
Redirect if employee doesn't have right
```php
requireEmployeeRight($conn, $employee_id, 'can_view_payroll', 'dashboard.php');
```

### `getEmployeeRights($conn, $employee_id)`
Get all rights for an employee
```php
$rights = getEmployeeRights($conn, $employee_id);
// Returns array: ['can_view_payroll' => 1, 'can_apply_leave' => 0, ...]
```

### `updateEmployeeRight($conn, $employee_id, $feature, $value)`
Update specific right
```php
updateEmployeeRight($conn, $employee_id, 'can_view_payroll', 0);
```

## Security Notes

1. **Admin Only**: Only admin/super admin can manage rights
2. **Database Level**: Rights stored in database, not just session
3. **Page Protection**: Individual pages check rights before loading
4. **Default Allow**: All new employees get all rights by default
5. **Super Admin**: Super admin always has all rights (cannot be restricted)

## Testing Checklist

- [ ] Run `database/run_employee_rights_setup.php`
- [ ] Login as admin
- [ ] Go to Employee Rights Management page
- [ ] Toggle some rights for an employee
- [ ] Save and verify changes
- [ ] Login as that employee
- [ ] Check if disabled features are hidden
- [ ] Try accessing disabled feature directly via URL
- [ ] Verify redirect to dashboard

## Troubleshooting

### Columns not found error:
- Run the setup script again: `database/run_employee_rights_setup.php`

### Rights not updating:
- Check browser console for errors
- Verify database connection
- Check if ALTER TABLE queries are executing

### Employee still seeing disabled features:
- Clear browser cache
- Check if rights are saved in database
- Verify `getEmployeeRights()` function is working

## Support
For issues or questions, contact the development team.

---

**Version**: 1.0  
**Last Updated**: 2026  
**Compatible with**: EMS v2.0+
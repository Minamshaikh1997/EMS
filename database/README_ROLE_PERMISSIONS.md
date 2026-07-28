# Role & Permission Management System

## Overview
A complete Role-Based Access Control (RBAC) system for the Employee Management System (EMS) with granular permissions, audit logging, and professional UI.

## Features
✅ 8 User Roles with hierarchy levels
✅ 6 Permission types (View, Create, Edit, Delete, Approve, Export)
✅ 11 Modules with granular control
✅ Professional Bootstrap 5 UI
✅ Permission matrix with checkboxes
✅ CSRF protection
✅ Audit logging
✅ Access denied page
✅ Super Admin bypass
✅ Transaction-based permission saving
✅ Responsive design

## Database Structure

### Tables Created
1. **roles** - Stores role information with hierarchy levels
2. **permissions** - Stores permission types
3. **role_permissions** - Pivot table linking roles to permissions per module
4. **permission_logs** - Audit trail for permission access

### Roles (8 Total)
| Role | Level | Description |
|------|-------|-------------|
| Super Admin | 1 | Full system access |
| Admin | 2 | Managing Director level |
| VP | 3 | Vice President |
| Operations Manager | 3 | Operations oversight |
| WFM | 3 | Workforce Management |
| Supervisor | 4 | Team supervision |
| Team Lead | 5 | Team leadership |
| Employee | 6 | Basic access |

### Permissions (6 Types)
- **View** - Read/View data
- **Create** - Add new records
- **Edit** - Modify existing records
- **Delete** - Remove records
- **Approve** - Approve requests
- **Export** - Export to Excel/PDF

### Modules (11 Total)
1. Dashboard
2. Employee Management
3. Attendance
4. Leave Management
5. Payroll
6. Department
7. Reports
8. Notifications
9. Settings
10. User Management
11. Role & Permission

## Installation

### Step 1: Import Database
```bash
# Navigate to database folder
cd database/

# Import SQL file using phpMyAdmin or MySQL command line
mysql -u root -p employee_leave_system < role_permissions.sql
```

Or use phpMyAdmin:
1. Open phpMyAdmin
2. Select `employee_leave_system` database
3. Go to Import tab
4. Choose `database/role_permissions.sql`
5. Click "Go"

### Step 2: Verify Files
Ensure these files exist:
```
config/permissions.php          ✅ Permission functions
admin/role_permissions.php      ✅ Permission management UI
admin/access_denied.php         ✅ Access denied page
database/role_permissions.sql   ✅ Database schema
```

### Step 3: Test Access
1. Login as Super Admin
2. Navigate to: `http://localhost/EMS/admin/role_permissions.php`
3. You should see the permission matrix

## Usage

### For Super Admin

#### Managing Permissions
1. Login as Super Admin
2. Go to **Admin Panel → Role & Permission Management**
3. You'll see a matrix with:
   - **Rows**: Modules (grouped by category)
   - **Columns**: Roles
   - **Checkboxes**: Permissions (View, Create, Edit, Delete, Approve, Export)
4. Tick checkboxes to grant permissions
5. Click **"Save Permissions"**
6. Changes take effect immediately

#### Quick Actions
- **Select All**: Grants all permissions to all roles (use with caution)
- **Clear All**: Removes all permissions from all roles
- **Reset Changes**: Reverts form to last saved state

### For Developers: Integrating Permissions

#### Method 1: Simple Permission Check
```php
<?php
include("admincheck_role.php");
include("../config/db.php");
include("../config/permissions.php");

// Check if user has permission
if (hasPermission($conn, 'employee_management', 'view')) {
    // User can view employees - show content
    echo "Welcome to Employee Management";
} else {
    // User cannot view - show message or redirect
    echo "You don't have permission to view this module.";
}
?>
```

#### Method 2: Require Permission (Auto Redirect)
```php
<?php
include("admincheck_role.php");
include("../config/db.php");
include("../config/permissions.php");

// This will automatically redirect to access_denied.php if no permission
requirePermission($conn, 'employee_management', 'view');

// If code reaches here, user has permission
// Show protected content
?>
```

#### Method 3: Check Multiple Permissions
```php
<?php
// Check if user has ANY of these permissions
if (hasAnyPermission($conn, 'employee_management', ['view', 'create', 'edit'])) {
    // User can view, create, OR edit
}

// Check if user has ALL of these permissions
if (hasAllPermissions($conn, 'employee_management', ['view', 'create', 'edit', 'delete'])) {
    // User can do everything
}
?>
```

#### Method 4: Get User Permissions
```php
<?php
// Get all permissions for current user on a module
$permissions = getUserPermissions($conn, 'employee_management');

// Returns array like: ['view', 'create', 'edit']
foreach ($permissions as $perm) {
    echo "<button class='btn btn-$perm'>$perm</button>";
}

// Check specific permission
if (in_array('delete', $permissions)) {
    echo "<a href='delete.php' class='btn btn-danger'>Delete</a>";
}
?>
```

#### Method 5: Conditional UI Elements
```php
<?php
// Show/hide buttons based on permissions
if (hasPermission($conn, 'employee_management', 'create')) {
    echo "<a href='add_employee.php' class='btn btn-success'>
            <i class='fa fa-plus'></i> Add Employee
          </a>";
}

if (hasPermission($conn, 'employee_management', 'edit')) {
    echo "<a href='edit_employee.php?id=123' class='btn btn-primary'>
            <i class='fa fa-edit'></i> Edit
          </a>";
}

if (hasPermission($conn, 'employee_management', 'delete')) {
    echo "<a href='delete_employee.php?id=123' class='btn btn-danger'>
            <i class='fa fa-trash'></i> Delete
          </a>";
}

if (hasPermission($conn, 'employee_management', 'export')) {
    echo "<a href='export_employees.php' class='btn btn-info'>
            <i class='fa fa-download'></i> Export
          </a>";
}
?>
```

### Integrating into Existing Pages

#### Example: Protecting admin/dashboard.php
```php
<?php
// At the top of dashboard.php, after admincheck_role.php
include("admincheck_role.php");
include("../config/db.php");
include("../config/permissions.php");

// Require view permission for dashboard
requirePermission($conn, 'dashboard', 'view');
?>
```

#### Example: Protecting admin/employee.php
```php
<?php
include("admincheck_role.php");
include("../config/db.php");
include("../config/permissions.php");

// Require view permission
requirePermission($conn, 'employee_management', 'view');

// Now load the page content
$employees = mysqli_query($conn, "SELECT * FROM employees");
?>
```

#### Example: Protecting admin/add_employee.php
```php
<?php
include("admincheck_role.php");
include("../config/db.php");
include("../config/permissions.php");

// Require create permission
requirePermission($conn, 'employee_management', 'create');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Only users with 'create' permission can reach here
    $name = $_POST['name'];
    // ... save employee
}
?>
```

#### Example: Protecting admin/edit_employee.php
```php
<?php
include("admincheck_role.php");
include("../config/db.php");
include("../config/permissions.php");

// Require edit permission
requirePermission($conn, 'employee_management', 'edit');

$id = $_GET['id'] ?? 0;
// ... edit logic
?>
```

## Security Features

### 1. CSRF Protection
All forms include CSRF tokens:
```php
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
```

### 2. Super Admin Bypass
Super Admin automatically has all permissions:
```php
if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'Super Admin') {
    return true;
}
```

### 3. Prepared Statements
All database queries use prepared statements to prevent SQL injection.

### 4. Audit Logging
All permission checks are logged:
```php
logPermissionAccess($conn, 'module_name', 'permission', true/false);
```

### 5. Session Validation
All pages validate admin session before checking permissions.

## Module Mapping

Map your existing pages to modules:

| Page | Module Name | Usage Example |
|------|-------------|---------------|
| dashboard.php | `dashboard` | `requirePermission($conn, 'dashboard', 'view')` |
| employee.php | `employee_management` | `requirePermission($conn, 'employee_management', 'view')` |
| add_employee.php | `employee_management` | `requirePermission($conn, 'employee_management', 'create')` |
| edit_employee.php | `employee_management` | `requirePermission($conn, 'employee_management', 'edit')` |
| delete_employee.php | `employee_management` | `requirePermission($conn, 'employee_management', 'delete')` |
| attendance_report.php | `attendance` | `requirePermission($conn, 'attendance', 'view')` |
| manage_shifts.php | `attendance` | `requirePermission($conn, 'attendance', 'edit')` |
| leave_requests.php | `leave_management` | `requirePermission($conn, 'leave_management', 'view')` |
| approve_leave.php | `leave_management` | `requirePermission($conn, 'leave_management', 'approve')` |
| generate_payroll.php | `payroll` | `requirePermission($conn, 'payroll', 'create')` |
| reports.php | `reports` | `requirePermission($conn, 'reports', 'view')` |
| export_excel.php | `reports` | `requirePermission($conn, 'reports', 'export')` |
| add_notice.php | `notifications` | `requirePermission($conn, 'notifications', 'create')` |
| settings.php | `settings` | `requirePermission($conn, 'settings', 'edit')` |
| role_permissions.php | `role_permission` | `requirePermission($conn, 'role_permission', 'edit')` |

## API Reference

### Core Functions

#### hasPermission($conn, $module, $permission)
Check if current user has specific permission.
```php
bool hasPermission(mysqli $conn, string $module, string $permission = 'view')
```
**Returns:** `true` if user has permission, `false` otherwise

#### requirePermission($conn, $module, $permission)
Require permission or redirect to access denied.
```php
void requirePermission(mysqli $conn, string $module, string $permission = 'view')
```
**Returns:** void (exits on denial)

#### hasAnyPermission($conn, $module, $permissions)
Check if user has any of the specified permissions.
```php
bool hasAnyPermission(mysqli $conn, string $module, array $permissions = ['view'])
```

#### hasAllPermissions($conn, $module, $permissions)
Check if user has all specified permissions.
```php
bool hasAllPermissions(mysqli $conn, string $module, array $permissions = ['view'])
```

#### getUserPermissions($conn, $module)
Get all permissions for current user on a module.
```php
array getUserPermissions(mysqli $conn, string $module)
```
**Returns:** Array of permission slugs

#### getAllRoles($conn, $active_only)
Get all roles from database.
```php
array getAllRoles(mysqli $conn, bool $active_only = true)
```

#### getAllPermissions($conn)
Get all permissions from database.
```php
array getAllPermissions(mysqli $conn)
```

#### getAllModules()
Get all available modules.
```php
array getAllModules()
```

#### isSuperAdmin()
Check if current user is Super Admin.
```php
bool isSuperAdmin()
```

#### isAdminOrHigher()
Check if current user is Admin or Super Admin.
```php
bool isAdminOrHigher()
```

## Troubleshooting

### Issue: "Access Denied" for Super Admin
**Solution:** Ensure Super Admin role name is exactly "Super Admin" (case-sensitive).

### Issue: Permissions not saving
**Solution:** Check database foreign key constraints. Ensure roles and permissions tables have data.

### Issue: CSRF token error
**Solution:** Clear browser cookies and restart session.

### Issue: Page loads slowly
**Solution:** Add indexes to role_permissions table:
```sql
CREATE INDEX idx_role_module ON role_permissions(role_id, module_name);
```

## Best Practices

1. **Always include permission checks at the top of protected pages**
2. **Use `requirePermission()` for critical pages** (auto-redirects)
3. **Use `hasPermission()` for conditional UI elements**
4. **Never rely solely on UI hiding** - always check permissions server-side
5. **Log important permission checks** for audit trail
6. **Test with different roles** to ensure proper access control
7. **Document module mappings** for future developers

## Performance Optimization

### Caching Permissions
For high-traffic systems, cache permissions in session:
```php
// In permissions.php - add caching
if (!isset($_SESSION['user_permissions'])) {
    $_SESSION['user_permissions'] = [];
}

function getCachedPermissions($conn, $module) {
    $cache_key = $_SESSION['admin_id'] . '_' . $module;
    
    if (!isset($_SESSION['user_permissions'][$cache_key])) {
        $_SESSION['user_permissions'][$cache_key] = getUserPermissions($conn, $module);
    }
    
    return $_SESSION['user_permissions'][$cache_key];
}
```

### Clear Cache on Permission Update
```php
// After saving permissions
unset($_SESSION['user_permissions']);
```

## Support

For issues or questions:
1. Check this documentation
2. Review code comments in `config/permissions.php`
3. Test with Super Admin account first
4. Check browser console for JavaScript errors
5. Verify database tables were created correctly

## License
Part of Employee Management System (EMS) by Minam Shaikh

## Version
1.0.0 - Initial Release
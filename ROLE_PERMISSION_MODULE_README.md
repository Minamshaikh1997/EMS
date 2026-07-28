# Role & Permission Management Module
## Employee Management System (EMS)

---

## 📋 Table of Contents
1. [Overview](#overview)
2. [Features](#features)
3. [Installation](#installation)
4. [File Structure](#file-structure)
5. [Usage Guide](#usage-guide)
6. [Integration](#integration)
7. [API Reference](#api-reference)
8. [Security](#security)
9. [Troubleshooting](#troubleshooting)
10. [Support](#support)

---

## 🎯 Overview

A complete, production-ready Role-Based Access Control (RBAC) system for the Employee Management System. This module provides granular permission management with a professional Bootstrap 5 UI, audit logging, and secure PHP implementation.

### What's Included
- ✅ **8 User Roles** with hierarchy levels
- ✅ **6 Permission Types** (View, Create, Edit, Delete, Approve, Export)
- ✅ **11 Modules** with granular access control
- ✅ **Professional Bootstrap 5 UI** with permission matrix
- ✅ **CSRF Protection** for all forms
- ✅ **Audit Logging** for security tracking
- ✅ **Access Denied Page** with user-friendly messaging
- ✅ **Super Admin Bypass** for unrestricted access
- ✅ **Transaction-based** permission saving
- ✅ **Responsive Design** for all devices
- ✅ **Interactive Test Page** for verification
- ✅ **One-click Installer** for easy setup

---

## ✨ Features

### 1. Role Management
- **8 Pre-configured Roles:**
  - Super Admin (Level 1) - Full system access
  - Admin (Level 2) - Managing Director
  - VP (Level 3) - Vice President
  - Operations Manager (Level 3)
  - WFM (Level 3) - Workforce Management
  - Supervisor (Level 4)
  - Team Lead (Level 5)
  - Employee (Level 6)

### 2. Permission Types
- **View** - Read/View data
- **Create** - Add new records
- **Edit** - Modify existing records
- **Delete** - Remove records
- **Approve** - Approve requests
- **Export** - Export to Excel/PDF

### 3. Modules Covered
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

### 4. Security Features
- CSRF token protection
- Prepared statements (SQL injection prevention)
- Session validation
- Super Admin automatic bypass
- Audit logging
- Permission-based redirects

### 5. UI Features
- Permission matrix with checkboxes
- Color-coded permissions
- Role hierarchy badges
- Statistics dashboard
- Select All / Clear All options
- Unsaved changes warning
- Responsive design
- Professional Bootstrap 5 styling

---

## 🚀 Installation

### Method 1: Using the Installer (Recommended)

1. **Navigate to the installer:**
   ```
   http://localhost/EMS/database/install_permissions.php
   ```

2. **Click "Install Now" button**

3. **Wait for installation to complete**

4. **Follow the next steps guide**

### Method 2: Manual Installation

1. **Import the SQL file:**
   ```bash
   # Using MySQL command line
   mysql -u root -p employee_leave_system < database/role_permissions.sql
   
   # Or using phpMyAdmin:
   # 1. Open phpMyAdmin
   # 2. Select database: employee_leave_system
   # 3. Go to Import tab
   # 4. Choose file: database/role_permissions.sql
   # 5. Click "Go"
   ```

2. **Verify files exist:**
   ```
   config/permissions.php
   admin/role_permissions.php
   admin/access_denied.php
   ```

3. **Test the installation:**
   ```
   http://localhost/EMS/admin/test_permissions.php
   ```

---

## 📁 File Structure

```
EMS/
├── config/
│   ├── db.php                          # Database connection
│   ├── permissions.php                 # Permission functions ⭐
│   └── integration_example.php         # Integration examples
│
├── admin/
│   ├── admincheck_role.php             # Role checking (existing)
│   ├── role_permissions.php            # Permission management UI ⭐
│   ├── access_denied.php               # Access denied page ⭐
│   ├── test_permissions.php            # Test page ⭐
│   ├── dashboard.php                   # Example integration
│   ├── employee.php                    # Example integration
│   └── [other admin pages...]
│
├── database/
│   ├── role_permissions.sql            # Database schema ⭐
│   ├── install_permissions.php         # Installer script ⭐
│   └── README_ROLE_PERMISSIONS.md      # Detailed documentation
│
└── ROLE_PERMISSION_MODULE_README.md    # This file
```

**⭐ = New files created by this module**

---

## 📖 Usage Guide

### For Super Admin

#### Managing Permissions

1. **Login as Super Admin**

2. **Navigate to Role & Permission Management:**
   ```
   http://localhost/EMS/admin/role_permissions.php
   ```

3. **You'll see a permission matrix:**
   - **Rows:** Modules (grouped by category)
   - **Columns:** Roles
   - **Checkboxes:** Permissions (View, Create, Edit, Delete, Approve, Export)

4. **Grant permissions:**
   - Tick checkboxes to grant permissions
   - Use "Select All" for bulk granting (use with caution)
   - Use "Clear All" to remove all permissions

5. **Save changes:**
   - Click "Save Permissions"
   - Changes take effect immediately
   - Activity is logged for audit

#### Quick Actions
- **Select All** - Grants all permissions to all roles
- **Clear All** - Removes all permissions from all roles
- **Reset Changes** - Reverts form to last saved state

### For Developers

#### Basic Integration

Add these 3 lines at the TOP of any protected page:

```php
<?php
include("admincheck_role.php");
include("../config/db.php");
include("../config/permissions.php");

// Require permission (redirects if no access)
requirePermission($conn, 'module_name', 'view');
?>
```

#### Example: Protecting a Page

```php
<?php
// admin/employee.php
session_start();
include("admincheck_role.php");
include("../config/db.php");
include("../config/permissions.php");

// Require view permission for employee management
requirePermission($conn, 'employee_management', 'view');

// If user reaches here, they have permission
// Rest of your page code...
?>
```

#### Conditional UI Elements

```php
<?php
// Show Add button only if user has 'create' permission
if (hasPermission($conn, 'employee_management', 'create')) {
    echo '<a href="add_employee.php" class="btn btn-success">Add Employee</a>';
}

// Show Delete button only if user has 'delete' permission
if (hasPermission($conn, 'employee_management', 'delete')) {
    echo '<a href="delete_employee.php?id=123" class="btn btn-danger">Delete</a>';
}

// Show Export button only if user has 'export' permission
if (hasPermission($conn, 'employee_management', 'export')) {
    echo '<a href="export_employees.php" class="btn btn-info">Export</a>';
}
?>
```

---

## 🔌 Integration

### Step 1: Include Permission Files

At the top of each protected page, add:

```php
<?php
session_start();
include("admincheck_role.php");
include("../config/db.php");
include("../config/permissions.php");
?>
```

### Step 2: Add Permission Check

After including files, add permission check:

```php
<?php
// For pages that view data
requirePermission($conn, 'module_name', 'view');

// For pages that create data
requirePermission($conn, 'module_name', 'create');

// For pages that edit data
requirePermission($conn, 'module_name', 'edit');

// For pages that delete data
requirePermission($conn, 'module_name', 'delete');

// For pages that approve requests
requirePermission($conn, 'module_name', 'approve');

// For pages that export data
requirePermission($conn, 'module_name', 'export');
?>
```

### Step 3: Map Your Pages to Modules

| Your Page | Module Name | Permission Required |
|-----------|-------------|---------------------|
| dashboard.php | `dashboard` | view |
| employee.php | `employee_management` | view |
| add_employee.php | `employee_management` | create |
| edit_employee.php | `employee_management` | edit |
| delete_employee.php | `employee_management` | delete |
| attendance_report.php | `attendance` | view |
| manage_shifts.php | `attendance` | edit |
| leave_requests.php | `leave_management` | view |
| approve_leave.php | `leave_management` | approve |
| generate_payroll.php | `payroll` | create |
| reports.php | `reports` | view |
| export_excel.php | `reports` | export |
| add_notice.php | `notifications` | create |
| settings.php | `settings` | edit |
| role_permissions.php | `role_permission` | edit |

### Step 4: Add Conditional Buttons (Optional)

```php
<?php
// Show/hide action buttons based on permissions
if (hasPermission($conn, 'employee_management', 'create')): ?>
    <a href="add_employee.php" class="btn btn-success">
        <i class="fa fa-plus"></i> Add Employee
    </a>
<?php endif; ?>

<?php if (hasPermission($conn, 'employee_management', 'edit')): ?>
    <a href="edit_employee.php" class="btn btn-primary">
        <i class="fa fa-edit"></i> Edit
    </a>
<?php endif; ?>

<?php if (hasPermission($conn, 'employee_management', 'delete')): ?>
    <a href="delete_employee.php" class="btn btn-danger">
        <i class="fa fa-trash"></i> Delete
    </a>
<?php endif; ?>

<?php if (hasPermission($conn, 'employee_management', 'export')): ?>
    <a href="export_employees.php" class="btn btn-info">
        <i class="fa fa-download"></i> Export
    </a>
<?php endif; ?>
?>
```

---

## 📚 API Reference

### Core Functions

#### `hasPermission($conn, $module, $permission)`
Check if current user has specific permission.

```php
bool hasPermission(mysqli $conn, string $module, string $permission = 'view')
```

**Parameters:**
- `$conn` - Database connection
- `$module` - Module name (e.g., 'employee_management')
- `$permission` - Permission slug (default: 'view')

**Returns:** `true` if user has permission, `false` otherwise

**Example:**
```php
if (hasPermission($conn, 'employee_management', 'create')) {
    // User can create employees
}
```

---

#### `requirePermission($conn, $module, $permission)`
Require permission or redirect to access denied page.

```php
void requirePermission(mysqli $conn, string $module, string $permission = 'view')
```

**Parameters:**
- `$conn` - Database connection
- `$module` - Module name
- `$permission` - Permission slug (default: 'view')

**Returns:** void (exits on denial)

**Example:**
```php
requirePermission($conn, 'employee_management', 'view');
// If user reaches here, they have permission
```

---

#### `hasAnyPermission($conn, $module, $permissions)`
Check if user has ANY of the specified permissions.

```php
bool hasAnyPermission(mysqli $conn, string $module, array $permissions = ['view'])
```

**Example:**
```php
if (hasAnyPermission($conn, 'employee_management', ['create', 'edit', 'delete'])) {
    // User can create, edit, OR delete
}
```

---

#### `hasAllPermissions($conn, $module, $permissions)`
Check if user has ALL specified permissions.

```php
bool hasAllPermissions(mysqli $conn, string $module, array $permissions = ['view'])
```

**Example:**
```php
if (hasAllPermissions($conn, 'employee_management', ['view', 'edit', 'delete'])) {
    // User can view, edit, AND delete
}
```

---

#### `getUserPermissions($conn, $module)`
Get all permissions for current user on a module.

```php
array getUserPermissions(mysqli $conn, string $module)
```

**Returns:** Array of permission slugs

**Example:**
```php
$permissions = getUserPermissions($conn, 'employee_management');
// Returns: ['view', 'create', 'edit']

foreach ($permissions as $perm) {
    echo "<span class='badge'>$perm</span>";
}
```

---

#### `isSuperAdmin()`
Check if current user is Super Admin.

```php
bool isSuperAdmin()
```

**Example:**
```php
if (isSuperAdmin()) {
    // Show Super Admin features
}
```

---

#### `getCurrentUserRole($conn)`
Get current user's role name.

```php
string getCurrentUserRole(mysqli $conn)
```

**Example:**
```php
$role = getCurrentUserRole($conn);
echo "Your role: $role";
```

---

## 🔒 Security

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
All permission checks are logged in `permission_logs` table.

### 5. Session Validation
All pages validate admin session before checking permissions.

---

## 🧪 Testing

### Test Page
Access the test page to verify permissions:
```
http://localhost/EMS/admin/test_permissions.php
```

The test page shows:
- Current user information
- Role-wise permission statistics
- Interactive permission tester
- Your permissions by module

### Test with Different Roles

1. **Create test users** with different roles
2. **Login as each user**
3. **Verify access** to different pages
4. **Check permission matrix** in role_permissions.php
5. **Review audit logs** if needed

---

## 🛠️ Troubleshooting

### Issue: "Access Denied" for Super Admin
**Solution:** Ensure Super Admin role name is exactly "Super Admin" (case-sensitive).

### Issue: Permissions not saving
**Solution:** 
1. Check database foreign key constraints
2. Ensure roles and permissions tables have data
3. Check PHP error logs

### Issue: CSRF token error
**Solution:** Clear browser cookies and restart session.

### Issue: Page loads slowly
**Solution:** Add indexes to role_permissions table:
```sql
CREATE INDEX idx_role_module ON role_permissions(role_id, module_name);
```

### Issue: Tables not created
**Solution:** Run the installer again:
```
http://localhost/EMS/database/install_permissions.php
```

---

## 📊 Database Schema

### Tables

#### 1. roles
```sql
- id (PK)
- role_name (VARCHAR 100, UNIQUE)
- role_slug (VARCHAR 100, UNIQUE)
- description (TEXT)
- hierarchy_level (INT)
- is_active (TINYINT)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

#### 2. permissions
```sql
- id (PK)
- permission_name (VARCHAR 100, UNIQUE)
- permission_slug (VARCHAR 100, UNIQUE)
- description (TEXT)
- created_at (TIMESTAMP)
```

#### 3. role_permissions
```sql
- id (PK)
- role_id (FK → roles.id)
- permission_id (FK → permissions.id)
- module_name (VARCHAR 100)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
- UNIQUE KEY (role_id, permission_id, module_name)
```

#### 4. permission_logs
```sql
- id (PK)
- admin_id (INT)
- admin_email (VARCHAR 255)
- role (VARCHAR 100)
- module (VARCHAR 100)
- permission (VARCHAR 100)
- granted (TINYINT)
- ip_address (VARCHAR 45)
- user_agent (TEXT)
- created_at (TIMESTAMP)
```

---

## 🎨 Customization

### Adding New Roles

1. **Add to database:**
   ```sql
   INSERT INTO roles (role_name, role_slug, description, hierarchy_level) 
   VALUES ('New Role', 'new_role', 'Description', 3);
   ```

2. **Configure permissions:**
   - Go to Role & Permission Management
   - Grant permissions to the new role
   - Save

### Adding New Modules

1. **Update config/permissions.php:**
   ```php
   function getAllModules() {
       return [
           // ... existing modules
           'new_module' => 'New Module Name'
       ];
   }
   ```

2. **Grant permissions:**
   - Go to Role & Permission Management
   - You'll see the new module
   - Grant permissions as needed

### Changing Permission Types

1. **Add to database:**
   ```sql
   INSERT INTO permissions (permission_name, permission_slug, description)
   VALUES ('New Permission', 'new_permission', 'Description');
   ```

2. **Update UI:**
   - The permission will automatically appear in the matrix
   - Grant to roles as needed

---

## 📈 Performance

### Caching Recommendations

For high-traffic systems, implement permission caching:

```php
// In config/permissions.php
function getCachedPermissions($conn, $module) {
    $cache_key = $_SESSION['admin_id'] . '_' . $module;
    
    if (!isset($_SESSION['user_permissions'][$cache_key])) {
        $_SESSION['user_permissions'][$cache_key] = getUserPermissions($conn, $module);
    }
    
    return $_SESSION['user_permissions'][$cache_key];
}

// Clear cache after permission update
function clearPermissionCache() {
    unset($_SESSION['user_permissions']);
}
```

### Database Indexes

The system automatically creates these indexes:
- `idx_hierarchy` on roles(hierarchy_level)
- `idx_active` on roles(is_active)
- `idx_slug` on permissions(permission_slug)
- `idx_role_module` on role_permissions(role_id, module_name)
- `idx_permission` on role_permissions(permission_id)
- `idx_admin` on permission_logs(admin_id)
- `idx_module` on permission_logs(module)
- `idx_created` on permission_logs(created_at)

---

## 📝 Best Practices

1. **Always include permission checks at the TOP of protected pages**
2. **Use `requirePermission()` for critical pages** (auto-redirects)
3. **Use `hasPermission()` for conditional UI elements**
4. **Never rely solely on UI hiding** - always check server-side
5. **Log important permission checks** for audit trail
6. **Test with different roles** to ensure proper access control
7. **Document module mappings** for future developers
8. **Regularly review permission logs** for security
9. **Backup permission configuration** before major changes
10. **Use transactions** when updating multiple permissions

---

## 🔄 Updates and Maintenance

### Backup Permissions

```sql
-- Export all permissions
SELECT 
    r.role_name,
    p.permission_name,
    rp.module_name
FROM role_permissions rp
JOIN roles r ON rp.role_id = r.id
JOIN permissions p ON rp.permission_id = p.id
ORDER BY r.hierarchy_level, rp.module_name;
```

### Restore Permissions

```sql
-- Clear all permissions
DELETE FROM role_permissions;

-- Re-insert from backup
-- (Use the exported SQL)
```

### Update Permissions in Bulk

Use the Role & Permission Management UI to:
1. Select All permissions
2. Deselect specific permissions
3. Save changes

---

## 📞 Support

### Documentation
- **Detailed Documentation:** `database/README_ROLE_PERMISSIONS.md`
- **Integration Examples:** `config/integration_example.php`
- **Test Page:** `admin/test_permissions.php`

### Common Questions

**Q: Can I have multiple Super Admins?**
A: Yes, assign "Super Admin" role to multiple users.

**Q: Do I need to restart the server after changing permissions?**
A: No, changes take effect immediately.

**Q: Can I customize the modules?**
A: Yes, edit `getAllModules()` in `config/permissions.php`.

**Q: Is the system secure?**
A: Yes, it uses prepared statements, CSRF tokens, and session validation.

**Q: Can I audit permission changes?**
A: Yes, check the `permission_logs` table.

---

## 🎓 Examples

### Complete Page Template

```php
<?php
session_start();
include("admincheck_role.php");
include("../config/db.php");
include("../config/permissions.php");

// Define module
$module = 'employee_management';

// Require view permission
requirePermission($conn, $module, 'view');

// Get user permissions
$user_perms = getUserPermissions($conn, $module);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Employee Management</title>
</head>
<body>
    <h1>Employee Management</h1>
    
    <!-- Action Buttons -->
    <div class="mb-3">
        <?php if (hasPermission($conn, $module, 'create')): ?>
            <a href="add_employee.php" class="btn btn-success">
                <i class="fa fa-plus"></i> Add Employee
            </a>
        <?php endif; ?>
        
        <?php if (hasPermission($conn, $module, 'export')): ?>
            <a href="export_employees.php" class="btn btn-info">
                <i class="fa fa-download"></i> Export
            </a>
        <?php endif; ?>
    </div>
    
    <!-- Your page content -->
    <p>Your permissions: <?= implode(', ', $user_perms) ?></p>
</body>
</html>
```

---

## 📄 License

Part of Employee Management System (EMS) by Minam Shaikh

## 🔖 Version

**1.0.0** - Initial Release

---

## ✅ Checklist

After installation, verify:

- [ ] Installer completed successfully
- [ ] Can access role_permissions.php as Super Admin
- [ ] Permission matrix displays correctly
- [ ] Can save permissions
- [ ] Test page shows correct permissions
- [ ] Access denied page works for unauthorized users
- [ ] Existing pages still work
- [ ] Audit logs are being created

---

## 🎉 Congratulations!

You now have a complete, production-ready Role & Permission Management System!

**Next Steps:**
1. Login as Super Admin
2. Go to Role & Permission Management
3. Configure permissions for each role
4. Test with different user accounts
5. Review and adjust as needed

**Need Help?**
- Check `database/README_ROLE_PERMISSIONS.md` for detailed documentation
- Review `config/integration_example.php` for integration examples
- Use `admin/test_permissions.php` to verify the system

---

**Developed by:** Minam Shaikh  
**Version:** 1.0.0  
**Date:** 2026  
**License:** Part of EMS Project
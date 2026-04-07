# MIKHMON MySQL Database Integration Guide

**Version**: 1.0  
**Date**: April 8, 2026  
**Status**: Ready for Implementation

---

## Table of Contents

1. [Quick Start](#quick-start)
2. [Database Setup](#database-setup)
3. [Configuration](#configuration)
4. [CRUD Operations](#crud-operations)
5. [Multi-Admin System](#multi-admin-system)
6. [Testing & Verification](#testing-verification)
7. [API Reference](#api-reference)
8. [Troubleshooting](#troubleshooting)

---

## Quick Start

### Prerequisites
- PHP 7.4+ with PDO MySQL support
- MySQL 5.7+ or MariaDB 10.3+
- Laragon (already running Apache & MySQL)

### 1. Create Database User

```sql
-- MySQL/MariaDB Command Line
CREATE USER 'mikhmon_user'@'localhost' IDENTIFIED BY 'mikhmon_secure_pass_123';
GRANT ALL PRIVILEGES ON mikhmon_db.* TO 'mikhmon_user'@'localhost';
FLUSH PRIVILEGES;
```

### 2. Run Database Setup

```bash
cd c:\laragon\www\mikhmon
php database/setup.php migrate
php database/setup.php seed
```

### 3. Verify Installation

```bash
php database/setup.php status
```

You should see:
- ✓ Connected to database
- ✓ All tables created
- ✓ Sample data populated

---

## Database Setup

### Step 1: Create Database and User

Using phpMyAdmin or MySQL CLI:

```sql
CREATE DATABASE mikhmon_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'mikhmon_user'@'localhost' IDENTIFIED BY 'mikhmon_secure_pass_123';
GRANT ALL PRIVILEGES ON mikhmon_db.* TO 'mikhmon_user'@'localhost';
FLUSH PRIVILEGES;
```

### Step 2: Run Migrations

```bash
# Navigate to project directory
cd c:\laragon\www\mikhmon

# Create all tables from migrations
php database/setup.php migrate
```

This creates 6 tables:
- `admin_users` - Admin user accounts
- `routers` - Router configurations
- `vouchers` - Voucher tracking
- `user_logs` - User activity logs
- `transaction_history` - Financial records
- `system_logs` - System events

### Step 3: Populate Test Data

```bash
php database/setup.php seed
```

This creates:
- 3 test admin users
- 4-6 routers across admins
- 100+ vouchers
- 1000+ activity logs
- 300+ transaction records

### Step 4: Verify Installation

```bash
php database/setup.php status
```

---

## Configuration

### Database Config File

Edit: `include/db_config.php`

```php
<?php
// Database Connection Settings
define('DB_HOST', 'localhost:3306');        // Database host
define('DB_USER', 'mikhmon_user');          // Database user
define('DB_PASS', 'mikhmon_secure_pass_123'); // Database password
define('DB_NAME', 'mikhmon_db');            // Database name
define('DB_CHARSET', 'utf8mb4');            // Character set

// Debugging (set to true for development)
define('DB_DEBUG', false);

// Error Logging
define('LOG_ERRORS', true);
define('LOG_PATH', './logs/database');
?>
```

### Production Settings

For production, update:

```php
define('DB_HOST', 'your-production-host:3306');
define('DB_USER', 'secure_username');
define('DB_PASS', 'very_secure_password_here');
define('DB_DEBUG', false);  // NEVER enable in production
```

---

## CRUD Operations

### Admin User CRUD

#### CREATE - Add New Admin

```php
<?php
require_once('./include/db_config.php');
require_once('./lib/Database.class.php');
require_once('./lib/AdminUser.class.php');

$db = new Database();
$adminManager = new AdminUser($db, 1); // Superadmin ID = 1

// Create new admin
$result = $adminManager->create(array(
    'username' => 'newadmin',
    'password' => 'SecurePass@123',
    'email' => 'admin@example.com',
    'full_name' => 'New Administrator',
    'role' => 'admin'  // admin, operator, or viewer
));

if ($result) {
    echo "Admin created with ID: {$result}";
} else {
    echo "Failed to create admin";
}
?>
```

#### READ - Get Admin by ID

```php
<?php
$admin = $adminManager->getById(5);

if ($admin) {
    echo "Username: " . $admin['username'];
    echo "Role: " . $admin['role'];
} else {
    echo "Admin not found";
}
?>
```

#### READ - Get Admin by Username

```php
<?php
$admin = $adminManager->getByUsername('admin1');

if ($admin) {
    echo "Admin ID: " . $admin['id'];
}
?>
```

#### UPDATE - Modify Admin

```php
<?php
$result = $adminManager->update(5, array(
    'full_name' => 'Updated Name',
    'email' => 'newemail@example.com',
    'role' => 'operator'
));

if ($result) {
    echo "Admin updated successfully";
}
?>
```

#### DELETE - Disable Admin

```php
<?php
// Soft delete (disable)
$result = $adminManager->delete(5, true);

// Hard delete (permanent - superadmin only)
$result = $adminManager->delete(5, false);
?>
```

---

### Router CRUD

#### CREATE - Add Router

```php
<?php
require_once('./lib/Router.class.php');

$routerManager = new Router($db, $admin_id); // Each admin has their routers

$result = $routerManager->create(array(
    'name' => 'Main Router',
    'description' => 'Primary hotspot router',
    'ip_address' => '192.168.1.1',
    'api_port' => 8728,
    'api_username' => 'admin',
    'api_password_encrypted' => encryptPassword('password123'),
    'hotspot_name' => 'MyHotSpot',
    'dns_server' => '8.8.8.8',
    'currency' => 'USD',
    'interface_name' => 'ether2',
    'idle_timeout' => 1800,
    'max_concurrent_users' => 100
));

if ($result) {
    echo "Router created with ID: {$result}";
}
?>
```

#### READ - Get Router

```php
<?php
// Get router (only if owned by current admin)
$router = $routerManager->getById(3);

if ($router) {
    echo "Router: " . $router['name'];
    echo "IP: " . $router['ip_address'];
} else {
    echo "Router not found or not owned by you";
}
?>
```

#### UPDATE - Modify Router

```php
<?php
$result = $routerManager->update(3, array(
    'description' => 'Updated description',
    'max_concurrent_users' => 200,
    'idle_timeout' => 3600
));

if ($result) {
    echo "Router updated";
}
?>
```

#### DELETE - Disable Router

```php
<?php
$result = $routerManager->delete(3);

if ($result) {
    echo "Router disabled";
}
?>
```

#### LIST - Get All Routers

```php
<?php
$routers = $routerManager->getAll(50, 0, array('only_active' => true));

foreach ($routers as $router) {
    echo $router['name'] . " (" . $router['ip_address'] . ")";
}
?>
```

---

## Multi-Admin System

### How Data Isolation Works

Each admin's routers, vouchers, logs, and transactions are completely isolated:

```php
<?php
// Admin 1's routers
$admin1Router = new Router($db, 1);
$admin1Routers = $admin1Router->getAll();  // Only gets admin1's routers

// Admin 2's routers
$admin2Router = new Router($db, 2);
$admin2Routers = $admin2Router->getAll();  // Only gets admin2's routers

// Admin 2 cannot access Admin 1's routers
$router = $admin2Router->getById(5);  // If belongs to admin 1, returns false
?>
```

### Role-Based Permissions

Roles define what each admin can do:

| Role | Permissions |
|------|-------------|
| superadmin | All permissions |
| admin | Manage routers, users, vouchers, view reports |
| operator | Manage users, vouchers, view reports |
| viewer | View reports only |

```php
<?php
// Check permissions
if ($adminManager->hasPermission($user_id, 'manage_routers')) {
    // User can manage routers
}

if ($adminManager->hasPermission($user_id, 'manage_admins')) {
    // Only superadmin can do this
}
?>
```

---

## Testing & Verification

### Run CRUD Demo

```bash
php test/crud_demo.php
```

This runs:
1. CREATE operations
2. READ operations
3. UPDATE operations
4. LIST operations
5. DELETE operations
6. Data isolation tests
7. Authentication tests

Expected output:
```
✓ PASS: Admin user created successfully
✓ PASS: Admin user retrieved successfully
✓ PASS: Admin user updated successfully
✓ PASS: Retrieved X admin users
✓ PASS: Router created successfully
✓ PASS: Data isolation working correctly
```

### Check Database Status

```bash
php database/setup.php status
```

Shows:
- Total admins, routers, vouchers, logs
- Active/inactive status
- Router count per admin

### View Logs

Check database logs:
```bash
logs/database/error_2024-04-08.log  # Error logs
```

---

## API Reference

### Database Class

Core database operations:

```php
$db = new Database();

// SELECT - Get multiple rows
$results = $db->select("SELECT * FROM tablename WHERE id > ?", array(5));

// SELECT ONE - Get single row
$result = $db->selectOne("SELECT * FROM tablename WHERE id = ?", array(5));

// EXECUTE - INSERT/UPDATE/DELETE
$id = $db->execute("INSERT INTO tablename (name) VALUES (?)", array("test"));
$affected = $db->affectedRows();

// COUNT - Count records
$count = $db->count('tablename', array('is_active' => 1));

// TRANSACTIONS
$db->beginTransaction();
$db->execute($query1, $params1);
$db->execute($query2, $params2);
$db->commit();  // or $db->rollBack();
```

### AdminUser Class

User management:

```php
$admin = new AdminUser($db, $current_admin_id);

// CRUD
$id = $admin->create($data);
$user = $admin->getById($id);
$user = $admin->getByUsername('username');
$admin->update($id, $data);
$admin->delete($id, $soft_delete = true);

// Authentication
$user = $admin->verifyLogin('username', 'password');
$admin->updateLastLogin($id);

// Permissions
$admin->hasPermission($user_id, 'permission_name');
$admin->isAdminSuperadmin($user_id);

// Information
$count = $admin->getTotalCount();
```

### Router Class

Router management:

```php
$router = new Router($db, $admin_id);  // Admin-specific

// CRUD
$id = $router->create($data);
$r = $router->getById($id);
$r = $router->getByIp('192.168.1.1');
$routers = $router->getAll($limit, $offset, $filters);
$router->update($id, $data);
$router->delete($id);  // Soft delete only

// Status
$router->updateConnectionStatus($id, 'connected', '');
$count = $router->getTotalCount($only_active = false);
```

---

## Troubleshooting

### Issue: "Cannot connect to database"

**Solution:**
1. Check credentials in `include/db_config.php`
2. Verify MySQL is running in Laragon
3. Verify database and user exist:
```sql
SHOW DATABASES;
SELECT USER();
```

### Issue: "Table doesn't exist"

**Solution:**
```bash
php database/setup.php migrate
```

### Issue: "Access denied for user"

**Solution:**
```sql
-- Verify user has privileges
GRANT ALL PRIVILEGES ON mikhmon_db.* TO 'mikhmon_user'@'localhost';
FLUSH PRIVILEGES;
```

### Issue: "Data isolation not working"

**Solution:**
Ensure Router/Admin manager is initialized with correct admin_id:
```php
$router = new Router($db, $admin_id);  // Must pass admin_id
```

### Issue: "Logs not writing"

**Solution:**
1. Create logs directory:
```bash
mkdir logs
mkdir logs/database
chmod 755 logs
```

2. Enable logging:
```php
define('LOG_ERRORS', true);
define('LOG_PATH', './logs/database');
```

---

## File Structure

```
mikhmon/
├── include/
│   ├── db_config.php          ← Database configuration
│   ├── auth.php               ← Authentication manager
│   └── ... (existing files)
├── lib/
│   ├── Database.class.php     ← Database abstraction
│   ├── AdminUser.class.php    ← Admin CRUD
│   ├── Router.class.php       ← Router CRUD
│   └── DatabaseSeeder.class.php ← Test data seeder
├── database/
│   ├── migrations/
│   │   └── 001_create_initial_schema.sql ← Database schema
│   └── setup.php              ← Setup script
├── test/
│   └── crud_demo.php          ← CRUD demonstration
└── logs/
    └── database/              ← Error logs
```

---

## Next Steps

1. ✅ Run database setup: `php database/setup.php migrate`
2. ✅ Populate test data: `php database/setup.php seed`
3. ✅ Run CRUD demo: `php test/crud_demo.php`
4. ✅ Test in web browser: `http://localhost/mikhmon/admin.php?id=login`
5. 🔄 Integrate with existing admin.php
6. 🔄 Update login system to use MySQL authentication
7. 🔄 Create admin management UI
8. 🔄 Update router management UI

---

## Support & Documentation

- Database Schema: See `database/migrations/001_create_initial_schema.sql`
- CRUD Examples: See `test/crud_demo.php`
- API Reference: This file (README.md)
- Bangla Documentation: See `DOCUMENTATION_BANGLA.md`

---

**Ready to integrate MySQL? Start with Step 1: Create Database User above!**

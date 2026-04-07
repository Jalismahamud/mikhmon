# MIKHMON Project Optimization & Concept Enhancement

**Document Date**: April 8, 2026  
**Status**: Pre-Development Analysis

---

## Executive Summary

This document outlines the optimization strategy and modernization plan for the Mikhmon (Golden WiFi) project. The project will be enhanced from a simple RouterOS management interface into a robust, scalable system with MySQL integration, improved security, and better code structure.

---

## Current State Analysis

### Strengths ✅
1. **Modular Structure** - Well-organized folder hierarchy
2. **Multi-Router Support** - Can manage multiple RouterOS devices
3. **Feature Rich** - Comprehensive hotspot management capabilities
4. **Multi-Language** - Support for multiple languages (EN, ES, ID, TL)
5. **Multiple Themes** - User interface customization options
6. **GPL License** - Open source and freely usable

### Weaknesses ❌
1. **No Database** - Data stored only in RouterOS (no local persistence)
2. **Hard-coded Configuration** - Credentials in plain PHP arrays
3. **No User Management** - Single admin account only
4. **Security Issues** - No input validation, no encryption
5. **No Audit Trail** - Cannot track who did what and when
6. **No Historical Data** - Reports limited to what RouterOS provides
7. **Legacy Code** - Procedural PHP without framework
8. **Inconsistent Error Handling** - error_reporting(0) hides all issues

---

## Phase 1: Core MySQL Integration (Phase 1)

### Objective
Integrate MySQL database to support:
- Admin user management
- Router profile storage
- Transaction history
- User activity logs
- System audit trails

### Database Schema

#### Table 1: `admin_users`
```sql
CREATE TABLE admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(150),
    full_name VARCHAR(150),
    role ENUM('admin', 'operator', 'viewer') DEFAULT 'operator',
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_username (username),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### Table 2: `routers` (replaces hardcoded config.php)
```sql
CREATE TABLE routers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(15) NOT NULL,
    api_port INT DEFAULT 8728,
    api_username VARCHAR(100) NOT NULL,
    api_password_encrypted VARCHAR(255) NOT NULL,
    hotspot_name VARCHAR(100),
    dns_server VARCHAR(100),
    currency VARCHAR(10) DEFAULT 'USD',
    interface_name VARCHAR(50),
    idle_timeout INT DEFAULT 600,
    reload_interval INT DEFAULT 30,
    is_active BOOLEAN DEFAULT 1,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ip_address (ip_address),
    INDEX idx_is_active (is_active),
    FOREIGN KEY (created_by) REFERENCES admin_users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### Table 3: `vouchers`
```sql
CREATE TABLE vouchers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    router_id INT NOT NULL,
    voucher_code VARCHAR(50) UNIQUE NOT NULL,
    username VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255),
    profile_name VARCHAR(100),
    price DECIMAL(10, 2),
    currency VARCHAR(10),
    status ENUM('active', 'used', 'expired', 'voided') DEFAULT 'active',
    created_by INT,
    used_by VARCHAR(100),
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    INDEX idx_voucher_code (voucher_code),
    INDEX idx_router_id (router_id),
    INDEX idx_status (status),
    FOREIGN KEY (router_id) REFERENCES routers(id),
    FOREIGN KEY (created_by) REFERENCES admin_users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### Table 4: `user_logs`
```sql
CREATE TABLE user_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    router_id INT NOT NULL,
    username VARCHAR(100),
    action VARCHAR(50),
    details TEXT,
    ip_address VARCHAR(15),
    user_agent TEXT,
    status ENUM('success', 'error', 'warning') DEFAULT 'success',
    performed_by INT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_router_id (router_id),
    INDEX idx_timestamp (timestamp),
    INDEX idx_action (action),
    FOREIGN KEY (router_id) REFERENCES routers(id),
    FOREIGN KEY (performed_by) REFERENCES admin_users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### Table 5: `transaction_history`
```sql
CREATE TABLE transaction_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    router_id INT NOT NULL,
    transaction_type ENUM('voucher_sale', 'user_add', 'user_remove', 'credit_sale', 'refund') DEFAULT 'voucher_sale',
    reference_id VARCHAR(100),
    description TEXT,
    amount DECIMAL(10, 2),
    currency VARCHAR(10),
    payment_method VARCHAR(50),
    customer_name VARCHAR(150),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    INDEX idx_router_id (router_id),
    INDEX idx_created_at (created_at),
    INDEX idx_transaction_type (transaction_type),
    FOREIGN KEY (router_id) REFERENCES routers(id),
    FOREIGN KEY (created_by) REFERENCES admin_users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### Table 6: `system_logs`
```sql
CREATE TABLE system_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    log_level ENUM('INFO', 'WARNING', 'ERROR', 'CRITICAL') DEFAULT 'INFO',
    module VARCHAR(50),
    message TEXT,
    context JSON,
    user_id INT,
    ip_address VARCHAR(15),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_timestamp (timestamp),
    INDEX idx_log_level (log_level),
    INDEX idx_module (module),
    FOREIGN KEY (user_id) REFERENCES admin_users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Implementation Structure

#### New File: `include/db_config.php`
```php
<?php
// Database configuration
define('DB_HOST', '127.0.0.1:3306');
define('DB_USER', 'mikhmon_user');
define('DB_PASS', 'secure_password');
define('DB_NAME', 'mikhmon_db');
define('DB_CHARSET', 'utf8mb4');

// Database connection settings
define('DB_PERSISTENT', false);
define('DB_DEBUG', false);
?>
```

#### New File: `lib/Database.class.php`
```php
<?php
/**
 * Database Management Class
 * Provides PDO abstraction for MySQL operations
 */
class Database {
    private $connection = null;
    private $statement = null;
    private $debug = false;

    public function __construct() {
        $this->connect();
    }

    /**
     * Create PDO connection to MySQL
     */
    public function connect() {
        try {
            $dsn = 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET;
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_PERSISTENT => DB_PERSISTENT
            ));
            return true;
        } catch (PDOException $e) {
            $this->error('Database Connection Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Execute SELECT query
     */
    public function select($query, $params = array()) {
        $this->statement = $this->connection->prepare($query);
        $this->statement->execute($params);
        return $this->statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Execute SELECT query - single row
     */
    public function selectOne($query, $params = array()) {
        $result = $this->select($query, $params);
        return !empty($result) ? $result[0] : null;
    }

    /**
     * Execute INSERT/UPDATE/DELETE
     */
    public function execute($query, $params = array()) {
        try {
            $this->statement = $this->connection->prepare($query);
            $this->statement->execute($params);
            return $this->connection->lastInsertId();
        } catch (PDOException $e) {
            $this->error('Query Execution Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get affected rows count
     */
    public function rowCount() {
        return $this->statement ? $this->statement->rowCount() : 0;
    }

    /**
     * Start database transaction
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        return $this->connection->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollBack() {
        return $this->connection->rollBack();
    }

    /**
     * Log errors
     */
    private function error($message) {
        if (DB_DEBUG) {
            error_log('[MIKHMON DB ERROR] ' . $message);
        }
    }

    /**
     * Close connection
     */
    public function closeConnection() {
        $this->connection = null;
    }
}
?>
```

#### New File: `lib/Auth.class.php`
```php
<?php
/**
 * Authentication & Authorization Class
 * Manages admin user login and session handling
 */
class Auth {
    private $db = null;
    private $session_lifetime = 3600; // 1 hour

    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Authenticate user with username and password
     */
    public function authenticate($username, $password) {
        $query = "SELECT id, username, password_hash, role, is_active 
                  FROM admin_users 
                  WHERE username = ? AND is_active = 1 LIMIT 1";
        
        $user = $this->db->selectOne($query, array($username));
        
        if (!$user) {
            $this->logFailedAttempt($username, $_SERVER['REMOTE_ADDR']);
            return false;
        }

        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            $this->logFailedAttempt($username, $_SERVER['REMOTE_ADDR']);
            return false;
        }

        // Update last login
        $update = "UPDATE admin_users SET last_login = NOW() WHERE id = ?";
        $this->db->execute($update, array($user['id']));

        // Create session
        $_SESSION['mikhmon_user_id'] = $user['id'];
        $_SESSION['mikhmon_username'] = $user['username'];
        $_SESSION['mikhmon_role'] = $user['role'];
        $_SESSION['mikhmon_login_time'] = time();

        return $user;
    }

    /**
     * Check if user is authenticated
     */
    public function isAuthenticated() {
        if (!isset($_SESSION['mikhmon_user_id'])) {
            return false;
        }

        // Check session timeout
        if (time() - $_SESSION['mikhmon_login_time'] > $this->session_lifetime) {
            $this->logout();
            return false;
        }

        return true;
    }

    /**
     * Check user role
     */
    public function hasRole($required_role) {
        if (!$this->isAuthenticated()) {
            return false;
        }

        $user_role = $_SESSION['mikhmon_role'];
        $role_hierarchy = array('admin' => 3, 'operator' => 2, 'viewer' => 1);
        $required_level = $role_hierarchy[$required_role] ?? 0;
        $user_level = $role_hierarchy[$user_role] ?? 0;

        return $user_level >= $required_level;
    }

    /**
     * Logout user
     */
    public function logout() {
        session_destroy();
        return true;
    }

    /**
     * Get current user info
     */
    public function getCurrentUser() {
        if (!$this->isAuthenticated()) {
            return null;
        }

        return array(
            'id' => $_SESSION['mikhmon_user_id'],
            'username' => $_SESSION['mikhmon_username'],
            'role' => $_SESSION['mikhmon_role']
        );
    }

    /**
     * Log failed login attempt
     */
    private function logFailedAttempt($username, $ip_address) {
        $query = "INSERT INTO system_logs (log_level, module, message, context, ip_address) 
                  VALUES (?, ?, ?, ?, ?)";
        $context = json_encode(array('username' => $username));
        $this->db->execute($query, array('WARNING', 'auth', 'Failed login attempt', $context, $ip_address));
    }
}
?>
```

### Configuration Migration Step

The current `include/config.php` structure will be preserved for backward compatibility during transition, but gradually replaced with database queries.

---

## Phase 2: Code Structure Improvement

### A. Move from Procedural to OOP

**Current Problem:**
```php
// admin.php - Direct procedural code
$session = $_GET['session'];
$id = $_GET['id'];
// ...lots of inline logic
```

**Improved Approach:**
```php
// class/Router.class.php
class Router {
    public function getConnections() { ... }
    public function addUser($params) { ... }
}

// class/Hotspot.class.php
class Hotspot {
    public function getUsers($profile) { ... }
    public function getUserProfiles() { ... }
}
```

### B. Create Service Layer

```php
// service/UserService.php
class UserService {
    private $router;
    private $db;

    public function createVoucher($quantity, $profile, $price) {
        // Business logic for voucher creation
    }

    public function deleteUser($user_id) {
        // Business logic for user deletion
    }
}
```

### C. Implement Router Pattern

```php
// core/Router.php
$router = new Router();

$router->get('/', 'DashboardController@index');
$router->get('/hotspot/users', 'HotspotController@listUsers');
$router->post('/hotspot/users', 'HotspotController@createUser');
$router->delete('/hotspot/users/:id', 'HotspotController@deleteUser');

$router->dispatch($_REQUEST['route']);
```

---

## Phase 3: Security Hardening

### A. Input Validation Framework
```php
// lib/Validator.class.php
class Validator {
    public static function ip($value) {
        return filter_var($value, FILTER_VALIDATE_IP);
    }

    public static function username($value) {
        return preg_match('/^[a-zA-Z0-9_-]{3,50}$/', $value);
    }

    public static function email($value) {
        return filter_var($value, FILTER_VALIDATE_EMAIL);
    }
}
```

### B. Password Encryption
```php
// Replace plain passwords with bcrypt
$hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
// Verify: password_verify($input, $hashed)
```

### C. CSRF Protection
```php
// Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Validate in forms
echo '<input type="hidden" name="csrf_token" value="'.$_SESSION['csrf_token'].'">';

// Verify in handler
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('CSRF token validation failed');
}
```

### D. API Rate Limiting
```php
// lib/RateLimiter.class.php
class RateLimiter {
    public function checkLimit($identifier, $limit = 100, $window = 3600) {
        // Track requests per user/IP
        // Return false if limit exceeded
    }
}
```

---

## Phase 4: API Development

### RESTful API Endpoints

```
Authentication
  POST   /api/auth/login              - Login user
  POST   /api/auth/logout             - Logout user
  GET    /api/auth/me                 - Get current user

Routers
  GET    /api/routers                 - List all routers
  POST   /api/routers                 - Create router profile
  GET    /api/routers/:id             - Get router details
  PUT    /api/routers/:id             - Update router
  DELETE /api/routers/:id             - Delete router

Hotspot Users
  GET    /api/routers/:id/users       - List users
  POST   /api/routers/:id/users       - Add user
  GET    /api/routers/:id/users/:uid  - Get user details
  PUT    /api/routers/:id/users/:uid  - Update user
  DELETE /api/routers/:id/users/:uid  - Remove user

Vouchers
  GET    /api/routers/:id/vouchers    - List vouchers
  POST   /api/routers/:id/vouchers    - Generate vouchers
  GET    /api/routers/:id/vouchers/:code - Get voucher

Reports
  GET    /api/reports/transactions    - Transaction report
  GET    /api/reports/users           - User activity report
  GET    /api/reports/traffic         - Traffic report
```

---

## Phase 5: Frontend Enhancement

### A. Modernize UI Framework
- **Replace**: Direct HTML + Inline CSS
- **With**: Bootstrap 5 or Tailwind CSS
- **Add**: Vue.js or React for dynamic components

### B. Responsive Design
```html
<!-- Existing: Fixed layout -->
<!-- New: Mobile-first responsive -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
```

### C. Data Table Enhancement
```javascript
// Replace: Static HTML tables
// With: DataTables.js
  - Sorting
  - Filtering
  - Pagination
  - Export functionality
```

---

## Phase 6: Monitoring & Analytics

### A. Dashboard Metrics
- Total users connected
- Revenue generated (daily/monthly)
- Most popular profiles
- Traffic statistics
- Active vouchers

### B. Real-time Notifications
```php
// Alert admin to:
// - Critical errors
// - High traffic
// - Failed connections
// - Suspicious activities
```

### C. Reports Generation
- PDF export capability
- Scheduled reports via email
- Custom date range queries
- Data visualization (charts/graphs)

---

## Development Roadmap

### Timeline

```
Week 1-2: Database Design & MySQL Setup
  ✓ Create database schema
  ✓ Set up database
  ✓ Create initial migration scripts

Week 3-4: Core Class Development
  ✓ Database.class.php
  ✓ Auth.class.php
  ✓ Validator.class.php

Week 5-6: Integration & Testing
  ✓ Modify admin.php for MySQL auth
  ✓ Create router profile management
  ✓ Unit testing

Week 7-8: APIs & Web Services
  ✓ Implement REST API
  ✓ API authentication
  ✓ API documentation

Week 9-10: UI Enhancement
  ✓ Update templates
  ✓ Responsive design
  ✓ User experience improvements

Week 11-12: Testing & Deployment
  ✓ Full integration testing
  ✓ Performance optimization
  ✓ Security audit
  ✓ Production deployment
```

---

## Best Practices to Implement

### 1. Code Organization
```
mikhmon/
├── app/
│   ├── controllers/
│   ├── models/
│   ├── services/
│   └── middleware/
├── config/
├── database/
│   ├── migrations/
│   └── seeds/
├── public/
├── resources/
│   ├── views/
│   ├── css/
│   └── js/
└── tests/
```

### 2. Version Control
```bash
git init
git add .
git commit -m "Initial commit with MySQL integration"
git branch develop
# Feature branches: feature/user-management, feature/api-endpoints
```

### 3. Documentation
- Inline code comments
- README.md with setup instructions
- API documentation (Swagger/OpenAPI)
- Database schema documentation

### 4. Testing
- Unit tests (phpunit)
- Integration tests
- User acceptance tests (UAT)
- Performance testing

### 5. Deployment
- Automated migrations
- Environment configuration
- Backup procedures
- Rollback capability

---

## Risk Assessment

| Risk | Impact | Mitigation |
|------|--------|-----------|
| Data loss | Critical | Regular backups, transaction support |
| Performance degradation | High | Database indexing, query optimization |
| Security vulnerabilities | Critical | Security audit, penetration testing |
| Backward compatibility | Medium | Version compatibility layer |
| Developer learning curve | Medium | Training, documentation, code reviews |

---

## Success Metrics

- ✅ All critical features working with MySQL
- ✅ Admin user access control implemented
- ✅ API endpoints operational
- ✅ Security vulnerabilities resolved
- ✅ Page load time < 2 seconds
- ✅ 95%+ test coverage
- ✅ Zero data loss incidents
- ✅ Documentation complete in Bangla & English

---

## Conclusion

This optimization plan transforms Mikhmon from a simple RouterOS interface into a robust, enterprise-ready hotspot management system. The phased approach allows for incremental improvements while maintaining system stability.

**Next Steps:**
1. Get stakeholder approval for this plan
2. Set up development environment with MySQL
3. Create database schema and initial migrations
4. Begin Phase 1 implementation

---

**Document Prepared**: April 8, 2026  
**Status**: Ready for Stakeholder Review  
**Estimated Timeline**: 12 weeks  
**Team Size Recommended**: 2-3 developers

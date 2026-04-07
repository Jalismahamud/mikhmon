-- ============================================
-- Mikhmon Database Schema
-- MikroTik Hotspot Management System
-- Multi-Admin, Multi-Router Architecture
-- ============================================

-- Drop existing tables if exists (for clean migration)
DROP TABLE IF EXISTS system_logs;
DROP TABLE IF EXISTS transaction_history;
DROP TABLE IF EXISTS user_logs;
DROP TABLE IF EXISTS vouchers;
DROP TABLE IF EXISTS routers;
DROP TABLE IF EXISTS admin_users;

-- ============================================
-- 1. ADMIN USERS TABLE
-- ============================================
CREATE TABLE admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL COMMENT 'Unique admin username',
    password_hash VARCHAR(255) NOT NULL COMMENT 'Bcrypt hashed password',
    email VARCHAR(150) UNIQUE NOT NULL COMMENT 'Admin email address',
    full_name VARCHAR(150) NOT NULL COMMENT 'Full name of admin',
    role ENUM('superadmin', 'admin', 'operator', 'viewer') NOT NULL DEFAULT 'operator' COMMENT 'User role and permissions',
    is_active BOOLEAN DEFAULT 1 COMMENT 'Account active status',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Account creation timestamp',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
    last_login TIMESTAMP NULL COMMENT 'Last login timestamp',
    
    -- Indexes for performance
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_is_active (is_active),
    INDEX idx_created_at (created_at)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Admin user accounts and authentication';

-- ============================================
-- 2. ROUTERS TABLE (Replaces hardcoded config.php)
-- ============================================
CREATE TABLE routers (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT 'Unique router ID',
    admin_id INT NOT NULL COMMENT 'Admin who owns this router',
    name VARCHAR(100) NOT NULL COMMENT 'Router server name/label',
    description TEXT COMMENT 'Router description',
    ip_address VARCHAR(15) NOT NULL COMMENT 'RouterOS IP address',
    api_port INT NOT NULL DEFAULT 8728 COMMENT 'RouterOS API port',
    api_username VARCHAR(100) NOT NULL COMMENT 'RouterOS API username',
    api_password_encrypted VARCHAR(255) NOT NULL COMMENT 'Encrypted RouterOS API password',
    hotspot_name VARCHAR(100) NOT NULL COMMENT 'Hotspot service name',
    dns_server VARCHAR(100) COMMENT 'DNS server address',
    currency VARCHAR(10) DEFAULT 'USD' COMMENT 'Default currency for vouchers',
    interface_name VARCHAR(50) COMMENT 'Primary network interface',
    idle_timeout INT DEFAULT 600 COMMENT 'User idle timeout in seconds',
    reload_interval INT DEFAULT 30 COMMENT 'Page reload interval',
    max_concurrent_users INT DEFAULT 0 COMMENT 'Maximum concurrent users (0=unlimited)',
    is_active BOOLEAN DEFAULT 1 COMMENT 'Router connection active',
    connection_status VARCHAR(50) DEFAULT 'disconnected' COMMENT 'Current connection status',
    last_connected TIMESTAMP NULL COMMENT 'Last successful connection time',
    
    created_by INT NOT NULL COMMENT 'User who created this router',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation timestamp',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
    updated_by INT DEFAULT NULL COMMENT 'User who last updated',
    
    -- Foreign keys
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE RESTRICT,
    FOREIGN KEY (updated_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    
    -- Indexes for performance
    INDEX idx_admin_id (admin_id),
    INDEX idx_ip_address (ip_address),
    INDEX idx_is_active (is_active),
    INDEX idx_created_at (created_at),
    INDEX idx_admin_active (admin_id, is_active)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='MikroTik Router configurations per admin';

-- ============================================
-- 3. VOUCHERS TABLE
-- ============================================
CREATE TABLE vouchers (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT 'Unique voucher ID',
    router_id INT NOT NULL COMMENT 'Associated router',
    admin_id INT NOT NULL COMMENT 'Admin who created this voucher',
    voucher_code VARCHAR(50) UNIQUE NOT NULL COMMENT 'Unique voucher code',
    username VARCHAR(100) NOT NULL COMMENT 'Hotspot username from code',
    password_plain VARCHAR(100) COMMENT 'Plain password (stored temporarily)',
    profile_name VARCHAR(100) NOT NULL COMMENT 'Hotspot profile used',
    price DECIMAL(10, 2) DEFAULT 0 COMMENT 'Voucher selling price',
    currency VARCHAR(10) DEFAULT 'USD' COMMENT 'Price currency',
    valid_days INT DEFAULT 1 COMMENT 'Voucher validity in days',
    status ENUM('active', 'used', 'expired', 'voided') DEFAULT 'active' COMMENT 'Current voucher status',
    
    created_by INT NOT NULL COMMENT 'User who generated voucher',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Generation timestamp',
    
    used_by VARCHAR(100) COMMENT 'Username who used this voucher',
    used_at TIMESTAMP NULL COMMENT 'When voucher was used',
    expires_at TIMESTAMP NULL COMMENT 'Voucher expiration time',
    
    notes TEXT COMMENT 'Admin notes for this voucher',
    
    -- Foreign keys
    FOREIGN KEY (router_id) REFERENCES routers(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE RESTRICT,
    
    -- Indexes for performance
    INDEX idx_voucher_code (voucher_code),
    INDEX idx_router_id (router_id),
    INDEX idx_admin_id (admin_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_used_at (used_at),
    INDEX idx_admin_router (admin_id, router_id)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Generated voucher tracking';

-- ============================================
-- 4. USER LOGS TABLE
-- ============================================
CREATE TABLE user_logs (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT 'Log entry ID',
    router_id INT NOT NULL COMMENT 'Associated router',
    admin_id INT NOT NULL COMMENT 'Admin of this router',
    username VARCHAR(100) COMMENT 'Hotspot username',
    action VARCHAR(50) NOT NULL COMMENT 'Action performed',
    action_type ENUM('create', 'update', 'delete', 'enable', 'disable', 'reset', 'login', 'logout') COMMENT 'Type of action',
    details TEXT COMMENT 'Action details/description',
    old_value TEXT COMMENT 'Previous value (for updates)',
    new_value TEXT COMMENT 'New value (for updates)',
    ip_address VARCHAR(15) COMMENT 'Request IP address',
    status ENUM('success', 'error', 'warning') DEFAULT 'success' COMMENT 'Action result status',
    error_message TEXT COMMENT 'Error message if failed',
    performed_by INT NOT NULL COMMENT 'Admin who performed action',
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Action timestamp',
    
    -- Foreign keys
    FOREIGN KEY (router_id) REFERENCES routers(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by) REFERENCES admin_users(id) ON DELETE RESTRICT,
    
    -- Indexes for performance
    INDEX idx_router_id (router_id),
    INDEX idx_admin_id (admin_id),
    INDEX idx_action (action),
    INDEX idx_timestamp (timestamp),
    INDEX idx_status (status),
    INDEX idx_admin_router_time (admin_id, router_id, timestamp)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='User activity and operation logs per admin';

-- ============================================
-- 5. TRANSACTION HISTORY TABLE
-- ============================================
CREATE TABLE transaction_history (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT 'Transaction ID',
    router_id INT NOT NULL COMMENT 'Associated router',
    admin_id INT NOT NULL COMMENT 'Admin of this router',
    transaction_type ENUM('voucher_sale', 'user_add', 'user_remove', 'credit_sale', 'refund', 'subscription') NOT NULL COMMENT 'Type of transaction',
    reference_id VARCHAR(100) COMMENT 'Reference (voucher code, user ID)',
    description TEXT NOT NULL COMMENT 'Transaction description',
    amount DECIMAL(10, 2) DEFAULT 0 COMMENT 'Transaction amount',
    currency VARCHAR(10) DEFAULT 'USD' COMMENT 'Amount currency',
    payment_method VARCHAR(50) COMMENT 'How payment was received',
    payment_status ENUM('completed', 'pending', 'failed', 'refunded') DEFAULT 'completed' COMMENT 'Payment status',
    customer_name VARCHAR(150) COMMENT 'Customer name',
    customer_phone VARCHAR(20) COMMENT 'Customer phone',
    customer_email VARCHAR(150) COMMENT 'Customer email',
    
    created_by INT NOT NULL COMMENT 'User who recorded transaction',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Transaction timestamp',
    
    notes TEXT COMMENT 'Additional notes',
    
    -- Foreign keys
    FOREIGN KEY (router_id) REFERENCES routers(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE RESTRICT,
    
    -- Indexes for performance
    INDEX idx_router_id (router_id),
    INDEX idx_admin_id (admin_id),
    INDEX idx_transaction_type (transaction_type),
    INDEX idx_created_at (created_at),
    INDEX idx_payment_status (payment_status),
    INDEX idx_admin_router_date (admin_id, router_id, created_at)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Financial and operational transactions per admin';

-- ============================================
-- 6. SYSTEM LOGS TABLE
-- ============================================
CREATE TABLE system_logs (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT 'Log entry ID',
    admin_id INT COMMENT 'Associated admin (NULL for system events)',
    log_level ENUM('DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL') DEFAULT 'INFO' COMMENT 'Log severity level',
    module VARCHAR(100) COMMENT 'Module name',
    message TEXT NOT NULL COMMENT 'Log message',
    context JSON COMMENT 'Additional context data',
    user_id INT COMMENT 'User ID if applicable',
    ip_address VARCHAR(15) COMMENT 'Request IP address',
    user_agent TEXT COMMENT 'Browser user agent',
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Log timestamp',
    
    -- Foreign keys (optional)
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE SET NULL,
    
    -- Indexes for performance
    INDEX idx_timestamp (timestamp),
    INDEX idx_log_level (log_level),
    INDEX idx_module (module),
    INDEX idx_admin_id (admin_id),
    INDEX idx_level_module (log_level, module),
    INDEX idx_admin_time (admin_id, timestamp)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='System and application event logs';

-- ============================================
-- SAMPLE DATA - INITIAL ADMINS
-- ============================================

-- Insert default superadmin user
INSERT INTO admin_users (username, password_hash, email, full_name, role, is_active) VALUES
('admin', '$2y$12$vJ0WlT7/cqRZ8QNZJbWWvO0Q7ydfR3Pv0pX8Jw5nQ9cQ1ZwK7/xpi', 'admin@mikhmon.local', 'System Administrator', 'superadmin', 1),
('admin1', '$2y$12$vJ0WlT7/cqRZ8QNZJbWWvO0Q7ydfR3Pv0pX8Jw5nQ9cQ1ZwK7/xpi', 'admin1@mikhmon.local', 'Administrator One', 'admin', 1),
('admin2', '$2y$12$vJ0WlT7/cqRZ8QNZJbWWvO0Q7ydfR3Pv0pX8Jw5nQ9cQ1ZwK7/xpi', 'admin2@mikhmon.local', 'Administrator Two', 'admin', 1);

-- NOTE: Password hash is for 'password123' using bcrypt
-- All demo accounts use password: 'password123'
-- Change immediately on first login!

-- Create example routers for admin1
INSERT INTO routers (admin_id, name, description, ip_address, api_port, api_username, api_password_encrypted, hotspot_name, currency, interface_name, is_active, created_by) VALUES
(2, 'Main Router', 'Primary router for location 1', '192.168.1.1', 8728, 'admin', 'rO5OLg9mKJxDQ2nXpQ==', 'HotSpot1', 'USD', 'ether2', 1, 2),
(2, 'Branch Router', 'Secondary router for location 2', '192.168.2.1', 8728, 'admin', 'rO5OLg9mKJxDQ2nXpQ==', 'HotSpot2', 'USD', 'ether2', 1, 2);

-- Create example routers for admin2
INSERT INTO routers (admin_id, name, description, ip_address, api_port, api_username, api_password_encrypted, hotspot_name, currency, interface_name, is_active, created_by) VALUES
(3, 'Coffee Shop Router', 'Router for coffee shop', '192.168.10.1', 8728, 'admin', 'rO5OLg9mKJxDQ2nXpQ==', 'CoffeeShop_WiFi', 'USD', 'ether2', 1, 3);

-- Log system initialization
INSERT INTO system_logs (admin_id, log_level, module, message) VALUES
(NULL, 'INFO', 'database', 'Database initialized with default admin users and sample routers');

-- Display confirmation message
SELECT '✓ Database initialization successful!' as status;
SELECT COUNT(*) as total_admins FROM admin_users;
SELECT COUNT(*) as total_routers FROM routers;

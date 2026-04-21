-- Mikhmon Database Schema (Simplified for PHP execution)

DROP TABLE IF EXISTS system_logs;
DROP TABLE IF EXISTS transaction_history;
DROP TABLE IF EXISTS user_logs;
DROP TABLE IF EXISTS vouchers;
DROP TABLE IF EXISTS routers;
DROP TABLE IF EXISTS admin_users;

CREATE TABLE admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    role ENUM('superadmin', 'admin', 'operator', 'viewer') NOT NULL DEFAULT 'operator',
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_is_active (is_active),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE routers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(15) NOT NULL,
    api_port INT NOT NULL DEFAULT 8728,
    api_username VARCHAR(100) NOT NULL,
    api_password_encrypted VARCHAR(255) NOT NULL,
    hotspot_name VARCHAR(100) NOT NULL,
    dns_server VARCHAR(100),
    currency VARCHAR(10) DEFAULT 'USD',
    interface_name VARCHAR(50),
    idle_timeout INT DEFAULT 600,
    reload_interval INT DEFAULT 30,
    max_concurrent_users INT DEFAULT 0,
    is_active BOOLEAN DEFAULT 1,
    connection_status VARCHAR(50) DEFAULT 'disconnected',
    last_connected TIMESTAMP NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT DEFAULT NULL,
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE RESTRICT,
    FOREIGN KEY (updated_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_admin_id (admin_id),
    INDEX idx_ip_address (ip_address),
    INDEX idx_is_active (is_active),
    INDEX idx_created_at (created_at),
    INDEX idx_admin_active (admin_id, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE vouchers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    router_id INT NOT NULL,
    admin_id INT NOT NULL,
    voucher_code VARCHAR(50) UNIQUE NOT NULL,
    username VARCHAR(100) NOT NULL,
    password_plain VARCHAR(100),
    profile_name VARCHAR(100) NOT NULL,
    price DECIMAL(10, 2) DEFAULT 0,
    currency VARCHAR(10) DEFAULT 'USD',
    valid_days INT DEFAULT 1,
    status ENUM('active', 'used', 'expired', 'voided') DEFAULT 'active',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    used_by VARCHAR(100),
    used_at TIMESTAMP NULL,
    expired_at TIMESTAMP NULL,
    notes TEXT,
    INDEX idx_voucher_code (voucher_code),
    INDEX idx_router_id (router_id),
    INDEX idx_admin_id (admin_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    router_id INT NOT NULL,
    admin_id INT NOT NULL,
    username VARCHAR(100) NOT NULL,
    action VARCHAR(50) NOT NULL,
    details TEXT,
    ip_address VARCHAR(15),
    session_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_router_id (router_id),
    INDEX idx_admin_id (admin_id),
    INDEX idx_username (username),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE transaction_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    voucher_id INT,
    transaction_type VARCHAR(50) NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'USD',
    payment_method VARCHAR(50),
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_admin_id (admin_id),
    INDEX idx_voucher_id (voucher_id),
    INDEX idx_transaction_type (transaction_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE system_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT,
    log_level VARCHAR(20) NOT NULL DEFAULT 'INFO',
    module VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    context JSON,
    ip_address VARCHAR(15),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_admin_id (admin_id),
    INDEX idx_log_level (log_level),
    INDEX idx_module (module),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

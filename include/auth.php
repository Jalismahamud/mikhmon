<?php
/*
 * auth.php
 * Database-based authentication module
 * Replaces hardcoded credentials with MySQL user database
 * 
 * Integrates with existing session handling
 */

// Ensure database config is loaded
if (!defined('DB_HOST')) {
    require_once(dirname(__FILE__) . '/db_config.php');
}

// Include database and auth classes
require_once(dirname(__FILE__) . '/../lib/Database.class.php');
require_once(dirname(__FILE__) . '/../lib/AdminUser.class.php');

class AuthManager {
    private $db = null;
    private $adminUser = null;
    private $current_user = null;
    private $session_lifetime = 3600; // 1 hour

    /**
     * Constructor
     */
    public function __construct() {
        $this->db = new Database();
        
        if (!$this->db->isConnected()) {
            error_log('[AUTH] Database connection failed');
            return false;
        }

        $this->adminUser = new AdminUser($this->db, null);
    }

    /**
     * Check if user is authenticated
     * 
     * @return boolean
     */
    public function isAuthenticated() {
        if (!isset($_SESSION['mikhmon_user_id']) || !isset($_SESSION['mikhmon_login_time'])) {
            return false;
        }

        // Check session timeout
        if (time() - $_SESSION['mikhmon_login_time'] > $this->session_lifetime) {
            $this->logout();
            return false;
        }

        // Refresh session timer
        $_SESSION['mikhmon_login_time'] = time();

        return true;
    }

    /**
     * Authenticate user with username and password
     * 
     * @param string $username Admin username
     * @param string $password Plain password
     * @return array|false User data if valid, false otherwise
     */
    public function login($username, $password) {
        try {
            // Trim inputs
            $username = trim($username);
            $password = trim($password);

            // Validate inputs
            if (empty($username) || empty($password)) {
                $this->logFailedLogin($username, 'Empty credentials');
                return false;
            }

            // Verify login
            $user = $this->adminUser->verifyLogin($username, $password);

            if (!$user) {
                $this->logFailedLogin($username, 'Invalid credentials');
                return false;
            }

            // Set session variables
            $_SESSION['mikhmon_user_id'] = $user['id'];
            $_SESSION['mikhmon_username'] = $user['username'];
            $_SESSION['mikhmon_user_role'] = $user['role'];
            $_SESSION['mikhmon_email'] = $user['email'];
            $_SESSION['mikhmon_full_name'] = $user['full_name'];
            $_SESSION['mikhmon_login_time'] = time();
            $_SESSION['mikhmon'] = $user['username']; // For backward compatibility

            // Set user object
            $this->current_user = $user;

            // Log successful login
            $this->logSuccessfulLogin($user['id']);

            return $user;

        } catch (Exception $e) {
            error_log('[AUTH LOGIN] Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get current authenticated user
     * 
     * @return array|false User data or false
     */
    public function getCurrentUser() {
        if (!$this->isAuthenticated()) {
            return false;
        }

        if ($this->current_user === null) {
            // Reload from database
            $this->current_user = $this->adminUser->getById($_SESSION['mikhmon_user_id']);
            unset($this->current_user['password_hash']); // Remove sensitive data
        }

        return $this->current_user;
    }

    /**
     * Get current user ID
     * 
     * @return int|false User ID or false
     */
    public function getUserId() {
        return $this->isAuthenticated() ? $_SESSION['mikhmon_user_id'] : false;
    }

    /**
     * Get current user role
     * 
     * @return string|false User role or false
     */
    public function getUserRole() {
        return $this->isAuthenticated() ? $_SESSION['mikhmon_user_role'] : false;
    }

    /**
     * Check if current user has permission
     * 
     * @param string $permission Permission to check
     * @return boolean
     */
    public function hasPermission($permission) {
        $user_id = $this->getUserId();
        if (!$user_id) {
            return false;
        }

        return $this->adminUser->hasPermission($user_id, $permission);
    }

    /**
     * Logout user
     * 
     * @return boolean
     */
    public function logout() {
        // Store user ID before destroying session
        $user_id = isset($_SESSION['mikhmon_user_id']) ? $_SESSION['mikhmon_user_id'] : null;

        // Clear session
        session_destroy();
        session_start();

        // Log logout
        if ($user_id) {
            try {
                $query = "INSERT INTO system_logs (admin_id, log_level, module, message) 
                          VALUES (?, 'INFO', 'auth', 'User logged out')";
                $this->db->execute($query, array($user_id));
            } catch (Exception $e) {
                // Ignore logging errors
            }
        }

        return true;
    }

    /**
     * Log successful login
     * 
     * @param int $user_id User ID
     * @return void
     */
    private function logSuccessfulLogin($user_id) {
        try {
            $query = "INSERT INTO system_logs (admin_id, log_level, module, message, ip_address, user_agent) 
                      VALUES (?, 'INFO', 'auth', 'User logged in', ?, ?)";

            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

            $this->db->execute($query, array($user_id, $ip, $user_agent));

        } catch (Exception $e) {
            error_log('[AUTH LOGGING] Error: ' . $e->getMessage());
        }
    }

    /**
     * Log failed login attempt
     * 
     * @param string $username Username
     * @param string $reason Failure reason
     * @return void
     */
    private function logFailedLogin($username, $reason = '') {
        try {
            $query = "INSERT INTO system_logs (log_level, module, message, context, ip_address, user_agent) 
                      VALUES ('WARNING', 'auth', 'Failed login attempt', ?, ?, ?)";

            $context = json_encode(array(
                'username' => $username,
                'reason' => $reason
            ));

            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

            $this->db->execute($query, array($context, $ip, $user_agent));

        } catch (Exception $e) {
            error_log('[AUTH LOGGING] Error: ' . $e->getMessage());
        }
    }

    /**
     * Require authentication - redirect to login if not authenticated
     * 
     * @param string $required_permission Optional permission to check
     * @return boolean True if authenticated (with permission)
     */
    public function requireAuth($required_permission = null) {
        if (!$this->isAuthenticated()) {
            header("Location: ./admin.php?id=login");
            exit;
        }

        if ($required_permission && !$this->hasPermission($required_permission)) {
            header("HTTP/1.0 403 Forbidden");
            echo "You do not have permission to access this resource.";
            exit;
        }

        return true;
    }

    /**
     * Create new admin user (admin only)
     * 
     * @param array $data User data
     * @return int|false User ID or false
     */
    public function createUser($data) {
        // Check if current user is admin
        if (!$this->hasPermission('manage_admins')) {
            return false;
        }

        return $this->adminUser->create($data);
    }

    /**
     * Update user (owns profile can update self)
     * 
     * @param int $user_id User ID to update
     * @param array $data Data to update
     * @return boolean
     */
    public function updateUser($user_id, $data) {
        $current_id = $this->getUserId();

        // Can update own profile or admin can update others
        if ($current_id !== $user_id && !$this->hasPermission('manage_admins')) {
            return false;
        }

        return $this->adminUser->update($user_id, $data);
    }

    /**
     * Get database instance (for CRUD operations)
     * 
     * @return Database
     */
    public function getDatabase() {
        return $this->db;
    }

    /**
     * Get AdminUser instance (for CRUD operations)
     * 
     * @return AdminUser
     */
    public function getAdminUserManager() {
        return $this->adminUser;
    }
}

// Create global auth instance
$auth = new AuthManager();

// Function to get current auth instance
function getAuth() {
    global $auth;
    return $auth;
}
?>

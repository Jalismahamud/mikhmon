<?php
/*
 * AdminUser.class.php
 * Admin User Management
 * CRUD operations for admin users with role-based access control
 */

require_once(dirname(__FILE__) . '/../lib/Database.class.php');

class AdminUser {
    private $db = null;
    private $table = 'admin_users';
    private $current_admin_id = null;

    /**
     * Constructor
     * 
     * @param Database $database Database instance
     * @param int $current_admin_id Current admin ID (for audit trail)
     */
    public function __construct($database, $current_admin_id = null) {
        $this->db = $database;
        $this->current_admin_id = $current_admin_id;
    }

    /**
     * CREATE - Add new admin user
     * 
     * @param array $data Admin data (username, password, email, full_name, role)
     * @return int|false Admin ID or false on error
     */
    public function create($data) {
        try {
            // Validate required fields
            if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
                return false;
            }

            // Check if username already exists
            $existing = $this->db->selectOne(
                "SELECT id FROM `{$this->table}` WHERE username = ? LIMIT 1",
                array($data['username'])
            );

            if ($existing) {
                return false; // Username already exists
            }

            // Hash password
            $password_hash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);

            // Prepare query
            $query = "INSERT INTO `{$this->table}` 
                      (username, password_hash, email, full_name, role, is_active) 
                      VALUES (?, ?, ?, ?, ?, ?)";

            $params = array(
                $data['username'],
                $password_hash,
                $data['email'],
                isset($data['full_name']) ? $data['full_name'] : $data['username'],
                isset($data['role']) ? $data['role'] : 'operator',
                isset($data['is_active']) ? $data['is_active'] : 1
            );

            $result = $this->db->execute($query, $params);

            if ($result) {
                // Log the action
                $this->logAction('create', null, json_encode($data), 'Admin user created');
                return $result;
            }

            return false;

        } catch (Exception $e) {
            error_log('[AdminUser CREATE] Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * READ - Get admin by ID
     * 
     * @param int $id Admin ID
     * @return array|false Admin data or false
     */
    public function getById($id) {
        try {
            $query = "SELECT id, username, email, full_name, role, is_active, created_at, last_login 
                      FROM `{$this->table}` 
                      WHERE id = ? 
                      LIMIT 1";

            return $this->db->selectOne($query, array($id));

        } catch (Exception $e) {
            error_log('[AdminUser READ] Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * READ - Get admin by username
     * 
     * @param string $username Admin username
     * @return array|false Admin data or false
     */
    public function getByUsername($username) {
        try {
            $query = "SELECT id, username, email, full_name, role, is_active, password_hash, created_at 
                      FROM `{$this->table}` 
                      WHERE username = ? 
                      LIMIT 1";

            return $this->db->selectOne($query, array($username));

        } catch (Exception $e) {
            error_log('[AdminUser READ] Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * READ - Get all admins (with pagination)
     * 
     * @param int $limit Results per page
     * @param int $offset Starting position
     * @param array $where Additional WHERE conditions
     * @return array|false Array of admins or false
     */
    public function getAll($limit = 50, $offset = 0, $where = array()) {
        try {
            $query = "SELECT id, username, email, full_name, role, is_active, created_at, last_login 
                      FROM `{$this->table}` WHERE 1=1";

            $params = array();

            // Add where conditions
            if (!empty($where)) {
                foreach ($where as $column => $value) {
                    $query .= " AND `" . $column . "` = ?";
                    $params[] = $value;
                }
            }

            $query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            return $this->db->select($query, $params);

        } catch (Exception $e) {
            error_log('[AdminUser GETALL] Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * UPDATE - Update admin data
     * 
     * @param int $id Admin ID
     * @param array $data Data to update
     * @return boolean Success or failure
     */
    public function update($id, $data) {
        try {
            // Don't allow updating username (primary identifier)
            unset($data['username']);
            unset($data['id']);

            if (empty($data)) {
                return false; // No data to update
            }

            $set_clauses = array();
            $params = array();

            foreach ($data as $column => $value) {
                // Hash password if provided
                if ($column === 'password') {
                    $value = password_hash($value, PASSWORD_BCRYPT, ['cost' => 12]);
                    $column = 'password_hash';
                }
                $set_clauses[] = "`" . $column . "` = ?";
                $params[] = $value;
            }

            $params[] = $id;

            $query = "UPDATE `{$this->table}` 
                      SET " . implode(",", $set_clauses) . ", updated_at = NOW() 
                      WHERE id = ? 
                      LIMIT 1";

            $result = $this->db->execute($query, $params);

            if ($result) {
                // Log the action
                $this->logAction('update', null, json_encode($data), 'Admin user updated');
                return true;
            }

            return false;

        } catch (Exception $e) {
            error_log('[AdminUser UPDATE] Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * DELETE - Soft delete or hard delete admin
     * 
     * @param int $id Admin ID
     * @param boolean $soft_delete Soft delete (disable) or hard delete
     * @return boolean Success or failure
     */
    public function delete($id, $soft_delete = true) {
        try {
            if ($soft_delete) {
                // Disable instead of delete
                $query = "UPDATE `{$this->table}` SET is_active = 0, updated_at = NOW() WHERE id = ? LIMIT 1";
                $result = $this->db->execute($query, array($id));
                
                if ($result) {
                    $this->logAction('delete', null, 'Soft delete - disabled', 'Admin user disabled');
                }
            } else {
                // Hard delete - only superadmin can do this
                if ($this->isAdminSuperadmin($this->current_admin_id)) {
                    $query = "DELETE FROM `{$this->table}` WHERE id = ? LIMIT 1";
                    $result = $this->db->execute($query, array($id));
                    
                    if ($result) {
                        $this->logAction('delete', null, 'Hard delete', 'Admin user deleted');
                    }
                } else {
                    return false; // Not authorized
                }
            }

            return $result ? true : false;

        } catch (Exception $e) {
            error_log('[AdminUser DELETE] Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify password for login
     * 
     * @param string $username Admin username
     * @param string $password Plain password
     * @return array|false Admin data if valid, false otherwise
     */
    public function verifyLogin($username, $password) {
        try {
            $admin = $this->getByUsername($username);

            if (!$admin) {
                return false; // User not found
            }

            if (!$admin['is_active']) {
                return false; // Account disabled
            }

            // Verify password
            if (!password_verify($password, $admin['password_hash'])) {
                // Log failed attempt
                $this->logFailedLogin($username);
                return false;
            }

            // Update last login
            $this->updateLastLogin($admin['id']);

            // Return safe admin data (without password hash)
            unset($admin['password_hash']);
            return $admin;

        } catch (Exception $e) {
            error_log('[AdminUser VERIFY] Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update last login timestamp
     * 
     * @param int $id Admin ID
     * @return boolean
     */
    public function updateLastLogin($id) {
        try {
            $query = "UPDATE `{$this->table}` SET last_login = NOW() WHERE id = ? LIMIT 1";
            return $this->db->execute($query, array($id)) ? true : false;
        } catch (Exception $e) {
            error_log('[AdminUser LASTLOGIN] Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if admin is superadmin
     * 
     * @param int $id Admin ID
     * @return boolean
     */
    public function isAdminSuperadmin($id) {
        try {
            $admin = $this->getById($id);
            return $admin && $admin['role'] === 'superadmin';
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Check admin permissions
     * 
     * @param int $admin_id Admin ID
     * @param string $permission Permission to check
     * @return boolean
     */
    public function hasPermission($admin_id, $permission) {
        try {
            $admin = $this->getById($admin_id);
            if (!$admin) {
                return false;
            }

            // Superadmin has all permissions
            if ($admin['role'] === 'superadmin') {
                return true;
            }

            // Define role permissions
            $permissions = array(
                'superadmin' => array('*'), // All
                'admin' => array('manage_routers', 'manage_users', 'view_reports', 'manage_vouchers'),
                'operator' => array('manage_users', 'view_reports', 'manage_vouchers'),
                'viewer' => array('view_reports')
            );

            if (isset($permissions[$admin['role']])) {
                return in_array($permission, $permissions[$admin['role']]) || 
                       in_array('*', $permissions[$admin['role']]);
            }

            return false;

        } catch (Exception $e) {
            error_log('[AdminUser PERMISSION] Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Log action to system logs
     * 
     * @param string $action Action type
     * @param string $old_value Old value
     * @param string $new_value New value
     * @param string $message Log message
     * @return void
     */
    private function logAction($action, $old_value, $new_value, $message) {
        try {
            $query = "INSERT INTO system_logs (admin_id, log_level, module, message, context) 
                      VALUES (?, 'INFO', 'admin_user', ?, ?)";

            $context = json_encode(array(
                'action' => $action,
                'old_value' => $old_value,
                'new_value' => $new_value
            ));

            $this->db->execute($query, array($this->current_admin_id, $message, $context));

        } catch (Exception $e) {
            error_log('[AdminUser LOGGING] Error: ' . $e->getMessage());
        }
    }

    /**
     * Log failed login attempt
     * 
     * @param string $username Username
     * @return void
     */
    private function logFailedLogin($username) {
        try {
            $query = "INSERT INTO system_logs (log_level, module, message, context, ip_address) 
                      VALUES ('WARNING', 'auth', 'Failed login attempt', ?, ?)";

            $context = json_encode(array('username' => $username));
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

            $this->db->execute($query, array($context, $ip));

        } catch (Exception $e) {
            error_log('[AdminUser FAILEDLOGIN] Error: ' . $e->getMessage());
        }
    }

    /**
     * Get total count of admins
     * 
     * @return int Total number of admins
     */
    public function getTotalCount() {
        try {
            return $this->db->count($this->table, array('is_active' => 1));
        } catch (Exception $e) {
            return 0;
        }
    }
}

?>

<?php
/*
 * Router.class.php
 * Router Management
 * CRUD operations for MikroTik router profiles
 * Each router belongs to one admin (multi-tenancy)
 */

require_once(dirname(__FILE__) . '/../lib/Database.class.php');

class Router {
    private $db = null;
    private $table = 'routers';
    private $current_admin_id = null;

    /**
     * Constructor
     * 
     * @param Database $database Database instance
     * @param int $current_admin_id Current admin ID (for data isolation)
     */
    public function __construct($database, $current_admin_id) {
        $this->db = $database;
        $this->current_admin_id = $current_admin_id;
    }

    /**
     * CREATE - Add new router
     * 
     * @param array $data Router data
     * @return int|false Router ID or false on error
     */
    public function create($data) {
        try {
            // Validate required fields
            if (empty($data['name']) || empty($data['ip_address']) || 
                empty($data['api_username']) || empty($data['api_password_encrypted']) ||
                empty($data['hotspot_name'])) {
                return false;
            }

            // Check for duplicate IP for this admin
            $existing = $this->db->selectOne(
                "SELECT id FROM `{$this->table}` WHERE admin_id = ? AND ip_address = ? LIMIT 1",
                array($this->current_admin_id, $data['ip_address'])
            );

            if ($existing) {
                return false; // Router already exists
            }

            $query = "INSERT INTO `{$this->table}` 
                      (admin_id, name, description, ip_address, api_port, api_username, 
                       api_password_encrypted, hotspot_name, dns_server, currency, 
                       interface_name, idle_timeout, reload_interval, max_concurrent_users, 
                       is_active, created_by) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $params = array(
                $this->current_admin_id,
                $data['name'],
                isset($data['description']) ? $data['description'] : '',
                $data['ip_address'],
                isset($data['api_port']) ? $data['api_port'] : 8728,
                $data['api_username'],
                $data['api_password_encrypted'],
                $data['hotspot_name'],
                isset($data['dns_server']) ? $data['dns_server'] : '8.8.8.8',
                isset($data['currency']) ? $data['currency'] : 'USD',
                isset($data['interface_name']) ? $data['interface_name'] : 'ether2',
                isset($data['idle_timeout']) ? $data['idle_timeout'] : 600,
                isset($data['reload_interval']) ? $data['reload_interval'] : 30,
                isset($data['max_concurrent_users']) ? $data['max_concurrent_users'] : 0,
                isset($data['is_active']) ? $data['is_active'] : 1,
                $this->current_admin_id
            );

            $result = $this->db->execute($query, $params);

            if ($result) {
                $this->logAction($result, 'create', null, json_encode($data), 'Router created');
            }

            return $result;

        } catch (Exception $e) {
            error_log('[Router CREATE] Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * READ - Get router by ID (with admin isolation)
     * 
     * @param int $id Router ID
     * @return array|false Router data or false
     */
    public function getById($id) {
        try {
            $query = "SELECT * FROM `{$this->table}` 
                      WHERE id = ? AND admin_id = ? 
                      LIMIT 1";

            return $this->db->selectOne($query, array($id, $this->current_admin_id));

        } catch (Exception $e) {
            error_log('[Router READ] Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * READ - Get router by IP address
     * 
     * @param string $ip_address Router IP address
     * @return array|false Router data or false
     */
    public function getByIp($ip_address) {
        try {
            $query = "SELECT * FROM `{$this->table}` 
                      WHERE ip_address = ? AND admin_id = ? 
                      LIMIT 1";

            return $this->db->selectOne($query, array($ip_address, $this->current_admin_id));

        } catch (Exception $e) {
            error_log('[Router GETBYIP] Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * READ - Get all routers for current admin
     * 
     * @param int $limit Results per page
     * @param int $offset Starting position
     * @param array $filters Additional filters (only_active, etc)
     * @return array|false Array of routers or false
     */
    public function getAll($limit = 50, $offset = 0, $filters = array()) {
        try {
            $query = "SELECT id, admin_id, name, description, ip_address, api_port, 
                             hotspot_name, currency, interface_name, is_active, 
                             connection_status, last_connected, created_at, updated_at
                      FROM `{$this->table}` 
                      WHERE admin_id = ?";

            $params = array($this->current_admin_id);

            // Apply filters
            if (isset($filters['only_active']) && $filters['only_active']) {
                $query .= " AND is_active = ?";
                $params[] = 1;
            }

            $query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            return $this->db->select($query, $params);

        } catch (Exception $e) {
            error_log('[Router GETALL] Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * UPDATE - Update router configuration
     * 
     * @param int $id Router ID
     * @param array $data Data to update
     * @return boolean Success or failure
     */
    public function update($id, $data) {
        try {
            // Verify ownership
            $router = $this->getById($id);
            if (!$router) {
                return false;
            }

            // Don't allow changing admin
            unset($data['admin_id']);
            unset($data['id']);

            if (empty($data)) {
                return false;
            }

            $set_clauses = array();
            $params = array();

            foreach ($data as $column => $value) {
                $set_clauses[] = "`" . $column . "` = ?";
                $params[] = $value;
            }

            $params[] = $this->current_admin_id;
            $params[] = $id;

            $query = "UPDATE `{$this->table}` 
                      SET " . implode(",", $set_clauses) . ", updated_at = NOW(), updated_by = ? 
                      WHERE admin_id = ? AND id = ? 
                      LIMIT 1";

            $result = $this->db->execute($query, $params);

            if ($result) {
                $this->logAction($id, 'update', json_encode($router), json_encode($data), 'Router updated');
            }

            return $result ? true : false;

        } catch (Exception $e) {
            error_log('[Router UPDATE] Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * DELETE - Delete router
     * 
     * @param int $id Router ID
     * @return boolean Success or failure
     */
    public function delete($id) {
        try {
            // Verify ownership
            $router = $this->getById($id);
            if (!$router) {
                return false;
            }

            // Use soft delete - disable instead
            $query = "UPDATE `{$this->table}` 
                      SET is_active = 0, updated_at = NOW() 
                      WHERE id = ? AND admin_id = ? 
                      LIMIT 1";

            $result = $this->db->execute($query, array($id, $this->current_admin_id));

            if ($result) {
                $this->logAction($id, 'delete', json_encode($router), null, 'Router deleted/disabled');
            }

            return $result ? true : false;

        } catch (Exception $e) {
            error_log('[Router DELETE] Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update router connection status
     * 
     * @param int $id Router ID
     * @param string $status Connection status (connected/disconnected/error)
     * @param string $error_msg Error message if applicable
     * @return boolean
     */
    public function updateConnectionStatus($id, $status, $error_msg = null) {
        try {
            if ($status === 'connected') {
                $query = "UPDATE `{$this->table}` 
                          SET connection_status = ?, last_connected = NOW() 
                          WHERE id = ? AND admin_id = ? 
                          LIMIT 1";
                $params = array('connected', $id, $this->current_admin_id);
            } else {
                $query = "UPDATE `{$this->table}` 
                          SET connection_status = ? 
                          WHERE id = ? AND admin_id = ? 
                          LIMIT 1";
                $params = array($status, $id, $this->current_admin_id);
            }

            $result = $this->db->execute($query, $params);

            if ($result && $error_msg) {
                $this->logAction($id, 'connection_status', $error_msg, $status, 'Connection status updated');
            }

            return $result ? true : false;

        } catch (Exception $e) {
            error_log('[Router CONNECTIONSTATUS] Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get total count of routers for admin
     * 
     * @param boolean $only_active Get only active routers
     * @return int Total count
     */
    public function getTotalCount($only_active = false) {
        try {
            $where = array('admin_id' => $this->current_admin_id);
            if ($only_active) {
                $where['is_active'] = 1;
            }
            return $this->db->count($this->table, $where);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Log action to system logs
     * 
     * @param int $router_id Router ID
     * @param string $action Action type
     * @param string $old_value Old value
     * @param string $new_value New value
     * @param string $message Log message
     * @return void
     */
    private function logAction($router_id, $action, $old_value, $new_value, $message) {
        try {
            $query = "INSERT INTO user_logs (router_id, admin_id, action, action_type, old_value, new_value, performed_by, details) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            $params = array(
                $router_id,
                $this->current_admin_id,
                $action,
                $action,
                $old_value,
                $new_value,
                $this->current_admin_id,
                $message
            );

            $this->db->execute($query, $params);

        } catch (Exception $e) {
            error_log('[Router LOGGING] Error: ' . $e->getMessage());
        }
    }
}

?>

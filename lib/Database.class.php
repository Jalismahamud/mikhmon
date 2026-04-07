<?php
/*
 * Database.class.php
 * Database Abstraction Layer
 * PDO-based database operations for Mikhmon
 * 
 * Provides secure, prepared statement-based database access
 * Follows current project coding patterns
 */

class Database {
    private $connection = null;
    private $statement = null;
    private $debug = false;
    private $lastError = null;
    private $lastInsertId = null;
    private $affectedRows = 0;

    /**
     * Constructor - Establish database connection
     */
    public function __construct() {
        $this->connect();
        if (DB_DEBUG) {
            $this->debug = true;
        }
    }

    /**
     * Connect to MySQL database using PDO
     * 
     * @return boolean Connection status
     */
    public function connect() {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            
            $this->connection = new PDO(
                $dsn,
                DB_USER,
                DB_PASS,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_PERSISTENT => false
                )
            );

            // Set charset
            $this->connection->exec("SET NAMES " . DB_CHARSET);

            if ($this->debug) {
                error_log('[MIKHMON DB] Connected to ' . DB_NAME);
            }

            return true;

        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            $this->logError('Connection Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Execute SELECT query - returns multiple rows
     * 
     * @param string $query SQL SELECT query with ? placeholders
     * @param array $params Parameters to bind
     * @return array|false Array of rows or false on error
     */
    public function select($query, $params = array()) {
        try {
            $this->statement = $this->connection->prepare($query);
            
            if (!$this->statement->execute($params)) {
                $this->lastError = $this->statement->errorInfo();
                return false;
            }

            $result = $this->statement->fetchAll(PDO::FETCH_ASSOC);
            $this->affectedRows = count($result);

            if ($this->debug) {
                error_log('[MIKHMON DB SELECT] Query: ' . $query . ' | Rows: ' . count($result));
            }

            return $result;

        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            $this->logError('SELECT Error: ' . $e->getMessage() . ' | Query: ' . $query);
            return false;
        }
    }

    /**
     * Execute SELECT query - returns single row
     * 
     * @param string $query SQL SELECT query with ? placeholders
     * @param array $params Parameters to bind
     * @return array|false Single row as array or false
     */
    public function selectOne($query, $params = array()) {
        try {
            $this->statement = $this->connection->prepare($query);
            
            if (!$this->statement->execute($params)) {
                $this->lastError = $this->statement->errorInfo();
                return false;
            }

            $result = $this->statement->fetch(PDO::FETCH_ASSOC);
            $this->affectedRows = $result ? 1 : 0;

            if ($this->debug && $result) {
                error_log('[MIKHMON DB SELECTONE] Found record');
            }

            return $result ? $result : false;

        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            $this->logError('SELECTONE Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Execute INSERT/UPDATE/DELETE query
     * 
     * @param string $query SQL query with ? placeholders
     * @param array $params Parameters to bind
     * @return int|boolean Last insert ID (for INSERT) or true (for UPDATE/DELETE), false on error
     */
    public function execute($query, $params = array()) {
        try {
            $this->statement = $this->connection->prepare($query);
            
            if (!$this->statement->execute($params)) {
                $this->lastError = $this->statement->errorInfo();
                return false;
            }

            $this->affectedRows = $this->statement->rowCount();
            $this->lastInsertId = $this->connection->lastInsertId();

            if ($this->debug) {
                error_log('[MIKHMON DB EXECUTE] Affected rows: ' . $this->affectedRows);
            }

            // Return insertId for INSERT, true for UPDATE/DELETE
            return ($this->lastInsertId && $this->lastInsertId > 0) ? $this->lastInsertId : true;

        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            $this->logError('EXECUTE Error: ' . $e->getMessage() . ' | Query: ' . $query);
            return false;
        }
    }

    /**
     * Count rows in table with conditions
     * 
     * @param string $table Table name
     * @param array $where WHERE conditions (column => value)
     * @return int|false Row count or false on error
     */
    public function count($table, $where = array()) {
        try {
            $query = "SELECT COUNT(*) as total FROM `" . $table . "`";
            $params = array();

            if (!empty($where)) {
                $query .= " WHERE ";
                $conditions = array();
                foreach ($where as $column => $value) {
                    $conditions[] = "`" . $column . "` = ?";
                    $params[] = $value;
                }
                $query .= implode(" AND ", $conditions);
            }

            $result = $this->selectOne($query, $params);
            return $result ? $result['total'] : 0;

        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Get number of affected rows from last operation
     * 
     * @return int Number of affected rows
     */
    public function affectedRows() {
        return $this->affectedRows;
    }

    /**
     * Get last insert ID
     * 
     * @return int|string Last inserted ID
     */
    public function insertId() {
        return $this->lastInsertId;
    }

    /**
     * Get last error message
     * 
     * @return string|null Last error or null
     */
    public function getLastError() {
        return $this->lastError;
    }

    /**
     * Start database transaction
     * 
     * @return boolean
     */
    public function beginTransaction() {
        try {
            $this->connection->beginTransaction();
            if ($this->debug) {
                error_log('[MIKHMON DB] Transaction begun');
            }
            return true;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Commit database transaction
     * 
     * @return boolean
     */
    public function commit() {
        try {
            $this->connection->commit();
            if ($this->debug) {
                error_log('[MIKHMON DB] Transaction committed');
            }
            return true;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Rollback database transaction
     * 
     * @return boolean
     */
    public function rollBack() {
        try {
            $this->connection->rollBack();
            if ($this->debug) {
                error_log('[MIKHMON DB] Transaction rolled back');
            }
            return true;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Escape string for safe SQL usage
     * Note: Prepared statements should be used instead
     * 
     * @param string $string String to escape
     * @return string Escaped string
     */
    public function escape($string) {
        return $this->connection->quote($string);
    }

    /**
     * Log error to file
     * 
     * @param string $message Error message
     * @return void
     */
    private function logError($message) {
        if (LOG_ERRORS && is_dir(LOG_PATH)) {
            $log_file = LOG_PATH . '/error_' . date('Y-m-d') . '.log';
            $timestamp = date('Y-m-d H:i:s');
            error_log("[{$timestamp}] {$message}\n", 3, $log_file);
        }
    }

    /**
     * Check if database connection is active
     * 
     * @return boolean
     */
    public function isConnected() {
        return $this->connection !== null;
    }

    /**
     * Close database connection
     * 
     * @return void
     */
    public function closeConnection() {
        $this->connection = null;
        if ($this->debug) {
            error_log('[MIKHMON DB] Connection closed');
        }
    }

    /**
     * Destructor - ensure connection is closed
     */
    public function __destruct() {
        $this->closeConnection();
    }
}

// Auto-require database config if not already loaded
if (!defined('DB_HOST')) {
    require_once(dirname(__FILE__) . '/db_config.php');
}
?>

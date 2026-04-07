#!/usr/bin/env php
<?php
/*
 * Database Setup & Seeding Script
 * Run this script to initialize the database and populate with test data
 * 
 * Usage: php database/setup.php [command]
 * Commands: migrate, seed, refresh, clear, status
 */

// Set working directory
chdir(dirname(__DIR__));

// Include configurations
require_once('./include/db_config.php');
require_once('./lib/Database.class.php');
require_once('./lib/DatabaseSeeder.class.php');

class DatabaseSetup {
    private $db = null;
    private $migrations_path = './database/migrations/';

    /**
     * Constructor
     */
    public function __construct() {
        echo "\n";
        echo "╔════════════════════════════════════════╗\n";
        echo "║  MIKHMON DATABASE SETUP & SEEDING      ║\n";
        echo "║  MikroTik Hotspot Management System   ║\n";
        echo "╚════════════════════════════════════════╝\n\n";

        $this->db = new Database();
        
        if (!$this->db->isConnected()) {
            die("[ERROR] Failed to connect to database: " . $this->db->getLastError() . "\n\n");
        }

        echo "[OK] Connected to database: " . DB_NAME . "\n\n";
    }

    /**
     * Run migrations - create database schema
     */
    public function migrate() {
        echo "Running migrations...\n";
        echo str_repeat("-", 40) . "\n";

        // Check if migrations path exists
        if (!is_dir($this->migrations_path)) {
            mkdir($this->migrations_path, 0755, true);
        }

        // Get all SQL files
        $migration_files = glob($this->migrations_path . '*.sql');
        
        if (empty($migration_files)) {
            echo "[ERROR] No migration files found in {$this->migrations_path}\n";
            return false;
        }

        sort($migration_files);

        try {
            foreach ($migration_files as $file) {
                echo "\nExecuting: " . basename($file) . "\n";
                
                $sql = file_get_contents($file);
                
                // Split SQL commands by semicolon
                $statements = array_filter(array_map('trim', explode(';', $sql)));
                
                foreach ($statements as $statement) {
                    if (!empty($statement)) {
                        // Skip comments
                        if (substr(trim($statement), 0, 2) === '--' || substr(trim($statement), 0, 1) === '#') {
                            continue;
                        }

                        // Execute statement
                        try {
                            $this->db->execute($statement . ';');
                        } catch (Exception $e) {
                            // Some statements might be comments or info, continue
                            if (strpos($statement, 'SELECT') === false) {
                                echo "  → " . substr($statement, 0, 50) . "...\n";
                            }
                        }
                    }
                }
            }

            echo "\n[OK] All migrations executed successfully!\n";
            return true;

        } catch (Exception $e) {
            echo "[ERROR] Migration failed: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * Seed database with test data
     */
    public function seed() {
        echo "\nSeeding database with test data...\n";
        echo str_repeat("-", 40) . "\n";

        $seeder = new DatabaseSeeder();
        $result = $seeder->runAll();

        return $result ? true : false;
    }

    /**
     * Refresh - drop all tables and recreate
     */
    public function refresh() {
        echo "\nRefreshing database...\n";
        echo str_repeat("-", 40) . "\n";

        try {
            echo "Dropping tables...\n";
            
            $tables = array(
                'system_logs',
                'transaction_history',
                'user_logs',
                'vouchers',
                'routers',
                'admin_users'
            );

            foreach ($tables as $table) {
                $this->db->execute("DROP TABLE IF EXISTS `{$table}`");
                echo "  ✓ Dropped {$table}\n";
            }

            echo "\nRecreating tables...\n";
            $this->migrate();

            echo "\nPopulating with seed data...\n";
            $this->seed();

            return true;

        } catch (Exception $e) {
            echo "[ERROR] Refresh failed: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * Clear all seeded data (keep structure)
     */
    public function clear() {
        echo "\nClearing all seeded data...\n";
        echo str_repeat("-", 40) . "\n";

        $seeder = new DatabaseSeeder();
        return $seeder->clear();
    }

    /**
     * Show database status
     */
    public function status() {
        echo "\nDatabase Status:\n";
        echo str_repeat("-", 40) . "\n";

        try {
            // Get table information
            $tables = array(
                'admin_users' => 'Admin Users',
                'routers' => 'Router Configurations',
                'vouchers' => 'Vouchers',
                'user_logs' => 'User Activity Logs',
                'transaction_history' => 'Transactions',
                'system_logs' => 'System Logs'
            );

            echo "\nTable Status:\n";
            foreach ($tables as $table_name => $label) {
                $count = $this->db->count($table_name);
                echo "  ✓ {$label}: {$count} records\n";
            }

            // Get admin information
            echo "\nAdmin Users:\n";
            $admins = $this->db->select("SELECT id, username, email, role, is_active FROM admin_users ORDER BY role DESC");
            
            if ($admins) {
                foreach ($admins as $admin) {
                    $status = $admin['is_active'] ? '✓ Active' : '✗ Inactive';
                    echo "  {$status} | {$admin['username']} ({$admin['role']}) - {$admin['email']}\n";
                }
            }

            // Get router information
            echo "\nRouters per Admin:\n";
            $router_counts = $this->db->select("
                SELECT 
                    au.username, 
                    COUNT(r.id) as router_count,
                    SUM(CASE WHEN r.is_active = 1 THEN 1 ELSE 0 END) as active_count
                FROM admin_users au
                LEFT JOIN routers r ON au.id = r.admin_id
                WHERE au.role != 'superadmin'
                GROUP BY au.id
            ");

            if ($router_counts) {
                foreach ($router_counts as $row) {
                    echo "  {$row['username']}: {$row['router_count']} routers ({$row['active_count']} active)\n";
                }
            }

            echo "\n";
            return true;

        } catch (Exception $e) {
            echo "[ERROR] Failed to get status: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * Show help
     */
    public function help() {
        echo "Available commands:\n\n";
        echo "  migrate       Run all migrations to create database schema\n";
        echo "  seed          Populate database with test data\n";
        echo "  refresh       Drop all tables and recreate (migrate + seed)\n";
        echo "  clear         Remove all seeded data but keep schema\n";
        echo "  status        Show database status and information\n";
        echo "  help          Show this help message\n\n";
        echo "Examples:\n";
        echo "  php database/setup.php migrate\n";
        echo "  php database/setup.php seed\n";
        echo "  php database/setup.php refresh\n\n";
    }

    /**
     * Run the setup
     */
    public function run($command = '') {
        $command = strtolower(trim($command));

        switch ($command) {
            case 'migrate':
                return $this->migrate();
            case 'seed':
                return $this->seed();
            case 'refresh':
                return $this->refresh();
            case 'clear':
                return $this->clear();
            case 'status':
                return $this->status();
            case 'help':
                $this->help();
                return true;
            default:
                if (!empty($command)) {
                    echo "[ERROR] Unknown command: {$command}\n";
                }
                $this->help();
                return false;
        }
    }
}

// Get command from CLI arguments
$command = isset($argv[1]) ? $argv[1] : 'help';

// Run setup
$setup = new DatabaseSetup();
$result = $setup->run($command);

// Exit with appropriate code
exit($result ? 0 : 1);
?>

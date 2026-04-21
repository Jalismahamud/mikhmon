<?php
/*
 * DatabaseSeeder.class.php
 * Database seeding with realistic test data
 * Creates admin users, routers, vouchers, and logs
 */

require_once(dirname(__FILE__) . '/../lib/Database.class.php');
require_once(dirname(__FILE__) . '/../lib/AdminUser.class.php');
require_once(dirname(__FILE__) . '/../lib/Router.class.php');

class DatabaseSeeder {
    private $db = null;
    private $faker_data = array();

    /**
     * Constructor
     */
    public function __construct() {
        $this->db = new Database();
        if (!$this->db->isConnected()) {
            die('Failed to connect to database');
        }
        $this->initializeFakerData();
    }

    /**
     * Initialize fake data for seeding
     */
    private function initializeFakerData() {
        $this->faker_data = array(
            'first_names' => array(
                'Mohammad', 'Ahmed', 'Fatema', 'Karim', 'Aisha', 'Hassan',
                'Zumaira', 'Rahman', 'Noor', 'Ravi', 'Priya', 'Amit'
            ),
            'last_names' => array(
                'Khan', 'Hassan', 'Ahmed', 'Ali', 'Hussain', 'Ibrahim',
                'Malik', 'Ahmed', 'Sheikh', 'Rana', 'Gupta', 'Verma'
            ),
            'companies' => array(
                'Coffee Shop', 'Internet Cafe', 'Hotel', 'Restaurant',
                'Shopping Mall', 'School', 'University', 'Training Center'
            ),
            'profiles' => array(
                array('name' => '1-Hour', 'price' => 0.5),
                array('name' => 'Daily', 'price' => 1.0),
                array('name' => 'Weekly', 'price' => 5.0),
                array('name' => 'Monthly', 'price' => 15.0),
                array('name' => 'Unlimited', 'price' => 25.0)
            ),
            'payment_methods' => array('Cash', 'Card', 'Mobile', 'Check'),
            'transaction_types' => array('voucher_sale', 'credit_sale', 'refund', 'user_add')
        );
    }

    /**
     * Run all seeders
     * 
     * @return array Results summary
     */
    public function runAll() {
        echo "\n========== MIKHMON DATABASE SEEDER ==========\n";
        
        try {
            // Start transaction
            $this->db->beginTransaction();

            // Seed admins with routers
            $results['admins'] = $this->seedAdmins();
            echo "✓ Seeded " . $results['admins'] . " admin users\n";

            // Seed routers for each admin
            $results['routers'] = $this->seedRouters();
            echo "✓ Seeded " . $results['routers'] . " routers\n";

            // Seed vouchers
            $results['vouchers'] = $this->seedVouchers();
            echo "✓ Seeded " . $results['vouchers'] . " vouchers\n";

            // Seed user logs
            $results['logs'] = $this->seedUserLogs();
            echo "✓ Seeded " . $results['logs'] . " user activity logs\n";

            // Seed transaction history
            $results['transactions'] = $this->seedTransactions();
            echo "✓ Seeded " . $results['transactions'] . " transactions\n";

            // Commit transaction
            $this->db->commit();

            echo "\n========== SEEDING COMPLETE ==========\n";
            echo "Summary:\n";
            echo json_encode($results, JSON_PRETTY_PRINT);
            echo "\n\n";

            return $results;

        } catch (Exception $e) {
            $this->db->rollBack();
            echo "✗ Seeding failed: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * Seed admin users
     * 
     * @return int Count of created admins
     */
    private function seedAdmins() {
        $count = 0;

        // Clear existing data
        //$this->db->execute("DELETE FROM admin_users WHERE role != 'superadmin'");

        $admins = array(
            // Primary admin account
            array(
                'username' => 'admin',
                'password' => '12345678',
                'email' => 'admin@mikhmon.local',
                'full_name' => 'Administrator',
                'role' => 'admin'
            ),
            
            // Additional test admins
            array(
                'username' => 'admin_bd',
                'password' => 'Test@123456',
                'email' => 'admin.bangladesh@mikhmon.local',
                'full_name' => 'Bangladesh Admin',
                'role' => 'admin'
            ),
            array(
                'username' => 'admin_dhaka',
                'password' => 'Test@123456',
                'email' => 'dhaka.admin@mikhmon.local',
                'full_name' => 'Dhaka Zone Manager',
                'role' => 'admin'
            ),
            array(
                'username' => 'operator_dhaka',
                'password' => 'Test@123456',
                'email' => 'operator.dhaka@mikhmon.local',
                'full_name' => 'Dhaka Operator',
                'role' => 'operator'
            ),
            array(
                'username' => 'viewer_report',
                'password' => 'Test@123456',
                'email' => 'viewer@mikhmon.local',
                'full_name' => 'Report Viewer',
                'role' => 'viewer'
            )
        );

        foreach ($admins as $admin) {
            $adminUser = new AdminUser($this->db, 1); // Super admin creates
            
            $result = $adminUser->create($admin);
            if ($result) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Seed router configurations
     * 
     * @return int Count of created routers
     */
    private function seedRouters() {
        $count = 0;

        // Get all non-superadmin admins
        $adminUser = new AdminUser($this->db, 1);
        $admins = $this->db->select("SELECT id FROM admin_users WHERE role != 'superadmin' AND is_active = 1");

        if (!$admins) {
            return 0;
        }

        $base_ips = array('192.168', '10.0', '172.16');
        $router_count = 0;

        foreach ($admins as $admin) {
            $router = new Router($this->db, $admin['id']);

            // Create 2-3 routers per admin
            $router_per_admin = rand(2, 3);

            for ($i = 0; $i < $router_per_admin; $i++) {
                $company = $this->faker_data['companies'][array_rand($this->faker_data['companies'])];
                
                $router_data = array(
                    'name' => $company . ' Router ' . ($i + 1),
                    'description' => 'MikroTik RouterOS at ' . $company,
                    'ip_address' => $base_ips[$router_count % 3] . '.' . ($i + 1) . '.' . (10 + $admin['id']),
                    'api_port' => 8728,
                    'api_username' => 'admin',
                    'api_password_encrypted' => $this->encryptPassword('admin123'),
                    'hotspot_name' => 'HS_' . strtoupper(substr($company, 0, 3)) . rand(100, 999),
                    'dns_server' => '8.8.8.8',
                    'currency' => 'USD',
                    'interface_name' => 'ether2',
                    'idle_timeout' => 1800,
                    'reload_interval' => 30,
                    'max_concurrent_users' => rand(50, 200),
                    'is_active' => 1
                );

                if ($router->create($router_data)) {
                    $count++;
                    $router_count++;
                }
            }
        }

        return $count;
    }

    /**
     * Seed vouchers
     * 
     * @return int Count of created vouchers
     */
    private function seedVouchers() {
        $count = 0;

        $routers = $this->db->select("SELECT id, admin_id, currency FROM routers WHERE is_active = 1");

        if (!$routers) {
            return 0;
        }

        $voucher_types = array('active', 'used', 'expired');
        $statuses = array(0 => 'active', 1 => 'used', 2 => 'expired');

        foreach ($routers as $router) {
            // Create 10-20 vouchers per router
            $voucher_count = rand(10, 20);

            for ($i = 0; $i < $voucher_count; $i++) {
                $status = $statuses[array_rand($statuses)];
                $profile = $this->faker_data['profiles'][array_rand($this->faker_data['profiles'])];

                $username = 'user_' . strtoupper(substr(md5(time() . rand()), 0, 6));
                $password = substr(md5(time() . rand()), 0, 8);

                $query = "INSERT INTO vouchers 
                         (router_id, admin_id, voucher_code, username, password_plain, 
                          profile_name, price, currency, status, created_by, created_at) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

                $voucher_code = strtoupper(substr(md5(time() . rand()), 0, 8));

                $params = array(
                    $router['id'],
                    $router['admin_id'],
                    $voucher_code,
                    $username,
                    $password,
                    $profile['name'],
                    $profile['price'],
                    $router['currency'],
                    $status,
                    $router['admin_id']
                );

                if ($this->db->execute($query, $params)) {
                    $count++;

                    // If used, add usage info
                    if ($status === 'used') {
                        $used_by = $this->faker_data['first_names'][array_rand($this->faker_data['first_names'])];
                        $this->db->execute(
                            "UPDATE vouchers SET used_by = ?, used_at = ? WHERE voucher_code = ?",
                            array($used_by, date('Y-m-d H:i:s', time() - rand(86400, 2592000)), $voucher_code)
                        );
                    }
                }
            }
        }

        return $count;
    }

    /**
     * Seed user activity logs
     * 
     * @return int Count of created logs
     */
    private function seedUserLogs() {
        $count = 0;

        $routers = $this->db->select("SELECT id, admin_id FROM routers WHERE is_active = 1");

        if (!$routers) {
            return 0;
        }

        $actions = array('create', 'update', 'delete', 'enable', 'disable', 'reset', 'login', 'logout');
        $statuses = array('success', 'success', 'success', 'error', 'warning');

        foreach ($routers as $router) {
            // Create 20-40 logs per router
            $log_count = rand(20, 40);

            for ($i = 0; $i < $log_count; $i++) {
                $action = $actions[array_rand($actions)];
                $status = $statuses[array_rand($statuses)];
                $username = 'user_' . rand(100, 999);

                $query = "INSERT INTO user_logs 
                         (router_id, admin_id, username, action, action_type, status, 
                          details, performed_by, timestamp) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $timestamp = date('Y-m-d H:i:s', time() - rand(3600, 604800)); // Last 7 days

                $params = array(
                    $router['id'],
                    $router['admin_id'],
                    $username,
                    $action,
                    $action,
                    $status,
                    'Test log entry for ' . $action,
                    $router['admin_id'],
                    $timestamp
                );

                if ($this->db->execute($query, $params)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Seed transaction history
     * 
     * @return int Count of created transactions
     */
    private function seedTransactions() {
        $count = 0;

        $routers = $this->db->select("SELECT id, admin_id, currency FROM routers WHERE is_active = 1");

        if (!$routers) {
            return 0;
        }

        foreach ($routers as $router) {
            // Create 15-30 transactions per router
            $transaction_count = rand(15, 30);

            for ($i = 0; $i < $transaction_count; $i++) {
                $type = $this->faker_data['transaction_types'][array_rand($this->faker_data['transaction_types'])];
                $profile = $this->faker_data['profiles'][array_rand($this->faker_data['profiles'])];
                $customer = $this->faker_data['first_names'][array_rand($this->faker_data['first_names'])] . ' ' .
                           $this->faker_data['last_names'][array_rand($this->faker_data['last_names'])];

                $query = "INSERT INTO transaction_history 
                         (router_id, admin_id, transaction_type, reference_id, description, 
                          amount, currency, payment_method, payment_status, 
                          customer_name, created_by, created_at) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $params = array(
                    $router['id'],
                    $router['admin_id'],
                    $type,
                    'REF_' . strtoupper(substr(md5(time() . rand()), 0, 8)),
                    $type . ' - ' . $profile['name'] . ' profile',
                    $profile['price'] + rand(-5, 10),
                    $router['currency'],
                    $this->faker_data['payment_methods'][array_rand($this->faker_data['payment_methods'])],
                    'completed',
                    $customer,
                    $router['admin_id'],
                    date('Y-m-d H:i:s', time() - rand(3600, 2592000))
                );

                if ($this->db->execute($query, $params)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Encrypt password (simple base64 for now, should use proper encryption)
     * 
     * @param string $password Plain password
     * @return string Encrypted password
     */
    private function encryptPassword($password) {
        // TODO: Implement proper encryption
        return base64_encode($password);
    }

    /**
     * Clear all seeded data
     * 
     * @return boolean
     */
    public function clear() {
        echo "\nClearing all seeded data...\n";

        try {
            $this->db->beginTransaction();

            // Delete in order (respect foreign keys)
            $this->db->execute("DELETE FROM system_logs");
            $this->db->execute("DELETE FROM transaction_history");
            $this->db->execute("DELETE FROM user_logs");
            $this->db->execute("DELETE FROM vouchers");
            $this->db->execute("DELETE FROM routers");
            $this->db->execute("DELETE FROM admin_users WHERE role != 'superadmin'");

            $this->db->commit();

            echo "✓ All seeded data cleared\n";
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            echo "✗ Failed to clear data: " . $e->getMessage() . "\n";
            return false;
        }
    }
}

?>

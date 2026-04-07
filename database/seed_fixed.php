<?php
/*
 * IMPROVED Database Seeder with Better Error Handling
 * Creates test admins, routers, vouchers, and logs
 */

echo "\n╔═══════════════════════════════════════════════════════════╗\n";
echo "║  MIKHMON DATABASE SEEDER (IMPROVED)                      ║\n";
echo "║  Creating Test Data for Multi-Admin System               ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

require_once('./include/db_config.php');

// Direct PDO connection
try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    
    $pdo = new PDO(
        $dsn,
        DB_USER,
        DB_PASS,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        )
    );
    
    echo "[✓] Connected to database: " . DB_NAME . "\n\n";
    
} catch (PDOException $e) {
    die("[✗] Connection failed: " . $e->getMessage() . "\n\n");
}

$created_stats = array(
    'routers' => 0,
    'vouchers' => 0,
    'user_logs' => 0,
    'transactions' => 0
);

// ========================================
// 1. CREATE TEST ROUTERS FOR EACH ADMIN
// ========================================
echo "Creating test routers...\n";
echo str_repeat("─", 60) . "\n\n";

$router_templates = array(
    array(
        'name' => 'Main Office Router',
        'ip' => '192.168.1.1',
        'hotspot' => 'HS_OFFICE',
        'desc' => 'Primary office hotspot',
        'interface' => 'ether2'
    ),
    array(
        'name' => 'Cafe WiFi Router',
        'ip' => '10.0.0.1',
        'hotspot' => 'HS_CAFE',
        'desc' => 'Cafe hotspot network',
        'interface' => 'ether2'
    ),
    array(
        'name' => 'Hotel Lobby Router',
        'ip' => '172.16.0.1',
        'hotspot' => 'HS_HOTEL',
        'desc' => 'Hotel lobby hotspot',
        'interface' => 'ether3'
    ),
    array(
        'name' => 'Training Center Router',
        'ip' => '192.168.2.1',
        'hotspot' => 'HS_TRAINING',
        'desc' => 'Training center hotspot',
        'interface' => 'ether2'
    )
);

// Get all admins
$stmt = $pdo->query("SELECT id, username FROM admin_users WHERE id > 1 ORDER BY id");
$admins = $stmt->fetchAll();

foreach ($admins as $admin) {
    // Create 2-3 routers per admin
    $num_routers = rand(2, 3);
    
    for ($i = 0; $i < $num_routers; $i++) {
        $template = $router_templates[$created_stats['routers'] % count($router_templates)];
        
        try {
            $query = "INSERT INTO routers 
                      (admin_id, name, description, ip_address, api_port, api_username, 
                       api_password_encrypted, hotspot_name, dns_server, currency, 
                       interface_name, idle_timeout, max_concurrent_users, is_active, created_by) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute(array(
                $admin['id'],
                $template['name'] . ' [' . $admin['username'] . ']',
                $template['desc'],
                $template['ip'],
                8728,
                'admin',
                base64_encode('password123'),
                $template['hotspot'],
                '8.8.8.8',
                'USD',
                $template['interface'],
                1800,
                100,
                1,
                $admin['id']  // created_by
            ));
            
            $created_stats['routers']++;
            echo "  [" . $created_stats['routers'] . "] ✓ Router for " . $admin['username'] . ": " . $template['name'] . "\n";
            
        } catch (Exception $e) {
            echo "  [✗] Failed to create router: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n";

// ========================================
// 2. CREATE TEST VOUCHERS
// ========================================
echo "Creating test vouchers...\n";
echo str_repeat("─", 60) . "\n\n";

$profiles = array('1-Hour', 'Daily (24h)', 'Weekly (7d)', 'Monthly (30d)', 'Unlimited');

// Get all routers
$stmt = $pdo->query("SELECT id, admin_id FROM routers ORDER BY admin_id");
$routers = $stmt->fetchAll();

$voucher_count = 0;
foreach ($routers as $router) {
    // Create 10-20 vouchers per router
    $num_vouchers = rand(10, 20);
    
    for ($i = 0; $i < $num_vouchers; $i++) {
        try {
            $profile = $profiles[array_rand($profiles)];
            $code = 'V' . strtoupper(bin2hex(random_bytes(4)));
            $price = rand(1, 25);
            
            $query = "INSERT INTO vouchers 
                      (admin_id, router_id, voucher_code, username, profile_name, 
                       price, currency, created_by) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute(array(
                $router['admin_id'],
                $router['id'],
                $code,
                'user_' . substr($code, 1),
                $profile,
                $price,
                'USD',
                $router['admin_id']  // created_by
            ));
            
            $created_stats['vouchers']++;
            
        } catch (Exception $e) {
            // Silently skip duplicate codes
        }
    }
}

echo "  ✓ Created " . $created_stats['vouchers'] . " test vouchers\n\n";

// ========================================
// 3. CREATE TEST USER ACTIVITY LOGS  
// ========================================
echo "Creating test user activity logs...\n";
echo str_repeat("─", 60) . "\n\n";

$actions = array('login', 'logout', 'user_created', 'user_deleted', 'password_change', 'session_timeout');
$users = array('testuser1', 'testuser2', 'testuser3', 'demo_user', 'admin_test');

// Get all routers again
$stmt = $pdo->query("SELECT id, admin_id FROM routers ORDER BY RAND() LIMIT 5");
$sample_routers = $stmt->fetchAll();

foreach ($sample_routers as $router) {
    // Create 5-10 logs per router
    $num_logs = rand(5, 10);
    
    for ($i = 0; $i < $num_logs; $i++) {
        try {
            $user = $users[array_rand($users)];
            $action = $actions[array_rand($actions)];
            $ip = "192.168." . rand(1, 255) . "." . rand(1, 255);
            
            $query = "INSERT INTO user_logs 
                      (router_id, admin_id, username, action, action_type, 
                       ip_address, details, performed_by) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute(array(
                $router['id'],
                $router['admin_id'],
                $user,
                $action,
                strtoupper($action),
                $ip,
                'User activity logged',
                $router['admin_id']  // performed_by
            ));
            
            $created_stats['user_logs']++;
            
        } catch (Exception $e) {
            // Continue on error
        }
    }
}

echo "  ✓ Created " . $created_stats['user_logs'] . " activity logs\n\n";

// ========================================
// 4. CREATE TEST TRANSACTIONS
// ========================================
echo "Creating test transactions...\n";
echo str_repeat("─", 60) . "\n\n";

$payment_methods = array('Cash', 'Card', 'Mobile Money', 'Check');
$transaction_types = array('voucher_sale', 'credit_sale', 'refund', 'user_add');

// Get all vouchers with their router_id
$stmt = $pdo->query("SELECT v.id, v.admin_id, v.router_id FROM vouchers v LIMIT 20");
$vouchers = $stmt->fetchAll();

foreach ($vouchers as $voucher) {
    try {
        $query = "INSERT INTO transaction_history 
                      (admin_id, router_id, transaction_type, reference_id, description, 
                       amount, currency, payment_method, payment_status, customer_name, created_by) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute(array(
                $voucher['admin_id'],
                $voucher['router_id'],
                $transaction_types[array_rand($transaction_types)],
                'REF_' . rand(10000, 99999),
                'Test transaction',
                rand(1, 25),
                'USD',
                $payment_methods[array_rand($payment_methods)],
                'completed',
                'Customer ' . rand(1, 100),
                $voucher['admin_id']  // created_by
            ));
            
            $created_stats['transactions']++;
        
    } catch (Exception $e) {
        // Continue on error
    }
}

echo "  ✓ Created " . $created_stats['transactions'] . " transactions\n\n";

// ========================================
// FINAL SUMMARY
// ========================================
echo str_repeat("═", 60) . "\n";
echo "SEEDING COMPLETE!\n";
echo str_repeat("═", 60) . "\n\n";

echo "Statistical Summary:\n\n";

// Count records
$stmt = $pdo->query("SELECT COUNT(*) as cnt FROM admin_users");
$admin_count = $stmt->fetch()['cnt'];

$stmt = $pdo->query("SELECT COUNT(*) as cnt FROM routers");
$router_count = $stmt->fetch()['cnt'];

$stmt = $pdo->query("SELECT COUNT(*) as cnt FROM vouchers");
$voucher_count = $stmt->fetch()['cnt'];

$stmt = $pdo->query("SELECT COUNT(*) as cnt FROM user_logs");
$log_count = $stmt->fetch()['cnt'];

$stmt = $pdo->query("SELECT COUNT(*) as cnt FROM transaction_history");
$transaction_count = $stmt->fetch()['cnt'];

echo "  Admin Users:        " . str_pad($admin_count, 5) . " total\n";
echo "  Routers:            " . str_pad($router_count, 5) . " total (" . $created_stats['routers'] . " created)\n";
echo "  Vouchers:           " . str_pad($voucher_count, 5) . " total (" . $created_stats['vouchers'] . " created)\n";
echo "  Activity Logs:      " . str_pad($log_count, 5) . " total (" . $created_stats['user_logs'] . " created)\n";
echo "  Transactions:       " . str_pad($transaction_count, 5) . " total (" . $created_stats['transactions'] . " created)\n\n";

// Show admin breakdown
echo "Admin & Router Breakdown:\n";
$stmt = $pdo->query("SELECT a.id, a.username, COUNT(r.id) as router_count 
                    FROM admin_users a 
                    LEFT JOIN routers r ON a.id = r.admin_id 
                    GROUP BY a.id 
                    ORDER BY a.id");
$admin_routers = $stmt->fetchAll();

foreach ($admin_routers as $row) {
    echo "  [ID: " . $row['id'] . "] " . str_pad($row['username'], 15) . " → " . $row['router_count'] . " router(s)\n";
}

echo "\n" . str_repeat("═", 60) . "\n";
echo "✓ DATABASE SEEDING COMPLETE!\n";
echo "═" . str_repeat("═", 58) . "═\n\n";

echo "Next steps:\n";
echo "  1. Login with admin1 / Test@123456\n";
echo "  2. Check routers and vouchers\n";
echo "  3. Run CRUD tests: php test/crud_demo.php\n\n";
?>

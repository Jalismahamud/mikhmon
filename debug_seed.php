<?php
/*
 * Debug Seeding Script
 * Tests admin user creation directly
 */

require_once('./include/db_config.php');
require_once('./lib/Database.class.php');
require_once('./lib/AdminUser.class.php');

// Connect to database
$db = new Database();

if (!$db->isConnected()) {
    echo "[ERROR] Failed to connect to database\n";
    exit(1);
}

echo "========== DEBUG SEEDING ==========\n\n";

// Test data
$admin_data = array(
    'username' => 'admin',
    'password' => '12345678',
    'email' => 'admin@mikhmon.local',
    'full_name' => 'Administrator',
    'role' => 'admin'
);

echo "Creating admin user...\n";
echo "Username: " . $admin_data['username'] . "\n";
echo "Password: " . $admin_data['password'] . "\n\n";

try {
    // Try direct insert without AdminUser class
    $password_hash = password_hash($admin_data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
    
    $query = "INSERT INTO admin_users 
              (username, password_hash, email, full_name, role, is_active) 
              VALUES (?, ?, ?, ?, ?, ?)";
    
    $params = array(
        $admin_data['username'],
        $password_hash,
        $admin_data['email'],
        $admin_data['full_name'],
        $admin_data['role'],
        1
    );
    
    $result = $db->execute($query, $params);
    
    echo "Insert result: " . var_export($result, true) . "\n";
    echo "Last insert ID: " . $db->insertId() . "\n\n";
    
    if ($result) {
        echo "✓ Admin user created successfully!\n\n";
        
        // Verify
        $verify = $db->select('SELECT id, username, email, role FROM admin_users WHERE username = ?', array('admin'));
        echo "Verification:\n";
        echo json_encode($verify, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        echo "\n";
    } else {
        echo "✗ Failed to create admin user\n";
        echo "Error: " . $db->getLastError() . "\n";
    }
    
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

echo "\n";
?>

<?php
/*
 * Test Credentials Script
 * Verifies admin user and password authentication
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

echo "========== TESTING CREDENTIALS ==========\n\n";

// Query admin user
$result = $db->select('SELECT id, username, email, role, is_active FROM admin_users WHERE username = ?', array('admin'));

if ($result) {
    $user = $result[0];
    echo "✓ Admin user found!\n";
    echo "  Username: " . $user['username'] . "\n";
    echo "  Email: " . $user['email'] . "\n";
    echo "  Role: " . $user['role'] . "\n";
    echo "  Active: " . ($user['is_active'] ? 'Yes' : 'No') . "\n\n";
    
    // Test password verification
    $adminUser = new AdminUser($db, $user['id']);
    $auth_result = $adminUser->verifyLogin('admin', '12345678');
    
    echo "Password Test:\n";
    if ($auth_result) {
        echo "✓ PASSWORD VERIFIED - Credentials are CORRECT!\n";
        echo "  You can login with: admin / 12345678\n";
    } else {
        echo "✗ Password verification FAILED\n";
        echo "  Credentials may not be working\n";
    }
} else {
    echo "✗ Admin user NOT found in database\n";
    echo "  Run: php database/setup.php seed\n";
}

echo "\n";
?>

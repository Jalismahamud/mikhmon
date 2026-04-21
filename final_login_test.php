<?php
/*
 * Final Login Test
 * Simulates the login process from admin.php
 */

session_start();

require_once('./include/db_config.php');
require_once('./lib/Database.class.php');
require_once('./lib/AdminUser.class.php');
require_once('./include/auth.php');

echo "========== FINAL LOGIN TEST ==========\n\n";

// Test credentials
$test_user = 'admin';
$test_pass = '12345678';

echo "Testing with:\n";
echo "  Username: $test_user\n";
echo "  Password: $test_pass\n\n";

try {
    $auth = new AuthManager();
    $login_result = $auth->login($test_user, $test_pass);
    
    if ($login_result) {
        echo "✓ LOGIN SUCCESSFUL!\n\n";
        echo "Session variables set:\n";
        echo "  mikhmon_user_id: " . $_SESSION['mikhmon_user_id'] . "\n";
        echo "  mikhmon_username: " . $_SESSION['mikhmon_username'] . "\n";
        echo "  mikhmon_user_role: " . $_SESSION['mikhmon_user_role'] . "\n";
        echo "  mikhmon_email: " . $_SESSION['mikhmon_email'] . "\n";
        echo "  mikhmon_full_name: " . $_SESSION['mikhmon_full_name'] . "\n";
        echo "\n✓ You can now login with these credentials!\n";
    } else {
        echo "✗ Login failed!\n";
    }
} catch (Exception $e) {
    echo "✗ Exception: " . $e->getMessage() . "\n";
}

echo "\n";
?>

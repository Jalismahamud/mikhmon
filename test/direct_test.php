<?php
/*
 * Direct Database Test
 * Testing basic database operations without class abstraction
 */

echo "\n=== MIKHMON DATABASE DIRECT TEST ===\n\n";

require_once('./include/db_config.php');

// Test 1: Direct PDO connection
echo "[TEST 1] PDO Connection Test\n";
echo "----------------------------\n";

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
    
    echo "✓ Connected to: " . DB_NAME . "\n";
    echo "✓ Host: " . DB_HOST . "\n";
    echo "✓ User: " . DB_USER . "\n\n";
    
} catch (PDOException $e) {
    echo "✗ Connection failed: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 2: Check if tables exist
echo "[TEST 2] Check Tables\n";
echo "----------------------\n";

$query = "SHOW TABLES FROM " . DB_NAME;
$result = $pdo->query($query);
$tables = $result->fetchAll(PDO::FETCH_COLUMN);

echo "Tables in database:\n";
foreach ($tables as $table) {
    echo "  ✓ " . $table . "\n";
}
echo "\n";

// Test 3: Check table structure
echo "[TEST 3] Check Admin Users Table Structure\n";
echo "------------------------------------------\n";

$query = "DESCRIBE admin_users";
$result = $pdo->query($query);
$columns = $result->fetchAll(PDO::FETCH_ASSOC);

echo "Columns:\n";
foreach ($columns as $col) {
    echo "  " . $col['Field'] . " (" . $col['Type'] . ")\n";
}
echo "\n";

// Test 4: Direct INSERT test
echo "[TEST 4] Direct INSERT Test\n";
echo "----------------------------\n";

$username = 'testadmin_' . time();
$password_hash = password_hash('Test@123456', PASSWORD_BCRYPT, ['cost' => 12]);
$email = 'test.' . time() . '@mikhmon.local';

try {
    $query = "INSERT INTO admin_users 
              (username, password_hash, email, full_name, role, is_active, created_at) 
              VALUES (?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $pdo->prepare($query);
    
    $result = $stmt->execute(array(
        $username,
        $password_hash,
        $email,
        'Test Admin ' . time(),
        'admin',
        1
    ));
    
    $lastId = $pdo->lastInsertId();
    
    if ($result && $lastId > 0) {
        echo "✓ INSERT successful!\n";
        echo "  Inserted ID: " . $lastId . "\n";
        echo "  Username: " . $username . "\n";
        echo "  Email: " . $email . "\n\n";
    } else {
        echo "✗ Insert returned no ID\n";
        echo "  Last Insert ID: " . $lastId . "\n";
        echo "  Affected Rows: " . $stmt->rowCount() . "\n\n";
    }
    
} catch (Exception $e) {
    echo "✗ INSERT failed: " . $e->getMessage() . "\n\n";
}

// Test 5: SELECT test
echo "[TEST 5] Direct SELECT Test\n";
echo "-----------------------------\n";

try {
    $query = "SELECT * FROM admin_users WHERE username = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array($username));
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "✓ Record found!\n";
        echo "  ID: " . $result['id'] . "\n";
        echo "  Username: " . $result['username'] . "\n";
        echo "  Email: " . $result['email'] . "\n";
        echo "  Role: " . $result['role'] . "\n\n";
    } else {
        echo "✗ Record not found\n\n";
    }
    
} catch (Exception $e) {
    echo "✗ SELECT failed: " . $e->getMessage() . "\n\n";
}

// Test 6: Count all records
echo "[TEST 6] Count All Admin Users\n";
echo "-------------------------------\n";

try {
    $query = "SELECT COUNT(*) as total FROM admin_users";
    $stmt = $pdo->query($query);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Total admin users: " . $result['total'] . "\n\n";
    
} catch (Exception $e) {
    echo "✗ COUNT failed: " . $e->getMessage() . "\n\n";
}

// Test 7: List all admins
echo "[TEST 7] List All Admin Users\n";
echo "------------------------------\n";

try {
    $query = "SELECT id, username, email, role, is_active FROM admin_users ORDER BY id";
    $stmt = $pdo->query($query);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($results) > 0) {
        echo "Admin Users Found:\n";
        foreach ($results as $row) {
            $status = $row['is_active'] ? 'Active' : 'Inactive';
            echo "  [ID: " . $row['id'] . "] " . $row['username'] . " (" . $row['role'] . ") - " . $status . "\n";
        }
    } else {
        echo "No admin users found\n";
    }
    echo "\n";
    
} catch (Exception $e) {
    echo "✗ LIST failed: " . $e->getMessage() . "\n\n";
}

echo "=== TEST COMPLETE ===\n";
?>

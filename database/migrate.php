<?php
/*
 * Enhanced Database Migration Runner
 * Properly executes SQL migrations with error reporting
 */

echo "\n=== MIKHMON DATABASE MIGRATION RUNNER ===\n\n";

require_once('./include/db_config.php');

// Direct PDO connection for migrations
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
    
    echo "[OK] Connected to database: " . DB_NAME . "\n\n";
    
} catch (PDOException $e) {
    die("[ERROR] Connection failed: " . $e->getMessage() . "\n\n");
}

// Path to migration file
$migration_file = './database/migrations/001_create_initial_schema.sql';

if (!file_exists($migration_file)) {
    die("[ERROR] Migration file not found: " . $migration_file . "\n\n");
}

echo "[OK] Found migration file: " . $migration_file . "\n\n";

// Read SQL file
$sql = file_get_contents($migration_file);

if (!$sql) {
    die("[ERROR] Could not read migration file\n\n");
}

echo "Running migration: " . basename($migration_file) . "\n";
echo str_repeat("-", 50) . "\n";

// Split by semicolon (handle multi-line statements)
$statements = array_filter(array_map('trim', explode(";\n", $sql)));

$success_count = 0;
$error_count = 0;
$skip_count = 0;

foreach ($statements as $key => $statement) {
    $statement = trim($statement);
    
    if (empty($statement)) {
        continue;
    }
    
    // Skip comment lines
    if (strpos($statement, '--') === 0 || strpos($statement, '/*') === 0) {
        $skip_count++;
        continue;
    }
    
    // Skip lines that are only comments
    $lines = explode("\n", $statement);
    $clean_statement = '';
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '--') === 0) {
            continue;
        }
        $clean_statement .= $line . ' ';
    }
    
    $clean_statement = trim($clean_statement);
    
    if (empty($clean_statement)) {
        $skip_count++;
        continue;
    }
    
    try {
        $pdo->exec($clean_statement);
        
        // Extract statement type for logging
        $stmt_type = strtoupper(substr(trim($clean_statement), 0, 20));
        
        echo "[" . ($success_count + 1) . "] ✓ " . substr($stmt_type, 0, 30) . "...\n";
        $success_count++;
        
    } catch (PDOException $e) {
        echo "[ERR] ✗ FAILED: " . $e->getMessage() . "\n";
        echo "  Query: " . substr($clean_statement, 0, 80) . "...\n";
        $error_count++;
    }
}

echo "\n" . str_repeat("-", 50) . "\n";
echo "Migration Results:\n";
echo "  ✓ Successful: " . $success_count . "\n";
echo "  ✗ Errors: " . $error_count . "\n";
echo "  ⊘ Skipped: " . $skip_count . "\n";

if ($error_count === 0) {
    echo "\n[OK] Migration completed successfully!\n\n";
} else {
    echo "\n[ERROR] Migration completed with errors!\n\n";
}

// Verify tables were created
echo "\nVerifying tables...\n";
echo str_repeat("-", 50) . "\n";

try {
    $query = "SHOW TABLES FROM " . DB_NAME;
    $result = $pdo->query($query);
    $tables = $result->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) > 0) {
        echo "Tables created:\n";
        foreach ($tables as $table) {
            echo "  ✓ " . $table . "\n";
        }
    } else {
        echo "[WARNING] No tables found in database!\n";
    }
    
} catch (PDOException $e) {
    echo "[ERROR] Could not list tables: " . $e->getMessage() . "\n";
}

echo "\n=== MIGRATION COMPLETE ===\n\n";
?>

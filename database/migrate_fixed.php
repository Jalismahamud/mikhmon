<?php
/*
 * PROPER Database Migration - Fixed SQL Parsing
 * Uses PDO::ATTR_MULTI_STATEMENT to execute multi-statement SQL
 */

echo "\n╔═══════════════════════════════════════════════════════════╗\n";
echo "║  MIKHMON DATABASE MIGRATION RUNNER (IMPROVED)            ║\n";
echo "║  MikroTik Hotspot Management System                      ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

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
    
    echo "[✓] Connected to database: " . DB_NAME . "\n";
    echo "[✓] Host: " . DB_HOST . "\n";
    echo "[✓] User: " . DB_USER . "\n\n";
    
} catch (PDOException $e) {
    die("[✗] Connection failed: " . $e->getMessage() . "\n\n");
}

// Path to migration file
$migration_file = './database/migrations/001_create_initial_schema.sql';

if (!file_exists($migration_file)) {
    die("[✗] Migration file not found: " . $migration_file . "\n\n");
}

echo "[✓] Found migration file: " . basename($migration_file) . "\n\n";

// Read SQL file
$sql = file_get_contents($migration_file);

if (!$sql) {
    die("[✗] Could not read migration file\n\n");
}

echo "Executing migration...\n";
echo str_repeat("─", 60) . "\n\n";

// Clean up SQL: remove comments and extra whitespace
$lines = explode("\n", $sql);
$clean_sql = '';

foreach ($lines as $line) {
    // Remove SQL comments
    if (strpos(trim($line), '--') === 0) {
        continue;
    }
    
    // Remove inline comments
    if (strpos($line, '--') !== false) {
        $line = substr($line, 0, strpos($line, '--'));
    }
    
    $line = trim($line);
    
    if (!empty($line)) {
        $clean_sql .= $line . "\n";
    }
}

// Split statements properly at semicolons
$statements = array_filter(array_map('trim', explode(';', $clean_sql)));

$success = 0;
$failed = 0;

foreach ($statements as $statement) {
    $statement = trim($statement);
    
    if (empty($statement)) {
        continue;
    }
    
    try {
        // Execute the statement
        $result = $pdo->exec($statement);
        
        // Extract what kind of statement
        $first_word = strtoupper(substr(trim($statement), 0, 20));
        
        echo "[" . ($success + 1) . "] ✓ " . $first_word . "\n";
        $success++;
        
    } catch (PDOException $e) {
        echo "[E] ✗ Failed: " . substr($statement, 0, 60) . "...\n";
        echo "     Error: " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "\n" . str_repeat("─", 60) . "\n\n";
echo "Migration Results:\n";
echo "  ✓ Successful statements: " . $success . "\n";
echo "  ✗ Failed statements: " . $failed . "\n\n";

// Verify tables
echo "Verifying tables created...\n";
echo str_repeat("─", 60) . "\n\n";

try {
    $result = $pdo->query("SHOW TABLES FROM " . DB_NAME);
    $tables = $result->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) > 0) {
        echo "✓ Tables in database (" . count($tables) . " total):\n\n";
        foreach ($tables as $table) {
            // Get row count
            try {
                $count_result = $pdo->query("SELECT COUNT(*) as cnt FROM " . $table);
                $count = $count_result->fetch()['cnt'];
                echo "  ├─ " . str_pad($table, 30) . " [" . $count . " rows]\n";
            } catch (Exception $e) {
                echo "  ├─ " . $table . "\n";
            }
        }
    } else {
        echo "[✗] WARNING: No tables found in database!\n";
    }
    
} catch (PDOException $e) {
    echo "[✗] Could not list tables: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("═", 60) . "\n";

if ($failed === 0 && $success > 0) {
    echo "✓ MIGRATION SUCCESSFUL!\n";
    echo "═" . str_repeat("═", 58) . "═\n\n";
} else {
    echo "✗ MIGRATION INCOMPLETE!\n";
    echo "═" . str_repeat("═", 58) . "═\n\n";
}
?>

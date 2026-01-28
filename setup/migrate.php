<?php
/**
 * Database Setup/Migration Script
 * Run this once to set up the database schema
 * 
 * Usage:
 *   From command line: php setup/migrate.php
 *   Or access in browser: http://localhost/LifeX/setup/migrate.php
 */

require_once __DIR__ . '/../config/db.php';

$executed = [];
$errors = [];

try {
  // Migration 1: Add profile columns to users table
  $columns = $pdo->query("DESCRIBE users")->fetchAll();
  $columnNames = array_column($columns, 'Field');
  
  $columnsToAdd = [
    'phone' => "ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL AFTER role",
    'address' => "ALTER TABLE users ADD COLUMN address VARCHAR(255) DEFAULT NULL AFTER phone",
    'city' => "ALTER TABLE users ADD COLUMN city VARCHAR(120) DEFAULT NULL AFTER address",
    'profile_pic' => "ALTER TABLE users ADD COLUMN profile_pic VARCHAR(255) DEFAULT NULL AFTER city"
  ];
  
  foreach ($columnsToAdd as $colName => $sql) {
    if (!in_array($colName, $columnNames)) {
      $pdo->exec($sql);
      $executed[] = "✓ Added column to users: $colName";
    } else {
      $executed[] = "ℹ Column already exists: $colName";
    }
  }
  
  // Migration 2: Ensure payment_transactions table exists
  try {
    $pdo->query("SELECT 1 FROM payment_transactions LIMIT 1");
    $executed[] = "ℹ payment_transactions table already exists";
  } catch (Exception $e) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS payment_transactions (
      payment_tx_id INT PRIMARY KEY AUTO_INCREMENT,
      order_id INT NOT NULL,
      gateway VARCHAR(50) NOT NULL,
      status VARCHAR(20) DEFAULT 'pending',
      amount DECIMAL(10,2) NOT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
    )");
    $executed[] = "✓ Created payment_transactions table";
  }
  
  // Migration 3: Add image_path to products if needed
  if (!in_array('image_path', $columnNames = array_column($pdo->query("DESCRIBE products")->fetchAll(), 'Field'))) {
    $pdo->exec("ALTER TABLE products ADD COLUMN image_path VARCHAR(255) DEFAULT NULL");
    $executed[] = "✓ Added image_path column to products";
  } else {
    $executed[] = "ℹ image_path column already exists in products";
  }
  
  // Migration 4: Create required directories
  $dirs = [
    __DIR__ . '/../assets/images/products' => 'Product images',
    __DIR__ . '/../assets/images/profiles' => 'Profile pictures'
  ];
  
  foreach ($dirs as $dir => $name) {
    if (!is_dir($dir)) {
      if (mkdir($dir, 0755, true)) {
        $executed[] = "✓ Created directory: $name";
      } else {
        $errors[] = "Failed to create directory: $name";
      }
    } else {
      $executed[] = "ℹ Directory already exists: $name";
    }
  }
  
} catch (Exception $e) {
  $errors[] = "Error: " . $e->getMessage();
}

// Output results
if (php_sapi_name() === 'cli') {
  // CLI output
  echo "\n=== DATABASE MIGRATION REPORT ===\n\n";
  
  if (!empty($executed)) {
    echo "EXECUTED:\n";
    foreach ($executed as $msg) {
      echo "$msg\n";
    }
    echo "\n";
  }
  
  if (!empty($errors)) {
    echo "ERRORS:\n";
    foreach ($errors as $msg) {
      echo "✗ $msg\n";
    }
    echo "\n";
    exit(1);
  }
  
  echo "STATUS: All migrations completed successfully!\n";
  exit(0);
} else {
  // Web output
  echo "<!DOCTYPE html><html><head><style>body{font-family:monospace;background:#1a1a1a;color:#fff;padding:20px}h2{color:#7cf2ff}.success{color:#7cf2ff}.error{color:#ff6b6b}.info{color:#999}</style></head><body>";
  echo "<h2>Database Migration Report</h2>";
  
  if (!empty($executed)) {
    foreach ($executed as $msg) {
      echo "<div class='success'>$msg</div>";
    }
  }
  
  if (!empty($errors)) {
    foreach ($errors as $msg) {
      echo "<div class='error'>✗ $msg</div>";
    }
  }
  
  if (empty($errors)) {
    echo "<div style='margin-top:20px;color:#7cf2ff'><strong>✓ All migrations completed successfully!</strong></div>";
  }
  
  echo "</body></html>";
}
?>


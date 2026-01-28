<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/db.php';

echo "<h2>Product Check</h2>";

try {
  // Check product count
  $stmt = $pdo->query("SELECT COUNT(*) c FROM products WHERE is_active=1");
  $count = (int)$stmt->fetch()['c'];

  echo "<p>Active products: <strong>$count</strong></p>";

  if ($count === 0) {
    echo "<p style='color:orange'>No active products found. The database is empty or all products are inactive.</p>";
    
    // Try to get category
    $catStmt = $pdo->query("SELECT category_id FROM categories LIMIT 1");
    $cat = $catStmt->fetch();
    $catId = $cat ? $cat['category_id'] : 1;
    
    echo "<p>Adding test products with category_id=$catId...</p>";
    
    // Add test products
    $products = [
      ['name' => 'Apple MacBook Pro', 'price' => 99999, 'stock' => 5, 'desc' => 'Powerful laptop with M4 chip, 16GB RAM, and stunning Retina display'],
      ['name' => 'Apple iPhone 15', 'price' => 89999, 'stock' => 10, 'desc' => 'Latest iPhone with advanced camera system and A18 processor'],
      ['name' => 'Apple iPad Air', 'price' => 54999, 'stock' => 8, 'desc' => 'Versatile tablet with M2 chip and beautiful 11-inch display'],
    ];
    
    foreach ($products as $p) {
      $stmt = $pdo->prepare("INSERT INTO products (name, description, price, stock_qty, category_id, is_active) VALUES (?, ?, ?, ?, ?, 1)");
      $stmt->execute([$p['name'], $p['desc'], $p['price'], $p['stock'], $catId]);
      echo "<p style='color:green'>✓ Added: " . $p['name'] . "</p>";
    }
  } else {
    echo "<p style='color:green'>✓ Products exist. Search should work.</p>";
    
    // Show sample products
    $stmt = $pdo->query("SELECT product_id, name FROM products WHERE is_active=1 LIMIT 5");
    $products = $stmt->fetchAll();
    
    echo "<p>Sample products:</p><ul>";
    foreach ($products as $p) {
      echo "<li>" . htmlspecialchars($p['name']) . "</li>";
    }
    echo "</ul>";
  }
  
} catch (Exception $e) {
  echo "<p style='color:red'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

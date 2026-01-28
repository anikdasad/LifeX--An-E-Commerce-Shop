<?php
require_once __DIR__ . '/config/db.php';

$sort = $_GET['sort'] ?? 'newest';

echo "<h2>Testing Sort Parameter</h2>";
echo "<p>Sort: <strong>$sort</strong></p>";

// Test the API
$url = 'http://localhost/LifeX/api/filter-products.php?category=0&q=&sort=' . urlencode($sort);
echo "<p>Testing URL: <code>$url</code></p>";

$response = file_get_contents($url);
$data = json_decode($response, true);

if ($data && $data['ok']) {
  echo "<p style='color:green'>✓ API returned products successfully</p>";
  echo "<p>HTML length: " . strlen($data['html']) . " characters</p>";
  if (strlen($data['html']) > 0) {
    echo "<div style='border:1px solid #ccc;padding:10px;margin-top:10px'>";
    echo "<h3>Products (first 500 chars):</h3>";
    echo htmlspecialchars(substr($data['html'], 0, 500));
    echo "</div>";
  }
} else {
  echo "<p style='color:red'>✗ API Error or no products</p>";
  echo "<pre>" . htmlspecialchars($response) . "</pre>";
}

// Test database
echo "<h2>Database Test</h2>";
try {
  $stmt = $pdo->query("SELECT COUNT(*) c FROM products WHERE is_active=1");
  $count = (int)$stmt->fetch()['c'];
  echo "<p>Active products in DB: <strong>$count</strong></p>";
  
  if ($count > 0) {
    $stmt = $pdo->query("SELECT product_id, name, price FROM products WHERE is_active=1 LIMIT 3");
    $products = $stmt->fetchAll();
    echo "<p>Sample products:</p><ul>";
    foreach ($products as $p) {
      echo "<li>" . $p['name'] . " - ৳" . $p['price'] . "</li>";
    }
    echo "</ul>";
  }
} catch (Exception $e) {
  echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>

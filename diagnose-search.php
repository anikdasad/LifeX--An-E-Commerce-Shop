<?php
require_once __DIR__ . '/config/db.php';

echo "<h2>Search/Filter Diagnostic</h2>";

// Test 1: Check if products exist
$stmt = $pdo->query("SELECT COUNT(*) c FROM products WHERE is_active=1");
$count = (int)$stmt->fetch()['c'];
echo "<p>✓ Active products: <strong>$count</strong></p>";

// Test 2: Test filter API with no params
echo "<h3>Test 1: Filter API (no params)</h3>";
$res = file_get_contents('http://localhost/LifeX/api/filter-products.php');
$data = json_decode($res, true);
if ($data && $data['ok']) {
  echo "<p>✓ API working, returned " . substr_count($data['html'], 'product-card') . " products</p>";
} else {
  echo "<p>✗ API failed or returned no data</p>";
}

// Test 3: Test filter API with category
echo "<h3>Test 2: Filter API (with category=6)</h3>";
$res = file_get_contents('http://localhost/LifeX/api/filter-products.php?category=6');
$data = json_decode($res, true);
if ($data && $data['ok']) {
  echo "<p>✓ Category filter working, returned " . substr_count($data['html'], 'product-card') . " products</p>";
} else {
  echo "<p>✗ Category filter failed</p>";
}

// Test 4: Test filter API with search
echo "<h3>Test 3: Filter API (search='apple')</h3>";
$res = file_get_contents('http://localhost/LifeX/api/filter-products.php?q=apple');
$data = json_decode($res, true);
if ($data && $data['ok']) {
  echo "<p>✓ Search working, returned " . substr_count($data['html'], 'product-card') . " products</p>";
} else {
  echo "<p>✗ Search failed</p>";
}

// Test 5: Test filter API with sort
echo "<h3>Test 4: Filter API (sort='price_asc')</h3>";
$res = file_get_contents('http://localhost/LifeX/api/filter-products.php?sort=price_asc');
$data = json_decode($res, true);
if ($data && $data['ok']) {
  echo "<p>✓ Sort working, returned " . substr_count($data['html'], 'product-card') . " products</p>";
} else {
  echo "<p>✗ Sort failed</p>";
}

// Test 6: Check categories
echo "<h3>Test 5: Categories</h3>";
$stmt = $pdo->query("SELECT * FROM categories LIMIT 5");
$cats = $stmt->fetchAll();
echo "<p>✓ Found " . count($cats) . " categories</p>";
foreach ($cats as $c) {
  echo "<p>&nbsp;&nbsp;- " . $c['name'] . "</p>";
}

echo "<h3>Summary</h3>";
echo "<p>If all tests show ✓, then the issue is in the JavaScript or frontend.</p>";
echo "<p>Open browser DevTools (F12) and check the Console tab for JavaScript errors.</p>";
?>

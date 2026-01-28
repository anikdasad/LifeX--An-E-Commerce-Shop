<?php
declare(strict_types=1);
require_once __DIR__ . '/../helpers.php';

function create_order(PDO $pdo, int $userId, array $cart, array $shipping, string $shippingMethod, float $shippingCost, string $paymentMethod, string $paymentStatus = 'unpaid'): int {
  if (!$cart) throw new RuntimeException('Cart is empty');

  // Fetch products
  $ids = array_keys($cart);
  $in = implode(',', array_fill(0, count($ids), '?'));
  $stmt = $pdo->prepare("SELECT product_id, name, price, stock_qty, is_active FROM products WHERE product_id IN ($in) FOR UPDATE");
  $stmt->execute($ids);
  $products = $stmt->fetchAll();
  $byId = [];
  foreach ($products as $p) $byId[(int)$p['product_id']] = $p;

  // Validate stock
  $subtotal = 0.0;
  foreach ($cart as $pid => $qty) {
    $pid = (int)$pid; $qty = (int)$qty;
    if (empty($byId[$pid]) || (int)$byId[$pid]['is_active'] !== 1) {
      throw new RuntimeException("Product $pid not available");
    }
    if ($qty > (int)$byId[$pid]['stock_qty']) {
      throw new RuntimeException("Not enough stock for: " . $byId[$pid]['name']);
    }
    $subtotal += ((float)$byId[$pid]['price']) * $qty;
  }

  $total = $subtotal + $shippingCost;

  // Create order
  $stmt = $pdo->prepare("INSERT INTO orders
    (user_id, total_amount, status, payment_method, payment_status, shipping_method, shipping_cost,
     ship_name, ship_phone, ship_address, ship_city, created_at)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW())");
  $stmt->execute([
    $userId, $total, 'Pending', $paymentMethod, $paymentStatus, $shippingMethod, $shippingCost,
    $shipping['name'] ?? '', $shipping['phone'] ?? '', $shipping['address'] ?? '', $shipping['city'] ?? ''
  ]);
  $orderId = (int)$pdo->lastInsertId();

  // Insert items + decrement stock
  $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (?,?,?,?)");
  $stockStmt = $pdo->prepare("UPDATE products SET stock_qty = stock_qty - ? WHERE product_id=?");

  foreach ($cart as $pid => $qty) {
    $pid = (int)$pid; $qty = (int)$qty;
    $itemStmt->execute([$orderId, $pid, $qty, (float)$byId[$pid]['price']]);
    $stockStmt->execute([$qty, $pid]);
  }

  return $orderId;
}

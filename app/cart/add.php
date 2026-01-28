<?php
require_once __DIR__ . '/../helpers.php';
csrf_check();

$pid = (int)($_POST['product_id'] ?? 0);
$qty = max(1, (int)($_POST['qty'] ?? 1));

$stmt = $pdo->prepare("SELECT stock_qty, is_active FROM products WHERE product_id=?");
$stmt->execute([$pid]);
$p = $stmt->fetch();

if (!$p || (int)$p['is_active'] !== 1) {
  flash_set('err', 'Product not available.');
  header('Location: ' . base_url());
  exit;
}

$stock = (int)$p['stock_qty'];
$cart = cart_get();
$current = (int)($cart[$pid] ?? 0);
$newQty = min($stock, $current + $qty);
$cart[$pid] = $newQty;
cart_set($cart);

flash_set('ok', 'Added to cart.');
$back = $_SERVER['HTTP_REFERER'] ?? base_url();
header('Location: ' . $back);
exit;

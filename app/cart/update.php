<?php
require_once __DIR__ . '/../helpers.php';
csrf_check();

$pid = (int)($_POST['product_id'] ?? 0);
$qty = max(1, (int)($_POST['qty'] ?? 1));

$stmt = $pdo->prepare("SELECT stock_qty FROM products WHERE product_id=?");
$stmt->execute([$pid]);
$p = $stmt->fetch();
if (!$p) { flash_set('err','Product not found.'); header('Location: '.base_url('cart.php')); exit; }

$qty = min((int)$p['stock_qty'], $qty);

$cart = cart_get();
$cart[$pid] = $qty;
cart_set($cart);

flash_set('ok', 'Cart updated.');
header('Location: ' . base_url('cart.php'));
exit;

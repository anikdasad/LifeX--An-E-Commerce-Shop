<?php
require_once __DIR__ . '/../helpers.php';
csrf_check();

$pid = (int)($_POST['product_id'] ?? 0);
$cart = cart_get();
unset($cart[$pid]);
cart_set($cart);

flash_set('ok', 'Removed from cart.');
header('Location: ' . base_url('cart.php'));
exit;

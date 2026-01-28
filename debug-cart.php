<?php
require_once __DIR__ . '/app/helpers.php';

echo '<pre>';
echo "Session Status: " . session_status() . "\n";
echo "Session ID: " . session_id() . "\n";
echo "Cart contents:\n";
var_dump(cart_get());
echo "Cart count: " . cart_count() . "\n";
echo '</pre>';

$cart = cart_get();
if ($cart) {
  echo '<p>Cart has items. Product IDs: ' . implode(', ', array_keys($cart)) . '</p>';
  foreach ($cart as $pid => $qty) {
    echo "Product $pid: Qty $qty\n";
  }
} else {
  echo '<p>Cart is empty!</p>';
}
?>

<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../orders/order_service.php';
require_login();

function get_pending_payment(): array {
  $token = $_GET['token'] ?? '';
  $pending = $_SESSION['pending_payment'] ?? null;
  if (!$pending || !$token || !hash_equals((string)$pending['token'], (string)$token)) {
    http_response_code(400);
    echo "Invalid or expired payment session.";
    exit;
  }
  // Expire after 30 minutes
  if (time() - (int)$pending['created_at'] > 1800) {
    unset($_SESSION['pending_payment']);
    http_response_code(400);
    echo "Payment session expired.";
    exit;
  }
  return $pending;
}

function finalize_payment(PDO $pdo, array $pending, string $gatewayName, bool $success): void {
  $user = current_user();
  if ((int)$pending['user_id'] !== (int)$user['user_id']) {
    http_response_code(403); echo "Forbidden"; exit;
  }

  if (!$success) {
    flash_set('err', $gatewayName . ' payment failed (demo). You can retry or choose COD.');
    header('Location: ' . base_url('app/orders/checkout.php?step=4'));
    exit;
  }

  try {
    $pdo->beginTransaction();
    $orderId = create_order(
      $pdo,
      (int)$user['user_id'],
      $pending['cart'],
      $pending['checkout']['shipping'],
      $pending['checkout']['shipping_method'],
      (float)$pending['checkout']['shipping_cost'],
      $gatewayName,
      'paid'
    );

    // Mark as Processing when paid
    $pdo->prepare("UPDATE orders SET status='Processing' WHERE order_id=?")->execute([$orderId]);

    // Save transaction record
    $pdo->prepare("INSERT INTO payment_transactions (order_id, gateway, status, created_at) VALUES (?,?,?,NOW())")
        ->execute([$orderId, $gatewayName, 'success']);

    $pdo->commit();

    cart_set([]);
    unset($_SESSION['checkout']);
    unset($_SESSION['pending_payment']);

    send_order_email_stub($user['email'], "LifeX Order #$orderId (Paid)", "Payment successful via $gatewayName. Invoice: " . base_url("invoice.php?id=$orderId"));

    flash_set('ok', "Payment successful! Order #$orderId");
    header('Location: ' . base_url('invoice.php?id=' . $orderId));
    exit;
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    flash_set('err', 'Payment finalization failed: ' . $e->getMessage());
    header('Location: ' . base_url('app/orders/checkout.php?step=4'));
    exit;
  }
}

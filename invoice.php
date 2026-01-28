<?php
require_once __DIR__ . '/app/helpers.php';
require_login();

$orderId = (int)($_GET['id'] ?? 0);
$u = current_user();

$stmt = $pdo->prepare("SELECT o.*, u.username, u.email FROM orders o LEFT JOIN users u ON u.user_id=o.user_id WHERE o.order_id=?");
$stmt->execute([$orderId]);
$o = $stmt->fetch();
if (!$o) { http_response_code(404); echo "Order not found"; exit; }

// Permission: owner OR admin/employee
$role = $u['role'] ?? '';
if ((int)$o['user_id'] !== (int)$u['user_id'] && !in_array($role, ['admin','employee'], true)) {
  http_response_code(403); echo "Forbidden"; exit;
}

$itemsStmt = $pdo->prepare("
  SELECT oi.*, p.name
  FROM order_items oi
  LEFT JOIN products p ON p.product_id = oi.product_id
  WHERE oi.order_id=?
");
$itemsStmt->execute([$orderId]);
$items = $itemsStmt->fetchAll();

// Get transaction details
$txnStmt = $pdo->prepare("
  SELECT payment_tx_id, gateway, status
  FROM payment_transactions
  WHERE order_id=?
  ORDER BY payment_tx_id DESC
  LIMIT 1
");
$txnStmt->execute([$orderId]);
$transaction = $txnStmt->fetch();

$meta = seo_meta("Invoice #{$orderId}");
include __DIR__ . '/app/partials/header.php';
?>
<section class="section">
  <div class="container">
    <div class="row">
      <h2 class="reveal" style="margin:0">Invoice #<?= (int)$o['order_id'] ?></h2>
      <div style="display:flex;gap:8px">
        <button class="btn" onclick="window.print()">Print</button>
        <a href="<?= e(base_url('invoice-download.php?id=' . (int)$o['order_id'])) ?>" class="btn primary">Download PDF</a>
      </div>
    </div>

    <div class="card reveal" style="margin-top:12px">
      <div class="pad">
        <div class="row">
          <div>
            <div class="muted small">Billed To</div>
            <div style="font-weight:800"><?= e($o['username'] ?? 'Customer') ?></div>
            <div class="muted small"><?= e($o['email'] ?? '') ?></div>
          </div>
          <div style="text-align:right">
            <div class="muted small">Order Date</div>
            <div style="font-weight:800"><?= e(date('M d, Y H:i', strtotime($o['created_at']))) ?></div>
            <div class="muted small">Payment: <?= e($o['payment_method']) ?></div>
            <?php if ($transaction): ?>
              <div class="muted small" style="margin-top:8px;border-top:1px solid var(--line);padding-top:8px">
                <strong>Transaction ID:</strong> <?= (int)$transaction['payment_tx_id'] ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="hr"></div>

        <table class="table">
          <thead>
            <tr>
              <th>Item</th>
              <th style="width:120px">Price</th>
              <th style="width:120px">Qty</th>
              <th style="width:140px">Subtotal</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $it): 
              $sub = (float)$it['unit_price'] * (int)$it['quantity'];
            ?>
              <tr>
                <td><?= e($it['name'] ?? '') ?></td>
                <td>৳<?= number_format((float)$it['unit_price'], 2) ?></td>
                <td><?= (int)$it['quantity'] ?></td>
                <td>৳<?= number_format($sub, 2) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <div class="hr"></div>

        <div class="row">
          <div class="muted">Status: <?= e($o['status']) ?></div>
          <div class="price" style="font-size:20px">Total: ৳<?= number_format((float)$o['total_amount'], 2) ?></div>
        </div>

        <div class="hr"></div>
        <div class="muted small">Thank You for Shopping and Stay with Us</div>
      </div>
    </div>
  </div>
</section>
<?php include __DIR__ . '/app/partials/footer.php'; ?>

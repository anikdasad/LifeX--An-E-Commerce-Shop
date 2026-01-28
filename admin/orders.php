<?php
require_once __DIR__ . '/inc/guard.php';
$meta = seo_meta('Orders');
csrf_check();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $orderId = (int)($_POST['order_id'] ?? 0);
  $status = $_POST['status'] ?? 'Pending';
  $allowed = ['Pending','Processing','Shipped','Delivered','Cancelled'];
  if (!in_array($status, $allowed, true)) $status = 'Pending';

  $pdo->prepare("UPDATE orders SET status=? WHERE order_id=?")->execute([$status, $orderId]);
  flash_set('ok', "Order #$orderId updated.");
  header('Location: ' . base_url('admin/orders.php'));
  exit;
}

$rows = $pdo->query("
  SELECT o.*, u.username
  FROM orders o
  LEFT JOIN users u ON u.user_id=o.user_id
  ORDER BY o.order_id DESC
  LIMIT 200
")->fetchAll();

$ok = flash_get('ok');
include __DIR__ . '/inc/admin_header.php';
?>
<h2 class="admin-title">Orders</h2>
<?php if ($ok): ?><div class="notice"><?= e($ok) ?></div><?php endif; ?>

<div class="card">
  <div class="pad">
    <table class="table">
      <thead>
        <tr>
          <th>Order</th>
          <th>Customer</th>
          <th>Created</th>
          <th>Status</th>
          <th>Payment</th>
          <th>Total</th>
          <th>Update</th>
          <th>Invoice</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $o): ?>
          <tr>
            <td>#<?= (int)$o['order_id'] ?></td>
            <td><?= e($o['username'] ?? '—') ?></td>
            <td class="muted small"><?= e(date('M d, Y H:i', strtotime($o['created_at']))) ?></td>
            <td><?= e($o['status']) ?></td>
            <td class="muted small"><?= e($o['payment_method']) ?> (<?= e($o['payment_status']) ?>)</td>
            <td>৳<?= number_format((float)$o['total_amount'],2) ?></td>
            <td>
              <form method="post" class="row" style="justify-content:flex-start">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="order_id" value="<?= (int)$o['order_id'] ?>">
                <select class="input" name="status" style="max-width:170px">
                  <?php foreach (['Pending','Processing','Shipped','Delivered','Cancelled'] as $s): ?>
                    <option value="<?= e($s) ?>" <?= $o['status']===$s?'selected':'' ?>><?= e($s) ?></option>
                  <?php endforeach; ?>
                </select>
                <button class="btn" type="submit">Save</button>
              </form>
            </td>
            <td><a class="btn" href="<?= e(base_url('invoice.php?id='.(int)$o['order_id'])) ?>">Open</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/inc/admin_footer.php'; ?>

<?php
require_once __DIR__ . '/app/helpers.php';
require_login();

$meta = seo_meta('My orders', 'View your order history on LifeX.');
$u = current_user();

$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY order_id DESC");
$stmt->execute([(int)$u['user_id']]);
$orders = $stmt->fetchAll();

include __DIR__ . '/app/partials/header.php';
?>
<section class="section">
  <div class="container">
    <h2 class="reveal">My Orders</h2>
    <div class="card reveal">
      <div class="pad">
        <?php if (!$orders): ?>
          <div class="muted">No orders yet.</div>
        <?php else: ?>
        <table class="table">
          <thead>
            <tr>
              <th>Order</th>
              <th>Date</th>
              <th>Status</th>
              <th>Total</th>
              <th>Invoice</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($orders as $o): ?>
              <tr>
                <td>#<?= (int)$o['order_id'] ?></td>
                <td><?= e(date('M d, Y H:i', strtotime($o['created_at']))) ?></td>
                <td><?= e($o['status']) ?></td>
                <td>৳<?= number_format((float)$o['total_amount'], 2) ?></td>
                <td><a class="btn" href="<?= e(base_url('invoice.php?id='.(int)$o['order_id'])) ?>">Open</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
<?php include __DIR__ . '/app/partials/footer.php'; ?>

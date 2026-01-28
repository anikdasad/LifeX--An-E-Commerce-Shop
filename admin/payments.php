<?php
require_once __DIR__ . '/inc/guard.php';
$meta = seo_meta('Payments');

$paid = (int)$pdo->query("SELECT COUNT(*) c FROM orders WHERE payment_status='paid'")->fetch()['c'];
$unpaid = (int)$pdo->query("SELECT COUNT(*) c FROM orders WHERE payment_status='unpaid'")->fetch()['c'];

$tx = $pdo->query("SELECT * FROM payment_transactions ORDER BY payment_tx_id DESC LIMIT 200")->fetchAll();

include __DIR__ . '/inc/admin_header.php';
?>
<h2 class="admin-title">Payments</h2>

<div class="kpi-row" style="grid-template-columns:repeat(2,1fr)">
  <div class="card"><div class="pad">
    <div class="muted small">Paid orders</div>
    <div class="num"><?= $paid ?></div>
  </div></div>
  <div class="card"><div class="pad">
    <div class="muted small">Unpaid orders ( Cash on Delivery)</div>
    <div class="num"><?= $unpaid ?></div>
  </div></div>
</div>

<div class="section">
  <h2>Transactions</h2>
  <div class="card">
    <div class="pad">
      <table class="table">
        <thead><tr><th>ID</th><th>Order</th><th>Gateway</th><th>Status</th><th>Created</th></tr></thead>
        <tbody>
          <?php foreach ($tx as $t): ?>
            <tr>
              <td><?= (int)$t['payment_tx_id'] ?></td>
              <td><?= $t['order_id'] ? '#'.(int)$t['order_id'] : '—' ?></td>
              <td><?= e($t['gateway']) ?></td>
              <td><?= e($t['status']) ?></td>
              <td class="muted small"><?= e(date('M d, Y H:i', strtotime($t['created_at']))) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/inc/admin_footer.php'; ?>

<?php
require_once __DIR__ . '/inc/guard.php';
$meta = seo_meta('Reports');

$range = $_GET['range'] ?? '30';
$days = (int)$range;
if (!in_array($days, [7,30,90,365], true)) $days = 30;

$from = (new DateTime())->modify("-{$days} days")->format('Y-m-d 00:00:00');

$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) s FROM orders WHERE payment_status='paid' AND created_at >= ?");
$stmt->execute([$from]);
$paidRevenue = (float)$stmt->fetch()['s'];

$stmt = $pdo->prepare("SELECT COUNT(*) c FROM orders WHERE created_at >= ?");
$stmt->execute([$from]);
$orderCount = (int)$stmt->fetch()['c'];

$stmt = $pdo->prepare("SELECT status, COUNT(*) c FROM orders WHERE created_at >= ? GROUP BY status ORDER BY c DESC");
$stmt->execute([$from]);
$statusBreakdown = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT payment_method, COUNT(*) c FROM orders WHERE created_at >= ? GROUP BY payment_method ORDER BY c DESC");
$stmt->execute([$from]);
$paymentBreakdown = $stmt->fetchAll();

$top = $pdo->prepare("
  SELECT p.product_id, p.name,
         SUM(oi.quantity) AS units,
         SUM(oi.quantity * oi.unit_price) AS sales
  FROM order_items oi
  JOIN products p ON p.product_id = oi.product_id
  JOIN orders o ON o.order_id = oi.order_id
  WHERE o.created_at >= ?
  GROUP BY p.product_id, p.name
  ORDER BY sales DESC
  LIMIT 10
");
$top->execute([$from]);
$topProducts = $top->fetchAll();

$low = $pdo->query("SELECT product_id, name, stock_qty FROM products WHERE stock_qty <= 10 ORDER BY stock_qty ASC, product_id DESC LIMIT 12")->fetchAll();

include __DIR__ . '/inc/admin_header.php';
?>
<h2 class="admin-title">Reports</h2>

<div class="admin-toolbar">
  <form method="get" class="admin-filters">
    <label class="muted small">Range</label>
    <select class="input" name="range" onchange="this.form.submit()">
      <option value="7" <?= $days===7?'selected':'' ?>>Last 7 days</option>
      <option value="30" <?= $days===30?'selected':'' ?>>Last 30 days</option>
      <option value="90" <?= $days===90?'selected':'' ?>>Last 90 days</option>
      <option value="365" <?= $days===365?'selected':'' ?>>Last 12 months</option>
    </select>
  </form>
</div>

<div class="kpi-row">
  <div class="card"><div class="pad">
    <div class="muted small">Paid revenue</div>
    <div class="num">৳<?= number_format($paidRevenue,2) ?></div>
  </div></div>
  <div class="card"><div class="pad">
    <div class="muted small">Orders</div>
    <div class="num"><?= $orderCount ?></div>
  </div></div>
  <div class="card"><div class="pad">
    <div class="muted small">Low stock items</div>
    <div class="num"><?= count($low) ?></div>
  </div></div>
</div>

<div class="grid" style="grid-template-columns:1fr 1fr;gap:16px;margin-top:16px">
  <div class="card"><div class="pad">
    <h3 style="margin:0 0 12px 0">Order status</h3>
    <table class="table">
      <thead><tr><th>Status</th><th style="text-align:right">Count</th></tr></thead>
      <tbody>
      <?php foreach ($statusBreakdown as $r): ?>
        <tr><td><?= e($r['status']) ?></td><td style="text-align:right"><?= (int)$r['c'] ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$statusBreakdown): ?><tr><td colspan="2" class="muted">No data</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div></div>

  <div class="card"><div class="pad">
    <h3 style="margin:0 0 12px 0">Payment methods</h3>
    <table class="table">
      <thead><tr><th>Method</th><th style="text-align:right">Count</th></tr></thead>
      <tbody>
      <?php foreach ($paymentBreakdown as $r): ?>
        <tr><td><?= e($r['payment_method']) ?></td><td style="text-align:right"><?= (int)$r['c'] ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$paymentBreakdown): ?><tr><td colspan="2" class="muted">No data</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div></div>
</div>

<div class="grid" style="grid-template-columns:1fr 1fr;gap:16px;margin-top:16px">
  <div class="card"><div class="pad">
    <h3 style="margin:0 0 12px 0">Top products</h3>
    <table class="table">
      <thead><tr><th>Product</th><th style="text-align:right">Units</th><th style="text-align:right">Sales</th></tr></thead>
      <tbody>
      <?php foreach ($topProducts as $r): ?>
        <tr>
          <td><?= e($r['name']) ?></td>
          <td style="text-align:right"><?= (int)$r['units'] ?></td>
          <td style="text-align:right">৳<?= number_format((float)$r['sales'],2) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$topProducts): ?><tr><td colspan="3" class="muted">No sales yet</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div></div>

  <div class="card"><div class="pad">
    <h3 style="margin:0 0 12px 0">Low stock</h3>
    <table class="table">
      <thead><tr><th>Product</th><th style="text-align:right">Stock</th></tr></thead>
      <tbody>
      <?php foreach ($low as $r): ?>
        <tr><td><?= e($r['name']) ?></td><td style="text-align:right"><?= (int)$r['stock_qty'] ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$low): ?><tr><td colspan="2" class="muted">All good</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div></div>
</div>

<div style="margin-top:16px;text-align:center;display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
  <button class="btn" onclick="window.print()" title="Print this report">🖨️ Print Report</button>
  <a href="reports-download.php?range=<?= $days ?>" class="btn primary" title="Download as PDF">📥 Download PDF</a>
</div>

<?php include __DIR__ . '/inc/admin_footer.php'; ?>

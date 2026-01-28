<?php
require_once __DIR__ . '/inc/guard.php';
$meta = seo_meta('Dashboard');

$kpiProducts = (int)$pdo->query("SELECT COUNT(*) c FROM products")->fetch()['c'];
$kpiOrders   = (int)$pdo->query("SELECT COUNT(*) c FROM orders")->fetch()['c'];
$kpiUsers    = (int)$pdo->query("SELECT COUNT(*) c FROM users")->fetch()['c'];

$stmt = $pdo->query("SELECT COALESCE(SUM(total_amount),0) s FROM orders WHERE payment_status='paid'");
$kpiRevenue = (float)$stmt->fetch()['s'];

$stmt = $pdo->query("SELECT COUNT(*) c FROM orders WHERE status='Pending'");
$kpiPending = (int)$stmt->fetch()['c'];

$statusBreakdown = $pdo->query("SELECT status, COUNT(*) c FROM orders GROUP BY status ORDER BY c DESC")->fetchAll();
$paymentBreakdown = $pdo->query("SELECT payment_method, COUNT(*) c FROM orders GROUP BY payment_method ORDER BY c DESC")->fetchAll();

$lowStock = $pdo->query("SELECT product_id, name, stock_qty FROM products WHERE stock_qty <= 10 ORDER BY stock_qty ASC, product_id DESC LIMIT 8")->fetchAll();

$recent = $pdo->query("
  SELECT o.order_id, o.total_amount, o.status, o.payment_method, o.payment_status, o.created_at, u.username
  FROM orders o
  LEFT JOIN users u ON u.user_id = o.user_id
  ORDER BY o.order_id DESC
  LIMIT 10
")->fetchAll();

// Sales data for chart (last 30 days)
$salesData = $pdo->query("
  SELECT DATE(created_at) as date, COALESCE(SUM(total_amount), 0) as sales, COUNT(*) as orders
  FROM orders
  WHERE payment_status='paid' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
  GROUP BY DATE(created_at)
  ORDER BY date ASC
")->fetchAll();

$chartDates = [];
$chartSales = [];
foreach ($salesData as $row) {
  $chartDates[] = $row['date'];
  $chartSales[] = (float)$row['sales'];
}

// Transaction log
$transactions = $pdo->query("
  SELECT pt.payment_tx_id, pt.order_id, pt.gateway, pt.status, pt.created_at, o.total_amount
  FROM payment_transactions pt
  LEFT JOIN orders o ON o.order_id = pt.order_id
  ORDER BY pt.created_at DESC
  LIMIT 15
")->fetchAll();

include __DIR__ . '/inc/admin_header.php';
?>
<h2 class="admin-title">Admin Dashboard</h2>

<div class="kpi-row">
  <div class="card"><div class="pad">
    <div class="muted small">Products</div>
    <div class="num"><?= $kpiProducts ?></div>
  </div></div>
  <div class="card"><div class="pad">
    <div class="muted small">Orders</div>
    <div class="num"><?= $kpiOrders ?></div>
  </div></div>
  <div class="card"><div class="pad">
    <div class="muted small">Users</div>
    <div class="num"><?= $kpiUsers ?></div>
  </div></div>
  <div class="card"><div class="pad">
    <div class="muted small">Paid revenue</div>
    <div class="num">৳<?= number_format($kpiRevenue, 2) ?></div>
  </div></div>
  <div class="card"><div class="pad">
    <div class="muted small">Pending</div>
    <div class="num"><?= $kpiPending ?></div>
  </div></div>
</div>

<div class="grid" style="grid-template-columns:1fr 1fr;gap:16px;margin-top:16px">
  <div class="card"><div class="pad">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px">
      <h3 style="margin:0">Order status</h3>
      <a class="muted small" href="<?= e(base_url('admin/reports.php')) ?>">Full reports →</a>
    </div>
    <table class="table" style="margin-top:10px">
      <thead><tr><th>Status</th><th style="text-align:right">Count</th></tr></thead>
      <tbody>
      <?php foreach ($statusBreakdown as $r): ?>
        <tr><td><?= e($r['status']) ?></td><td style="text-align:right"><?= (int)$r['c'] ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$statusBreakdown): ?><tr><td colspan="2" class="muted">No orders yet</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div></div>

  <div class="card"><div class="pad">
    <h3 style="margin:0 0 10px 0">Payment methods</h3>
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

<div class="card" style="margin-top:16px"><div class="pad">
  <h3 style="margin:0 0 16px 0">Sales Report (Last 30 Days)</h3>
  <div style="position:relative;height:300px">
    <canvas id="salesChart"></canvas>
  </div>
</div></div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function(){
  const ctx = document.getElementById('salesChart');
  if (!ctx) return;
  
  const dates = <?= json_encode($chartDates) ?>;
  const sales = <?= json_encode($chartSales) ?>;
  
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: dates,
      datasets: [{
        label: 'Daily Sales (৳)',
        data: sales,
        borderColor: '#7cf2ff',
        backgroundColor: 'rgba(124, 242, 255, 0.08)',
        borderWidth: 2,
        tension: 0.4,
        fill: true,
        pointBackgroundColor: '#7cf2ff',
        pointBorderColor: '#0b0b10',
        pointRadius: 4,
        pointHoverRadius: 6,
        pointBorderWidth: 2,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: true,
          labels: {
            color: '#b6b6c6',
            font: {
              size: 12,
              weight: 'bold'
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            color: '#b6b6c6',
            callback: function(value) {
              return '৳' + value.toLocaleString();
            }
          },
          grid: {
            color: 'rgba(255, 255, 255, 0.05)'
          }
        },
        x: {
          ticks: {
            color: '#b6b6c6'
          },
          grid: {
            color: 'rgba(255, 255, 255, 0.05)'
          }
        }
      }
    }
  });
})();
</script>

<div class="grid" style="grid-template-columns:1fr 1fr;gap:16px;margin-top:16px">
  <div class="card"><div class="pad">
    <h3 style="margin:0 0 10px 0">Low stock</h3>
    <table class="table">
      <thead><tr><th>Product</th><th style="text-align:right">Stock</th></tr></thead>
      <tbody>
      <?php foreach ($lowStock as $r): ?>
        <tr>
          <td><a href="<?= e(base_url('admin/products.php')) ?>"><?= e($r['name']) ?></a></td>
          <td style="text-align:right"><?= (int)$r['stock_qty'] ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$lowStock): ?><tr><td colspan="2" class="muted">All products have healthy stock</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div></div>

  <div class="card"><div class="pad">
    <h3 style="margin:0 0 10px 0">Recent orders</h3>
    <table class="table"><thead>
      <tr>
        <th>#</th><th>Customer</th><th>Status</th><th>Payment</th><th style="text-align:right">Total</th>
      </tr></thead><tbody>
      <?php foreach ($recent as $o): ?>
        <tr>
          <td><a href="<?= e(base_url('admin/orders.php')) ?>"><?= (int)$o['order_id'] ?></a></td>
          <td><?= e($o['username'] ?? '—') ?></td>
          <td><?= e($o['status']) ?></td>
          <td><?= e($o['payment_method']) ?> / <?= e($o['payment_status']) ?></td>
          <td style="text-align:right">৳<?= number_format((float)$o['total_amount'],2) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$recent): ?><tr><td colspan="5" class="muted">No orders yet</td></tr><?php endif; ?>
      </tbody></table>
  </div></div>
</div>

<div class="card" style="margin-top:16px"><div class="pad">
  <h3 style="margin:0 0 10px 0">Transaction Log</h3>
  <table class="table">
    <thead>
      <tr>
        <th>Transaction ID</th>
        <th>Order ID</th>
        <th>Gateway</th>
        <th>Status</th>
        <th style="text-align:right">Amount</th>
        <th>Date</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($transactions as $t): ?>
        <tr>
          <td><?= (int)$t['payment_tx_id'] ?></td>
          <td>
            <?php if ($t['order_id']): ?>
              <a href="<?= e(base_url('admin/orders.php')) ?>"><?= (int)$t['order_id'] ?></a>
            <?php else: ?>
              <span class="muted">—</span>
            <?php endif; ?>
          </td>
          <td><?= e($t['gateway'] ?? '—') ?></td>
          <td>
            <span style="padding:4px 8px;border-radius:6px;font-size:11px;font-weight:bold;background:<?= 
              $t['status'] === 'success' ? 'rgba(76,175,80,.15)' : 
              ($t['status'] === 'failed' ? 'rgba(244,67,54,.15)' : 'rgba(255,193,7,.15)') 
            ?>;color:<?= 
              $t['status'] === 'success' ? '#4caf50' : 
              ($t['status'] === 'failed' ? '#f44336' : '#ffc107') 
            ?>">
              <?= e(ucfirst($t['status'] ?? 'pending')) ?>
            </span>
          </td>
          <td style="text-align:right">৳<?= number_format((float)($t['total_amount'] ?? 0),2) ?></td>
          <td class="muted small"><?= date('M d, H:i', strtotime($t['created_at'])) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$transactions): ?><tr><td colspan="6" class="muted">No transactions yet</td></tr><?php endif; ?>
    </tbody>
  </table>
</div></div>

<?php include __DIR__ . '/inc/admin_footer.php'; ?>

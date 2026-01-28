<?php
require_once __DIR__ . '/app/helpers.php';
require_login();

$u = current_user();
$orderId = (int)($_GET['id'] ?? 0);
$order = null;
$items = [];
$statusTimeline = [];

if ($orderId > 0) {
  $stmt = $pdo->prepare("SELECT o.*, u.username FROM orders o LEFT JOIN users u ON u.user_id=o.user_id WHERE o.order_id=? AND o.user_id=?");
  $stmt->execute([$orderId, $u['user_id']]);
  $order = $stmt->fetch();
  
  if ($order) {
    // Get order items
    $itemsStmt = $pdo->prepare("
      SELECT oi.*, p.name, p.image_path
      FROM order_items oi
      LEFT JOIN products p ON p.product_id = oi.product_id
      WHERE oi.order_id=?
    ");
    $itemsStmt->execute([$orderId]);
    $items = $itemsStmt->fetchAll();
    
    // Build status timeline
    $statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    $createdTime = strtotime($order['created_at']);
    
    foreach ($statuses as $i => $status) {
      $isComplete = false;
      $timestamp = null;
      
      if (strtolower($order['status']) === $status) {
        $isComplete = true;
        $timestamp = $createdTime + ($i * 2 * 24 * 3600); // Simulate timeline
      } elseif (in_array(strtolower($order['status']), array_slice($statuses, 0, $i + 1))) {
        $isComplete = true;
        $timestamp = $createdTime + ($i * 2 * 24 * 3600);
      }
      
      $statusTimeline[] = [
        'status' => $status,
        'label' => ucfirst($status),
        'completed' => $isComplete,
        'timestamp' => $timestamp
      ];
    }
  }
} else {
  // Show user's recent orders
  $ordersStmt = $pdo->prepare("SELECT order_id, total_amount, status, created_at FROM orders WHERE user_id=? ORDER BY order_id DESC LIMIT 10");
  $ordersStmt->execute([$u['user_id']]);
  $recentOrders = $ordersStmt->fetchAll();
}

$meta = seo_meta('Order Tracking');
include __DIR__ . '/app/partials/header.php';
?>
<section class="section" style="min-height:80vh">
  <div class="container">
    <h2 class="reveal" style="margin-bottom:24px">Order Tracking</h2>

    <?php if ($orderId > 0 && $order): ?>
      <!-- Order Detail View -->
      <div class="card reveal" style="margin-bottom:24px">
        <div class="pad">
          <div class="row" style="gap:20px;align-items:flex-start">
            <div>
              <div class="muted small">Order Number</div>
              <div style="font-size:20px;font-weight:bold">#<?= str_pad($order['order_id'], 8, '0', STR_PAD_LEFT) ?></div>
            </div>
            <div>
              <div class="muted small">Order Date</div>
              <div style="font-size:16px"><?= date('M d, Y H:i', strtotime($order['created_at'])) ?></div>
            </div>
            <div>
              <div class="muted small">Total Amount</div>
              <div style="font-size:20px;font-weight:bold">৳<?= number_format($order['total_amount'], 2) ?></div>
            </div>
            <div>
              <div class="muted small">Payment Status</div>
              <div style="font-weight:bold;color:<?= $order['payment_status']==='paid'?'#7cf2ff':'#ff6b6b' ?>"><?= ucfirst($order['payment_status']) ?></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Status Timeline -->
      <div class="card reveal" style="margin-bottom:24px">
        <div class="pad">
          <h3 style="margin:0 0 24px 0">Delivery Status</h3>
          <div style="display:grid;gap:16px">
            <?php foreach ($statusTimeline as $i => $step): ?>
              <div style="display:flex;gap:16px;align-items:flex-start">
                <div style="flex-shrink:0;text-align:center">
                  <div style="width:40px;height:40px;border-radius:50%;background:<?= $step['completed']?'#7cf2ff':'#333' ?>;display:flex;align-items:center;justify-content:center;color:#000;font-weight:bold;margin-bottom:8px">
                    <?= $i + 1 ?>
                  </div>
                  <?php if ($i < count($statusTimeline) - 1): ?>
                    <div style="width:2px;height:40px;background:<?= $step['completed']?'#00ff40':'#333' ?>;margin:0 auto"></div>
                  <?php endif; ?>
                </div>
                <div style="flex:1;padding:8px 0">
                  <div style="font-weight:bold;color:<?= $step['completed']?'#7cf2ff':'#999' ?>"><?= $step['label'] ?></div>
                  <?php if ($step['timestamp']): ?>
                    <div class="muted small"><?= date('M d, Y', $step['timestamp']) ?></div>
                  <?php endif; ?>
                  <?php if (strtolower($order['status']) === $step['status']): ?>
                    <div style="margin-top:8px;padding:8px;background:rgba(124,242,255,0.1);border-left:2px solid #7cf2ff;font-size:14px">
                      Your order is currently <?= $step['label'] ?>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Order Items -->
      <div class="card reveal" style="margin-bottom:24px">
        <div class="pad">
          <h3 style="margin:0 0 16px 0">Items</h3>
          <table class="table" style="font-size:14px">
            <thead>
              <tr>
                <th>Product</th>
                <th style="text-align:right">Price</th>
                <th style="text-align:right">Qty</th>
                <th style="text-align:right">Total</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $item): ?>
                <tr>
                  <td>
                    <div><?= e($item['name'] ?? 'Product') ?></div>
                    <div class="muted small">SKU: <?= e($item['product_id']) ?></div>
                  </td>
                  <td style="text-align:right">৳<?= number_format($item['unit_price'], 2) ?></td>
                  <td style="text-align:right"><?= (int)$item['quantity'] ?></td>
                  <td style="text-align:right;font-weight:bold">৳<?= number_format($item['unit_price'] * $item['quantity'], 2) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Shipping Address -->
      <div class="card reveal">
        <div class="pad">
          <h3 style="margin:0 0 16px 0">Shipping Details</h3>
          <div class="row" style="gap:30px">
            <div>
              <div class="muted small">Recipient</div>
              <div><?= e($order['username']) ?></div>
            </div>
            <div>
              <div class="muted small">Shipping Address</div>
              <div><?= e($order['shipping_address'] ?? 'Not specified') ?></div>
            </div>
            <div>
              <div class="muted small">Shipping Method</div>
              <div><?= e(ucfirst($order['shipping_method'] ?? 'Standard')) ?></div>
            </div>
          </div>
        </div>
      </div>

      <div style="margin-top:24px;text-align:center">
        <a href="track.php" class="btn">Back to Orders</a>
      </div>

    <?php else: ?>
      <!-- Orders List View -->
      <div style="margin-bottom:24px">
        <input type="text" id="searchOrder" placeholder="Search by order ID..." style="width:100%;padding:12px;border:1px solid var(--line);border-radius:4px;background:var(--card);color:var(--text)">
      </div>

      <?php if (isset($recentOrders) && count($recentOrders) > 0): ?>
        <div style="display:grid;gap:12px">
          <?php foreach ($recentOrders as $o): ?>
            <a href="track.php?id=<?= (int)$o['order_id'] ?>" style="text-decoration:none">
              <div class="card" style="cursor:pointer;transition:all 0.3s;padding:16px" onmouseover="this.style.background='var(--line)'" onmouseout="this.style.background='var(--card)'">
                <div class="row" style="gap:20px;align-items:center">
                  <div style="flex:1">
                    <div style="font-weight:bold;margin-bottom:4px">Order #<?= str_pad($o['order_id'], 8, '0', STR_PAD_LEFT) ?></div>
                    <div class="muted small"><?= date('M d, Y H:i', strtotime($o['created_at'])) ?></div>
                  </div>
                  <div style="text-align:right">
                    <div style="font-weight:bold;margin-bottom:4px">৳<?= number_format($o['total_amount'], 2) ?></div>
                    <div style="font-size:12px;padding:4px 8px;background:<?= 
                      $o['status']==='pending'?'rgb(255, 0, 0)':
                      ($o['status']==='processing'?'rgba(183, 0, 255, 0.94)':
                      ($o['status']==='shipped'?'rgb(0, 153, 255)':
                      ($o['status']==='delivered'?'rgb(0, 255, 34)':'rgba(255,107,107,0.1)')))
                    ?>;border-radius:4px;color:<?= 
                      $o['status']==='pending'?'#ffbb00':
                      ($o['status']==='processing'?'#a98bff':
                      ($o['status']==='shipped'?'#3498db':
                      ($o['status']==='delivered'?'#7cf2ff':'#ff6b6b')))
                    ?>;font-weight:bold">
                      <?= ucfirst($o['status']) ?>
                    </div>
                  </div>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="card" style="text-align:center;padding:40px">
          <div class="muted">No orders found. Start shopping to track your orders!</div>
          <a href="index.php" class="btn" style="margin-top:16px">Continue Shopping</a>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>

<script>
document.getElementById('searchOrder')?.addEventListener('keyup', function() {
  const query = this.value.toLowerCase().trim();
  if (query) {
    window.location.href = 'track.php?id=' + query;
  }
});
</script>

<?php include __DIR__ . '/app/partials/footer.php'; ?>

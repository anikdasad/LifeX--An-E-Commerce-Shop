<?php
require_once __DIR__ . '/common.php';

$pending = get_pending_payment();
$gatewayName = 'bKash';

csrf_check();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  if ($action === 'success') {
    finalize_payment($pdo, $pending, $gatewayName, true);
  } else {
    // log failure attempt
    $pdo->prepare("INSERT INTO payment_transactions (order_id, gateway, status, created_at) VALUES (NULL, ?, ?, NOW())")
        ->execute([$gatewayName, 'failed_attempt']);
    finalize_payment($pdo, $pending, $gatewayName, false);
  }
}

$meta = seo_meta($gatewayName . ' payment');
include __DIR__ . '/../partials/header.php';
?>
<section class="section">
  <div class="container" style="max-width:760px">
    <h2 class="reveal"><?= e($gatewayName) ?> Payment </h2>
    <p class="subtle reveal">This is a test gateway screen. In real integration, you would redirect to the provider API.</p>

    <div class="card reveal">
      <div class="pad">
        <div class="row">
          <div>
            <div class="muted small">Cart items</div>
            <div style="font-weight:800"><?= count($pending['cart']) ?> product(s)</div>
          </div>
          <div style="text-align:right">
            <div class="muted small">Payable</div>
            <div class="price" style="font-size:20px">
              ৳<?= number_format(cart_total($pdo) + (float)$pending['checkout']['shipping_cost'], 2) ?>
            </div>
          </div>
        </div>
        <div class="hr"></div>

        <div style="background:rgba(124,242,255,.08);border:1px solid rgba(124,242,255,.2);border-radius:12px;padding:12px;margin-bottom:16px">
          <div class="muted small">Send payment to bKash account:</div>
          <div style="font-weight:800;font-size:18px;margin-top:6px">+8801724700072</div>
          <div class="muted small" style="margin-top:6px">Please transfer the exact amount shown above and confirm on this page.</div>
        </div>

        <div class="actions">
          <form method="post" style="margin:0">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="success">
            <button class="btn primary" type="submit">Confirm Payment</button>
          </form>

          <form method="post" style="margin:0">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="fail">
            <button class="btn" type="submit">Simulate Failure</button>
          </form>

          <a class="btn" href="<?= e(base_url('app/orders/checkout.php?step=3')) ?>">Change payment method</a>
        </div>

        <div class="muted small" style="margin-top:10px">
          Token: <?= e($_GET['token'] ?? '') ?>
        </div>
      </div>
    </div>
  </div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>

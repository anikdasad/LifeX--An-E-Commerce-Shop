<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/order_service.php';
require_login();

$cart = cart_get();
if (!$cart) {
  flash_set('err', 'Your cart is empty.');
  header('Location: ' . base_url('cart.php'));
  exit;
}

csrf_check();

$step = (int)($_GET['step'] ?? 1);
$step = max(1, min(4, $step));

if (empty($_SESSION['checkout'])) {
  $_SESSION['checkout'] = [
    'shipping' => ['name'=>'','phone'=>'','address'=>'','city'=>''],
    'shipping_method' => 'standard',
    'shipping_cost' => 60.0,
    'payment_method' => 'cod',
  ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $c = &$_SESSION['checkout'];

  if ($step === 1) {
    $c['shipping'] = [
      'name' => trim($_POST['name'] ?? ''),
      'phone' => trim($_POST['phone'] ?? ''),
      'address' => trim($_POST['address'] ?? ''),
      'city' => trim($_POST['city'] ?? ''),
    ];
    header('Location: ' . base_url('app/orders/checkout.php?step=2')); exit;
  }

  if ($step === 2) {
    $method = $_POST['shipping_method'] ?? 'standard';
    if (!in_array($method, ['standard','express'], true)) $method = 'standard';
    $c['shipping_method'] = $method;
    $c['shipping_cost'] = ($method === 'express') ? 120.0 : 60.0;
    header('Location: ' . base_url('app/orders/checkout.php?step=3')); exit;
  }

  if ($step === 3) {
    $pm = $_POST['payment_method'] ?? 'cod';
    if (!in_array($pm, ['cod','bkash','nagad','bank'], true)) $pm = 'cod';
    $c['payment_method'] = $pm;
    header('Location: ' . base_url('app/orders/checkout.php?step=4')); exit;
  }

  if ($step === 4) {
    // Create order or redirect to payment simulation
    $c = $_SESSION['checkout'];
    $user = current_user();

    try {
      $pdo->beginTransaction();

      if ($c['payment_method'] === 'cod') {
        $orderId = create_order($pdo, (int)$user['user_id'], $cart, $c['shipping'], $c['shipping_method'], (float)$c['shipping_cost'], 'Cash on Delivery', 'unpaid');
        $pdo->commit();

        // Clear
        cart_set([]);
        unset($_SESSION['checkout']);

        // Email (stub)
        send_order_email_stub($user['email'], "LifeX Order #$orderId", "Your order has been placed. Invoice: " . base_url("invoice.php?id=$orderId"));

        flash_set('ok', "Order placed! Order #$orderId");
        header('Location: ' . base_url('invoice.php?id=' . $orderId));
        exit;
      } else {
        // Store pending payload in session, then redirect
        $token = bin2hex(random_bytes(16));
        $_SESSION['pending_payment'] = [
          'token' => $token,
          'user_id' => (int)$user['user_id'],
          'cart' => $cart,
          'checkout' => $c,
          'created_at' => time(),
        ];
        $pdo->commit();
        header('Location: ' . base_url('app/payments/' . $c['payment_method'] . '.php?token=' . $token));
        exit;
      }
    } catch (Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      flash_set('err', 'Checkout failed: ' . $e->getMessage());
      header('Location: ' . base_url('app/orders/checkout.php?step=4'));
      exit;
    }
  }
}

$meta = seo_meta('Checkout');

$c = $_SESSION['checkout'];
$user = current_user();
$total = cart_total($pdo) + (float)$c['shipping_cost'];

include __DIR__ . '/../partials/header.php';
?>
<section class="section">
  <div class="container" style="max-width:920px">
    <h2 class="reveal">Checkout</h2>
    <p class="subtle reveal">Step <?= $step ?>/4 — Address → Shipping → Payment → Confirm</p>

    <?php if ($msg = flash_get('err')): ?>
      <div class="notice" style="background:rgba(255,80,80,.12)"><?= e($msg) ?></div>
    <?php endif; ?>

    <div class="card reveal">
      <div class="pad">
        <?php if ($step === 1): ?>
          <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="grid" style="grid-template-columns:repeat(2,1fr)">
              <div>
                <label class="muted small">Full Name</label>
                <input class="input" name="name" required value="<?= e($c['shipping']['name']) ?>">
              </div>
              <div>
                <label class="muted small">Phone</label>
                <input class="input" name="phone" required value="<?= e($c['shipping']['phone']) ?>">
              </div>
            </div>
            <div style="height:10px"></div>
            <label class="muted small">Address</label>
            <input class="input" name="address" required value="<?= e($c['shipping']['address']) ?>">
            <div style="height:10px"></div>
            <label class="muted small">City</label>
            <input class="input" name="city" required value="<?= e($c['shipping']['city']) ?>">
            <div class="actions" style="margin-top:14px">
              <button class="btn primary" type="submit">Continue</button>
              <a class="btn" href="<?= e(base_url('cart.php')) ?>">Back to cart</a>
            </div>
          </form>

        <?php elseif ($step === 2): ?>
          <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="row" style="align-items:flex-start">
              <label class="card" style="flex:1;cursor:pointer">
                <div class="pad">
                  <div class="row">
                    <div>
                      <div style="font-weight:800">Standard</div>
                      <div class="muted small">3–5 days</div>
                    </div>
                    <div class="price">৳60</div>
                  </div>
                  <div style="margin-top:10px">
                    <input type="radio" name="shipping_method" value="standard" <?= $c['shipping_method']==='standard'?'checked':'' ?>>
                  </div>
                </div>
              </label>
              <label class="card" style="flex:1;cursor:pointer">
                <div class="pad">
                  <div class="row">
                    <div>
                      <div style="font-weight:800">Express</div>
                      <div class="muted small">1–2 days</div>
                    </div>
                    <div class="price">৳120</div>
                  </div>
                  <div style="margin-top:10px">
                    <input type="radio" name="shipping_method" value="express" <?= $c['shipping_method']==='express'?'checked':'' ?>>
                  </div>
                </div>
              </label>
            </div>
            <div class="actions" style="margin-top:14px">
              <button class="btn primary" type="submit">Continue</button>
              <a class="btn" href="<?= e(base_url('app/orders/checkout.php?step=1')) ?>">Back</a>
            </div>
          </form>

        <?php elseif ($step === 3): ?>
          <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="grid" style="grid-template-columns:repeat(2,1fr)">
              <?php
                $pm = $c['payment_method'];
                $opts = [
                  'cod' => ['Cash on Delivery', 'Pay when delivered'],
                  'bkash' => ['bKash (demo)', 'Simulated payment success/fail'],
                  'nagad' => ['Nagad (demo)', 'Simulated payment success/fail'],
                  'bank' => ['Bank Account (demo)', 'Simulated payment success/fail'],
                ];
                foreach ($opts as $k => $v):
              ?>
              <label class="card" style="cursor:pointer">
                <div class="pad">
                  <div style="font-weight:800"><?= e($v[0]) ?></div>
                  <div class="muted small" style="margin-top:6px"><?= e($v[1]) ?></div>
                  <div style="margin-top:10px">
                    <input type="radio" name="payment_method" value="<?= e($k) ?>" <?= $pm===$k?'checked':'' ?>>
                  </div>
                </div>
              </label>
              <?php endforeach; ?>
            </div>
            <div class="actions" style="margin-top:14px">
              <button class="btn primary" type="submit">Continue</button>
              <a class="btn" href="<?= e(base_url('app/orders/checkout.php?step=2')) ?>">Back</a>
            </div>
          </form>

        <?php else: ?>
          <div class="row" style="align-items:flex-start">
            <div style="flex:1">
              <div class="muted small">Shipping</div>
              <div style="font-weight:800"><?= e($c['shipping']['name']) ?></div>
              <div class="muted small"><?= e($c['shipping']['phone']) ?></div>
              <div class="muted small"><?= e($c['shipping']['address']) ?>, <?= e($c['shipping']['city']) ?></div>
              <div class="hr"></div>
              <div class="muted small">Method</div>
              <div style="font-weight:800"><?= e(ucfirst($c['shipping_method'])) ?> • ৳<?= number_format((float)$c['shipping_cost'],2) ?></div>
              <div class="hr"></div>
              <div class="muted small">Payment</div>
              <div style="font-weight:800"><?= e($c['payment_method']==='cod'?'Cash on Delivery': strtoupper($c['payment_method']).' (demo)') ?></div>
            </div>

            <div class="card" style="flex:1">
              <div class="pad">
                <div class="row"><div class="muted">Cart total</div><div>৳<?= number_format(cart_total($pdo),2) ?></div></div>
                <div class="row"><div class="muted">Shipping</div><div>৳<?= number_format((float)$c['shipping_cost'],2) ?></div></div>
                <div class="hr"></div>
                <div class="row"><div class="muted">Payable</div><div class="price">৳<?= number_format($total,2) ?></div></div>
                <form method="post" style="margin-top:14px">
                  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                  <button class="btn primary" type="submit" style="width:100%">Place Order</button>
                </form>
                <div class="actions" style="margin-top:10px">
                  <a class="btn" href="<?= e(base_url('app/orders/checkout.php?step=3')) ?>">Back</a>
                </div>
                <div class="muted small" style="margin-top:10px">For demo gateways, you’ll see a simulated payment screen.</div>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>

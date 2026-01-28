<?php
require_once __DIR__ . '/app/helpers.php';
$meta = seo_meta('Your cart', 'Review items in your cart and continue to checkout.');

$cart = cart_get();
$items = [];
if ($cart) {
  $ids = array_keys($cart);
  $in = implode(',', array_fill(0, count($ids), '?'));
  $stmt = $pdo->prepare("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.category_id=p.category_id WHERE p.product_id IN ($in)");
  $stmt->execute($ids);
  $items = $stmt->fetchAll();
}
$total = cart_total($pdo);

$ok = flash_get('ok');
$err = flash_get('err');

include __DIR__ . '/app/partials/header.php';
?>
<section class="section">
  <div class="container">
    <h2 class="reveal">Cart</h2>
    <?php if ($ok): ?><div class="notice"><?= e($ok) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="notice" style="background:rgba(255,80,80,.12)"><?= e($err) ?></div><?php endif; ?>

    <?php if (!$cart): ?>
      <div class="card reveal"><div class="pad">
        <div class="muted">Your cart is empty.</div>
        <div style="margin-top:12px"><a class="btn primary" href="<?= e(base_url()) ?>#products">Shop now</a></div>
      </div></div>
    <?php else: ?>
      <div class="card reveal">
        <div class="pad">
          <table class="table">
            <thead>
              <tr>
                <th>Product</th>
                <th style="width:120px">Price</th>
                <th style="width:140px">Quantity</th>
                <th style="width:120px">Subtotal</th>
                <th style="width:90px">Remove</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $p): 
                $pid = (int)$p['product_id'];
                $qty = (int)($cart[$pid] ?? 0);
                if ($qty < 1) $qty = 1;
                $sub = (float)$p['price'] * $qty;
              ?>
              <tr>
                <td>
                  <div style="font-weight:800"><?= e($p['name']) ?></div>
                  <div class="muted small"><?= e($p['category_name'] ?? '') ?></div>
                </td>
                <td>৳<?= number_format((float)$p['price'], 2) ?></td>
                <td>
                  <form action="<?= e(base_url('app/cart/update.php')) ?>" method="post" style="display:flex;gap:8px;align-items:center">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="product_id" value="<?= $pid ?>">
                    <input class="input" style="width:80px" type="number" min="1" max="<?= (int)$p['stock_qty'] ?>" name="qty" value="<?= (int)$qty ?>" required>
                    <button class="btn" type="submit">Update</button>
                  </form>
                </td>
                <td>৳<?= number_format($sub, 2) ?></td>
                <td>
                  <form action="<?= e(base_url('app/cart/remove.php')) ?>" method="post">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="product_id" value="<?= $pid ?>">
                    <button class="btn" type="submit">X</button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>

          <div class="hr"></div>

          <div class="row">
            <div class="muted">Total</div>
            <div class="price" style="font-size:20px">৳<?= number_format($total, 2) ?></div>
          </div>

          <div class="actions" style="margin-top:14px">
            <a class="btn" href="<?= e(base_url()) ?>#products">Continue Shopping</a>
            <a class="btn primary" href="<?= e(base_url('app/orders/checkout.php')) ?>">Proceed to Checkout</a>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php include __DIR__ . '/app/partials/footer.php'; ?>
